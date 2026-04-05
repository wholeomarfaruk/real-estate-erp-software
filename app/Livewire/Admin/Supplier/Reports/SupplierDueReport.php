<?php

namespace App\Livewire\Admin\Supplier\Reports;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Services\Supplier\SupplierReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierDueReport extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public ?int $supplier_id = null;

    public bool $due_only = false;

    public bool $overdue_only = false;

    public ?string $from_date = null;

    public ?string $to_date = null;

    public int $perPage = 20;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.reports.due');
    }

    public function updatedSupplierId(): void
    {
        $this->resetPage();
    }

    public function updatedDueOnly(): void
    {
        $this->resetPage();
    }

    public function updatedOverdueOnly(): void
    {
        $this->resetPage();
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

    public function resetFilters(): void
    {
        $this->supplier_id = null;
        $this->due_only = false;
        $this->overdue_only = false;
        $this->from_date = null;
        $this->to_date = null;
        $this->perPage = 20;

        $this->resetPage();
    }

    public function printPlaceholder(): void
    {
        $this->dispatch('toast', [
            'type' => 'info',
            'message' => 'Print/export will be enabled in a later step.',
        ]);
    }

    public function exportPlaceholder(): void
    {
        $this->dispatch('toast', [
            'type' => 'info',
            'message' => 'Print/export will be enabled in a later step.',
        ]);
    }

    public function render(): View
    {
        $this->authorizePermission('supplier.reports.due');

        $service = app(SupplierReportService::class);
        $filters = $this->filters();

        $rows = $service->supplierDueQuery($filters)
            ->orderByDesc('net_payable')
            ->orderBy('suppliers.name')
            ->paginate($this->perPage);

        $summary = $service->supplierDueSummary($filters);

        return view('livewire.admin.supplier.reports.supplier-due-report', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'code']),
            'rows' => $rows,
            'summary' => $summary,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array{supplier_id:?int,due_only:bool,overdue_only:bool,from_date:?string,to_date:?string}
     */
    protected function filters(): array
    {
        return [
            'supplier_id' => $this->supplier_id,
            'due_only' => $this->due_only,
            'overdue_only' => $this->overdue_only,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
        ];
    }
}
