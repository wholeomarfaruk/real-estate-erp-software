<?php

namespace App\Livewire\Admin\Inventory\PurchaseOrder;

use App\Enums\Accounts\EntryMethod;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\PurchaseFund;
use App\Models\PurchaseOrder;
use App\Models\TransactionCategory;
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
    public string  $transaction_category_id = '';  // TransactionCategory id (Employee/Supplier Advance)
    public ?int    $bank_account_id          = null; // Source BankAccount id
    public string  $method                   = '';   // EntryMethod: cash | bank | cheque | mobile_banking
    public string  $release_date             = '';
    public float   $amount                   = 0;
    public string  $payee_type               = '';   // auto-derived: employee | supplier
    public ?int    $receiver_id              = null;
    public ?string $remarks                  = null;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->authorizePermission('inventory.purchase_order.fund_release');

        $this->purchaseOrder = $purchaseOrder->load([
            'store:id,name,code,type',
            'supplier:id,name',
            'funds' => fn ($q) => $q->with($this->fundsEagerLoad())->latest('release_date'),
        ]);

        $this->ensurePurchaseOrderAccessible($this->purchaseOrder);

        $this->release_date = now()->toDateString();

        // Pre-select supplier advance if PO has a supplier
        if ($this->purchaseOrder->supplier_id) {
            $supplierCategory = TransactionCategory::query()
                ->where('slug', 'supplier-advance')
                ->first();

            if ($supplierCategory) {
                $this->transaction_category_id = (string) $supplierCategory->id;
                $this->payee_type              = 'supplier';
                $this->receiver_id             = (int) $this->purchaseOrder->supplier_id;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Reactive handlers
    // -------------------------------------------------------------------------

    public function updatedTransactionCategoryId(): void
    {
        $this->payee_type  = '';
        $this->receiver_id = null;

        if (! $this->transaction_category_id) {
            return;
        }

        $category = TransactionCategory::query()->find((int) $this->transaction_category_id);
        if (! $category) {
            return;
        }

        if ($category->slug === 'supplier-advance') {
            $this->payee_type  = 'supplier';
            $this->receiver_id = $this->purchaseOrder->supplier_id
                ? (int) $this->purchaseOrder->supplier_id
                : null;
        } elseif ($category->slug === 'employee-advance') {
            $this->payee_type  = 'employee';
            $this->receiver_id = null;
        }
    }

    // -------------------------------------------------------------------------
    // Save
    // -------------------------------------------------------------------------

    public function save(): void
    {
        $this->authorizePermission('inventory.purchase_order.fund_release');

        $validated = $this->validate($this->rules(), $this->messages());

        // Fast cap check — gives immediate UI feedback before hitting the service.
        $approvedAmount   = round((float) ($this->purchaseOrder->approved_amount ?? 0), 2);
        $alreadyCommitted = round(
            (float) PurchaseFund::query()
                ->where('purchase_order_id', $this->purchaseOrder->id)
                ->whereIn('status', ['pending', 'completed'])
                ->sum('amount'),
            2
        );
        $remaining = round(max(0, $approvedAmount - $alreadyCommitted), 2);

        if ($approvedAmount <= 0) {
            $this->addError('amount', 'No approved amount is set for this purchase order.');
            return;
        }

        if ((float) $this->amount > $remaining) {
            $this->addError('amount', sprintf(
                'Exceeds remaining allowance of %s (Approved: %s − Committed: %s).',
                number_format($remaining, 2),
                number_format($approvedAmount, 2),
                number_format($alreadyCommitted, 2)
            ));
            return;
        }

        try {
            app(FundReleaseService::class)->requestRelease(
                $this->purchaseOrder,
                $validated,
                (int) auth()->id()
            );

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Fund request submitted for banking approval.']);
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
            'funds' => fn ($q) => $q->with($this->fundsEagerLoad())->latest('release_date'),
        ]);

        $totalCommitted = (float) $this->purchaseOrder->funds
            ->whereIn('status', ['pending', 'completed'])
            ->sum(fn ($f) => (float) $f->amount);

        $unreleased = max(0, (float) ($this->purchaseOrder->approved_amount ?? 0) - $totalCommitted);

        // Only employee and supplier advance are valid for a purchase order fund release
        $advanceCategories = TransactionCategory::query()
            ->active()
            ->where('type', 'advance')
            ->whereIn('slug', ['employee-advance', 'supplier-advance'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        // Source accounts: active bank accounts
        $sourceAccounts = BankAccount::query()
            ->where('status', 'active')
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'type', 'ac_number', 'code']);

        // Receivers — employees or the PO supplier
        $receivers = match ($this->payee_type) {
            'employee' => Employee::query()->orderBy('name')->get(['id', 'name']),
            'supplier' => $this->purchaseOrder->supplier
                ? collect([$this->purchaseOrder->supplier])
                : collect(),
            default => collect(),
        };

        return view('livewire.admin.inventory.purchase-order.purchase-fund-form', [
            'advanceCategories' => $advanceCategories,
            'paymentMethods'    => EntryMethod::cases(),
            'sourceAccounts'    => $sourceAccounts,
            'receivers'         => $receivers,
            'totalCommitted'    => round($totalCommitted, 2),
            'unreleased'        => round($unreleased, 2),
        ])->layout('layouts.admin.admin');
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    protected function rules(): array
    {
        return [
            'transaction_category_id' => ['required', 'integer', Rule::exists('transaction_categories', 'id')->where('type', 'advance')],
            'bank_account_id'         => ['required', 'integer', 'exists:bank_accounts,id'],
            'method'                  => ['required', Rule::in(array_column(EntryMethod::cases(), 'value'))],
            'release_date'            => ['required', 'date'],
            'amount'                  => ['required', 'numeric', 'min:0.01'],
            'payee_type'              => ['required', Rule::in(['employee', 'supplier'])],
            'receiver_id'             => ['required', 'integer', 'min:1'],
            'remarks'                 => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.min'                       => 'Release amount must be greater than zero.',
            'transaction_category_id.required' => 'Please select the advance type.',
            'bank_account_id.required'         => 'Please select the source account.',
            'method.required'                  => 'Please select a payment method.',
        ];
    }

    // -------------------------------------------------------------------------
    // Access helpers
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

    private function fundsEagerLoad(): array
    {
        return [
            'releaser:id,name',
            'receiver:id,name',
            'transactionCategory:id,name,slug',
            'bankAccount:id,bank_name,type',
            'transaction:id,account_id,transaction_category_id,method,debit',
            'transaction.account:id,name,code',
            'transaction.account.bankAccount:id,account_id,bank_name,type',
            'transaction.transactionCategory:id,name,slug',
        ];
    }
}
