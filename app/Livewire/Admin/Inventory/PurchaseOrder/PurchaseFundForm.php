<?php

namespace App\Livewire\Admin\Inventory\PurchaseOrder;

use App\Enums\Accounts\EntryMethod;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Account;
use App\Models\Employee;
use App\Models\PurchaseFund;
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
    // Form fields — a PO fund release is always a Supplier Advance; the money is
    // paid directly to the supplier or through an employee intermediary.
    // -------------------------------------------------------------------------
    public string  $account_type       = '';               // filter: cash | bank | mfs | wallet
    public ?int    $payment_account_id = null;             // Source COA money account (accounts.id)
    public string  $method             = '';               // EntryMethod
    public string  $release_date       = '';
    public float   $amount             = 0;
    public string  $receiver_mode      = 'supplier_direct'; // supplier_direct | via_employee
    public ?int    $receiver_id        = null;              // employee id when via_employee
    public ?string $reference_no       = null;              // optional voucher / reference
    public ?string $remarks            = null;

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
    }

    // -------------------------------------------------------------------------
    // Reactive handlers
    // -------------------------------------------------------------------------

    public function updatedReceiverMode(): void
    {
        // Employee is only chosen when paying through an employee.
        $this->receiver_id = null;
    }

    public function updatedAccountType(): void
    {
        // Reset the chosen account when the type filter changes.
        $this->payment_account_id = null;
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

        // Source accounts: chart-of-accounts money accounts of the chosen type.
        // A BankAccount (when linked) is just info attached to the COA account.
        $sourceAccounts = $this->account_type
            ? Account::query()
                ->where('is_active', true)
                ->where('type', $this->account_type)
                ->with('bankAccount:id,account_id,bank_name,ac_number')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'type'])
            : collect();

        // Employees — only needed when paying the advance through an employee.
        $employees = $this->receiver_mode === 'via_employee'
            ? Employee::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('livewire.admin.inventory.purchase-order.purchase-fund-form', [
            'paymentMethods' => EntryMethod::cases(),
            'accountTypes'   => ['cash' => 'Cash', 'bank' => 'Bank', 'mfs' => 'MFS', 'wallet' => 'Wallet'],
            'sourceAccounts' => $sourceAccounts,
            'employees'      => $employees,
            'totalCommitted' => round($totalCommitted, 2),
            'unreleased'     => round($unreleased, 2),
        ])->layout('layouts.admin.admin');
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    protected function rules(): array
    {
        return [
            'account_type'       => ['required', Rule::in(['cash', 'bank', 'mfs', 'wallet'])],
            // Source is a chart-of-accounts money account of the chosen type.
            'payment_account_id' => [
                'required', 'integer',
                Rule::exists('accounts', 'id')->where('is_active', true)->where('type', $this->account_type),
            ],
            'method'        => ['required', Rule::in(array_column(EntryMethod::cases(), 'value'))],
            'release_date'  => ['required', 'date'],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'receiver_mode' => ['required', Rule::in(['supplier_direct', 'via_employee'])],
            // Employee receiver is required only when paying through an employee.
            'receiver_id'   => ['nullable', 'required_if:receiver_mode,via_employee', 'integer', 'exists:employees,id'],
            'reference_no'  => ['nullable', 'string', 'max:100'],
            'remarks'       => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.min'                 => 'Release amount must be greater than zero.',
            'account_type.required'      => 'Please select the account type.',
            'payment_account_id.required' => 'Please select the source account.',
            'method.required'            => 'Please select a payment method.',
            'receiver_id.required_if'    => 'Please select the employee receiving the advance.',
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
            'bankAccount:id,bank_name,type',
            // Per-account movement lives in transaction_lines now (header debit/
            // credit/account_id were dropped). The credit line is the payment side.
            'transaction:id,type,method,name,phone,reference_no',
            'transaction.lines:id,transaction_id,account_id,debit,credit',
            'transaction.lines.account:id,name,code',
            'transaction.lines.account.bankAccount:id,account_id,bank_name,type',
        ];
    }
}
