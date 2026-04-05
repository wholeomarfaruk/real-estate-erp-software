<?php

namespace App\Livewire\Admin\Supplier\Ledger;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Models\SupplierLedger as SupplierLedgerModel;
use App\Services\Supplier\SupplierLedgerService;
use App\Services\Supplier\SupplierStatementService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierStatement extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public ?int $supplier_id = null;

    public ?string $from_date = null;

    public ?string $to_date = null;

    public int $perPage = 20;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.statement.view');
        $this->from_date = now()->startOfMonth()->toDateString();
        $this->to_date = now()->toDateString();
    }

    public function updatedSupplierId(): void
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

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function printPlaceholder(): void
    {
        $this->authorizePermission('supplier.statement.print');

        $this->dispatch('toast', [
            'type' => 'info',
            'message' => 'Print/export will be enabled in a later step.',
        ]);
    }

    public function exportPlaceholder(): void
    {
        $this->authorizePermission('supplier.statement.print');

        $this->dispatch('toast', [
            'type' => 'info',
            'message' => 'Print/export will be enabled in a later step.',
        ]);
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.statement.view');

        $this->syncSelectedSupplierLedger();

        $statementService = app(SupplierStatementService::class);

        $supplier = $this->supplier_id
            ? Supplier::query()->find($this->supplier_id, ['id', 'name', 'code', 'company_name', 'contact_person', 'phone', 'email', 'address'])
            : null;

        $summary = $supplier
            ? $statementService->buildSummary(
                supplierId: (int) $this->supplier_id,
                fromDate: $this->from_date,
                toDate: $this->to_date
            )
            : [
                'opening_balance' => 0,
                'total_debit' => 0,
                'total_credit' => 0,
                'closing_balance' => 0,
            ];

        $pendingSummary = $supplier
            ? $statementService->pendingBillsSummary((int) $this->supplier_id)
            : [
                'pending_count' => 0,
                'pending_amount' => 0,
                'overdue_count' => 0,
                'overdue_amount' => 0,
            ];

        $unallocatedSummary = $supplier
            ? $statementService->unallocatedPaymentSummary((int) $this->supplier_id, $this->to_date)
            : [
                'unallocated_count' => 0,
                'unallocated_amount' => 0,
            ];

        $transactionQuery = $supplier
            ? $statementService->transactionQuery(
                supplierId: (int) $this->supplier_id,
                fromDate: $this->from_date,
                toDate: $this->to_date
            )
            : SupplierLedgerModel::query()->whereRaw('1 = 0');

        $transactions = $transactionQuery
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->paginate($this->perPage);

        return view('livewire.admin.supplier.ledger.supplier-statement', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'code']),
            'supplier' => $supplier,
            'summary' => $summary,
            'pendingSummary' => $pendingSummary,
            'unallocatedSummary' => $unallocatedSummary,
            'transactions' => $transactions,
        ])->layout('layouts.admin.admin');
    }

    protected function syncSelectedSupplierLedger(): void
    {
        if (! $this->supplier_id) {
            return;
        }

        app(SupplierLedgerService::class)->syncSupplierFromSource((int) $this->supplier_id, (int) auth()->id());
    }
}
