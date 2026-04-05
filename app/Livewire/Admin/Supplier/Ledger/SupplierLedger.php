<?php

namespace App\Livewire\Admin\Supplier\Ledger;

use App\Enums\Supplier\SupplierLedgerTransactionType;
use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Services\Supplier\SupplierLedgerService;
use App\Services\Supplier\SupplierStatementService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierLedger extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public ?int $supplierFilter = null;

    public ?string $from_date = null;

    public ?string $to_date = null;

    public string $transaction_type = '';

    public int $perPage = 20;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.ledger.view');
    }

    public function updatedSupplierFilter(): void
    {
        $this->resetPage();
        $this->syncSelectedSupplierLedger();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function updatedTransactionType(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->supplierFilter = null;
        $this->from_date = null;
        $this->to_date = null;
        $this->transaction_type = '';
        $this->perPage = 20;

        $this->resetPage();
    }

    public function rebuildBalances(): void
    {
        $this->authorizePermission('supplier.ledger.view');

        if (! $this->supplierFilter) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please select a supplier first.']);

            return;
        }

        app(SupplierLedgerService::class)->syncSupplierFromSource((int) $this->supplierFilter, (int) auth()->id());

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier ledger rebuilt successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.ledger.view');

        $this->syncSelectedSupplierLedger();

        $statementService = app(SupplierStatementService::class);
        $transactionType = $this->transaction_type !== '' ? $this->transaction_type : null;

        $summary = $statementService->buildSummary(
            supplierId: $this->supplierFilter ? (int) $this->supplierFilter : null,
            fromDate: $this->from_date,
            toDate: $this->to_date,
            transactionType: $transactionType
        );

        $ledgerEntries = $statementService->transactionQuery(
            supplierId: $this->supplierFilter ? (int) $this->supplierFilter : null,
            fromDate: $this->from_date,
            toDate: $this->to_date,
            transactionType: $transactionType
        )
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->paginate($this->perPage);

        return view('livewire.admin.supplier.ledger.supplier-ledger', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'code']),
            'transactionTypes' => SupplierLedgerTransactionType::cases(),
            'ledgerEntries' => $ledgerEntries,
            'summary' => $summary,
        ])->layout('layouts.admin.admin');
    }

    protected function syncSelectedSupplierLedger(): void
    {
        if (! $this->supplierFilter) {
            return;
        }

        app(SupplierLedgerService::class)->syncSupplierFromSource((int) $this->supplierFilter, (int) auth()->id());
    }
}
