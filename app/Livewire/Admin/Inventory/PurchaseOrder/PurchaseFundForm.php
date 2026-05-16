<?php

namespace App\Livewire\Admin\Inventory\PurchaseOrder;

use App\Enums\Accounts\EntryMethod;
use App\Enums\Inventory\FundReleaseType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Account;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Services\Inventory\FundReleaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PurchaseFundForm extends Component
{
    use InteractsWithInventoryAccess;

    public PurchaseOrder $purchaseOrder;

    // -------------------------------------------------------------------------
    // Form fields
    // -------------------------------------------------------------------------
    public string  $advance_type        = '';    // employee_advance | supplier_advance
    public ?int    $advance_account_id  = null;  // DR  (Employee/Supplier Advance account)
    public ?int    $payment_account_group_id = null;  // head: Cash / Bank
    public ?int    $payment_account_id      = null;  // specific child account (required)
    public array   $payment_account_children = [];
    public string  $payment_method          = '';    // EntryMethod: cash | bank | cheque | mobile_banking
    public string  $release_date        = '';
    public float   $amount              = 0;
    public string  $payee_type          = '';    // employee | supplier
    public ?int    $receiver_id         = null;
    public ?string $remarks             = null;


    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->authorizePermission('inventory.purchase_order.fund_release');

        $this->purchaseOrder = $purchaseOrder->load([
            'store:id,name,code,type',
            'supplier:id,name',
            'funds' => fn ($q) => $q->with(['releaser:id,name', 'receiver:id,name', 'advanceAccount:id,name', 'paymentAccount:id,name'])->latest('release_date'),
        ]);

        $this->ensurePurchaseOrderAccessible($this->purchaseOrder);

        $this->release_date = now()->toDateString();

        // Pre-fill payee_type from PO supplier if present
        if ($this->purchaseOrder->supplier_id) {
            $this->payee_type  = 'supplier';
            $this->receiver_id = (int) $this->purchaseOrder->supplier_id;
        }
    }

    // -------------------------------------------------------------------------
    // Reactive handlers
    // -------------------------------------------------------------------------

    public function updatedAdvanceType(): void
    {
        // Auto-set payee_type to match advance type
        $this->payee_type = $this->advance_type === FundReleaseType::EMPLOYEE_ADVANCE->value
            ? 'employee'
            : 'supplier';

        if ($this->payee_type === 'supplier') {
            $this->receiver_id = $this->purchaseOrder->supplier_id;
        } else {
            $this->receiver_id = null;
        }

        $this->advance_account_id = null;
    }

    public function updatedPaymentAccountGroupId(): void
    {
        $this->payment_account_id       = null;
        $this->payment_account_children = [];

        if (! $this->payment_account_group_id) {
            return;
        }

        $children = Account::query()
            ->where('is_active', true)
            ->where('parent_id', $this->payment_account_group_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        // If the head account has no sub-accounts, treat the head itself as the account
        if (empty($children)) {
            $head = Account::query()->find($this->payment_account_group_id, ['id', 'name']);
            $this->payment_account_children = $head ? [['id' => $head->id, 'name' => $head->name]] : [];
            $this->payment_account_id = $head?->id;
        } else {
            $this->payment_account_children = $children;
        }
    }

    // -------------------------------------------------------------------------
    // Save
    // -------------------------------------------------------------------------

    public function save(): void
    {
        $this->authorizePermission('inventory.purchase_order.fund_release');

        $validated = $this->validate($this->rules(), $this->messages());

        // Fast cap check before hitting the service — gives immediate UI feedback.
        // The service repeats this check inside a lockForUpdate for race safety.
        $approvedAmount  = round((float) ($this->purchaseOrder->approved_amount ?? 0), 2);
        $alreadyReleased = round(
            (float) \App\Models\PurchaseFund::query()
                ->where('purchase_order_id', $this->purchaseOrder->id)
                ->whereNotNull('transaction_id')
                ->sum('amount'),
            2
        );
        $remaining = round(max(0, $approvedAmount - $alreadyReleased), 2);

        if ($approvedAmount <= 0) {
            $this->addError('amount', 'No approved amount is set for this purchase order.');
            return;
        }

        if ((float) $this->amount > $remaining) {
            $this->addError('amount', sprintf(
                'Exceeds remaining allowance of %s (Approved: %s − Released: %s).',
                number_format($remaining, 2),
                number_format($approvedAmount, 2),
                number_format($alreadyReleased, 2)
            ));
            return;
        }

        try {
            app(FundReleaseService::class)->release(
                $this->purchaseOrder,
                $validated,
                (int) auth()->id()
            );

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Fund release recorded and accounting entries posted.']);
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            return;
        }

        redirect()->route('admin.inventory.purchase-orders.view', $this->purchaseOrder);
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        $this->purchaseOrder->load([
            'funds' => fn ($q) => $q->with(['releaser:id,name', 'receiver:id,name', 'advanceAccount:id,name', 'paymentAccount:id,name'])->latest('release_date'),
        ]);

        $totalReleased = (float) $this->purchaseOrder->funds->sum(fn ($f) => (float) $f->amount);
        $unreleased    = max(0, (float) ($this->purchaseOrder->approved_amount ?? 0) - $totalReleased);

        // Advance accounts — any active asset account that contains "advance"
        $advanceAccounts = Account::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->where('name', 'like', '%advance%')
                ->orWhere('name', 'like', '%Advance%')
            )
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // First dropdown: head accounts whose name contains cash or bank (type = asset, must have children)
        $cashBankGroups = Account::query()
            ->where('is_active', true)
            ->where('type', 'asset')
            ->where(fn ($q) => $q
                ->where('name', 'like', '%cash%')
                ->orWhere('name', 'like', '%bank%')
                ->orWhereHas('parent', fn ($q2) => $q2->where('name', 'like', '%Assets%'))
            )
            ->whereHas('children')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Receivers
        $receivers = match ($this->payee_type) {
            'employee' => Employee::query()->orderBy('name')->get(['id', 'name']),
            'supplier' => $this->purchaseOrder->supplier
                ? collect([$this->purchaseOrder->supplier])
                : collect(),
            default => collect(),
        };

        return view('livewire.admin.inventory.purchase-order.purchase-fund-form', [
            'advanceTypes'    => FundReleaseType::cases(),
            'paymentMethods'  => EntryMethod::cases(),
            'advanceAccounts' => $advanceAccounts,
            'cashBankGroups'  => $cashBankGroups,
            'receivers'       => $receivers,
            'totalReleased'   => round($totalReleased, 2),
            'unreleased'      => round($unreleased, 2),
        ])->layout('layouts.admin.admin');
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    protected function rules(): array
    {
        return [
            'advance_type'       => ['required', Rule::in(array_column(FundReleaseType::cases(), 'value'))],
            'advance_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payment_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payment_method'     => ['required', Rule::in(array_column(EntryMethod::cases(), 'value'))],
            'release_date'       => ['required', 'date'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'payee_type'         => ['required', Rule::in(['employee', 'supplier'])],
            'receiver_id'        => ['required', 'integer', 'min:1'],
            'remarks'            => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.min'             => 'Release amount must be greater than zero.',
            'advance_account_id.required' => 'Please select the advance account (DR).',
            'payment_account_id.required' => 'Please select the cash/bank account (CR).',
        ];
    }

    // -------------------------------------------------------------------------
    // Access helpers (keep compatibility with existing trait usage)
    // -------------------------------------------------------------------------

    protected function ensurePurchaseOrderAccessible(PurchaseOrder $purchaseOrder): void
    {
        if ($this->canViewAllStores()) {
            return;
        }

        $storeIds = $this->getAccessibleStoreIds();

        abort_unless(
            in_array((int) $purchaseOrder->store_id, $storeIds, true),
            403,
            'You are not allowed to access this purchase order.'
        );
    }

    protected function canViewAllStores(): bool
    {
        return $this->hasInventoryWideAccess($this->purchaseOrderGlobalAccessPermissions());
    }
}
