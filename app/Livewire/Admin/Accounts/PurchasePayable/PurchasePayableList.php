<?php

namespace App\Livewire\Admin\Accounts\PurchasePayable;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\EntryMethod;
use App\Enums\Accounts\PurchasePayableStatus;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\Account;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayable;
use App\Models\Supplier;
use App\Services\Accounts\PurchasePayableService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PurchasePayableList extends Component
{
    use InteractsWithAccountsAccess;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?int $purchase_order_id = null;

    public ?int $supplier_id = null;

    public float|int|string $payable_amount = '';

    public float|int|string $paid_amount = 0;

    public bool $showSettlementModal = false;

    public ?int $settlementPayableId = null;

    public string $settlement_date = '';

    public string $settlement_method = '';

    public ?int $settlement_payment_account_id = null;

    public ?int $settlement_payable_account_id = null;

    public float|int|string $settlement_amount = '';

    public ?string $settlement_payee_name = null;

    public ?string $settlement_notes = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('accounts.purchase-payable.list');

        $this->settlement_date = now()->toDateString();
        $this->settlement_method = EntryMethod::BANK->value;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('accounts.purchase-payable.create');

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorizePermission('accounts.purchase-payable.edit');

        $payable = PurchasePayable::query()->find($id);

        if (! $payable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Payable not found.']);

            return;
        }

        $this->editingId = (int) $payable->id;
        $this->purchase_order_id = (int) $payable->purchase_order_id;
        $this->supplier_id = $payable->supplier_id ? (int) $payable->supplier_id : null;
        $this->payable_amount = (float) $payable->payable_amount;
        $this->paid_amount = (float) $payable->paid_amount;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function save(): void
    {
        $permission = $this->editingId ? 'accounts.purchase-payable.edit' : 'accounts.purchase-payable.create';
        $this->authorizePermission($permission);

        $validated = $this->validate($this->rules(), $this->messages());

        try {
            $payable = $this->editingId
                ? PurchasePayable::query()->findOrFail($this->editingId)
                : null;

            app(PurchasePayableService::class)->savePayable($validated, $payable);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase payable saved successfully.']);

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function deletePayable(int $id): void
    {
        $this->authorizePermission('accounts.purchase-payable.delete');

        $payable = PurchasePayable::query()->find($id);

        if (! $payable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Payable not found.']);

            return;
        }

        try {
            app(PurchasePayableService::class)->deletePayable($payable);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase payable deleted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function openSettlementModal(int $id): void
    {
        $this->authorizePermission('accounts.purchase-payable.settle');

        $payable = PurchasePayable::query()->with('supplier:id,name')->find($id);

        if (! $payable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Payable not found.']);

            return;
        }

        if ((float) $payable->due_amount <= 0) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Selected payable has no due amount.']);

            return;
        }

        $this->settlementPayableId = $payable->id;
        $this->settlement_date = now()->toDateString();
        $this->settlement_method = EntryMethod::BANK->value;
        $this->settlement_amount = (float) $payable->due_amount;
        $this->settlement_payee_name = $payable->supplier?->name;
        $this->settlement_notes = null;
        $this->settlement_payment_account_id = null;
        $this->settlement_payable_account_id = null;
        $this->showSettlementModal = true;
    }

    public function closeSettlementModal(): void
    {
        $this->showSettlementModal = false;
        $this->resetSettlementForm();
    }

    public function settlePayable(): void
    {
        $this->authorizePermission('accounts.purchase-payable.settle');

        $validated = $this->validate($this->settlementRules(), $this->settlementMessages());

        try {
            $payable = PurchasePayable::query()->findOrFail($validated['settlementPayableId']);

            app(PurchasePayableService::class)->settlePayable($payable, [
                'date' => $validated['settlement_date'],
                'method' => $validated['settlement_method'],
                'payment_account_id' => $validated['settlement_payment_account_id'],
                'payable_account_id' => $validated['settlement_payable_account_id'],
                'amount' => $validated['settlement_amount'],
                'payee_name' => $validated['settlement_payee_name'],
                'notes' => $validated['settlement_notes'],
            ], (int) auth()->id());
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);

            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payable settled successfully.']);

        $this->showSettlementModal = false;
        $this->resetSettlementForm();
    }

    public function render(): View
    {
        $this->authorizePermission('accounts.purchase-payable.list');

        $payables = PurchasePayable::query()
            ->with([
                'purchaseOrder:id,po_no',
                'supplier:id,name,code',
                'transaction:id,date,type',
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->whereHas('purchaseOrder', function (Builder $purchaseQuery) use ($search): void {
                        $purchaseQuery->where('po_no', 'like', $search);
                    })->orWhereHas('supplier', function (Builder $supplierQuery) use ($search): void {
                        $supplierQuery->where('name', 'like', $search)
                            ->orWhere('code', 'like', $search);
                    });
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $query): Builder => $query->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(15);

        $purchaseOrders = PurchaseOrder::query()
            ->latest('id')
            ->get(['id', 'po_no', 'order_date']);

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $activeAccounts = Account::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        $groupedAccounts = $activeAccounts->groupBy(fn (Account $account): string => $account->type?->value ?? AccountType::ASSET->value);

        return view('livewire.admin.accounts.purchase-payable.purchase-payable-list', [
            'payables' => $payables,
            'statuses' => PurchasePayableStatus::cases(),
            'purchaseOrders' => $purchaseOrders,
            'suppliers' => $suppliers,
            'methods' => EntryMethod::cases(),
            'types' => AccountType::cases(),
            'groupedAccounts' => $groupedAccounts,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'payable_amount' => ['required', 'numeric', 'gt:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function settlementRules(): array
    {
        return [
            'settlementPayableId' => ['required', 'integer', 'exists:purchase_payables,id'],
            'settlement_date' => ['required', 'date'],
            'settlement_method' => ['required', Rule::in(array_map(static fn (EntryMethod $method): string => $method->value, EntryMethod::cases()))],
            'settlement_payment_account_id' => ['required', 'exists:accounts,id'],
            'settlement_payable_account_id' => ['required', 'exists:accounts,id'],
            'settlement_amount' => ['required', 'numeric', 'gt:0'],
            'settlement_payee_name' => ['nullable', 'string', 'max:150'],
            'settlement_notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'purchase_order_id.required' => 'Purchase order is required.',
            'payable_amount.required' => 'Payable amount is required.',
            'payable_amount.gt' => 'Payable amount must be greater than zero.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function settlementMessages(): array
    {
        return [
            'settlement_payment_account_id.required' => 'Payment account is required.',
            'settlement_payable_account_id.required' => 'Payable account is required.',
            'settlement_amount.required' => 'Settlement amount is required.',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId',
            'purchase_order_id',
            'supplier_id',
            'payable_amount',
            'paid_amount',
        ]);

        $this->paid_amount = 0;
    }

    protected function resetSettlementForm(): void
    {
        $this->reset([
            'settlementPayableId',
            'settlement_payment_account_id',
            'settlement_payable_account_id',
            'settlement_amount',
            'settlement_payee_name',
            'settlement_notes',
        ]);

        $this->settlement_date = now()->toDateString();
        $this->settlement_method = EntryMethod::BANK->value;
    }
}
