<?php

namespace App\Livewire\Admin\Supplier\Reports;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Services\Supplier\SupplierReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierWiseReport extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public ?int $supplier_id = null;

    public ?string $from_date = null;

    public ?string $to_date = null;

    public string $status = '';

    public bool $due_only = false;

    public int $perPage = 20;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.reports.supplier-wise');
    }

    public function updatedSupplierId(): void
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

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDueOnly(): void
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
        $this->from_date = null;
        $this->to_date = null;
        $this->status = '';
        $this->due_only = false;
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
        $this->authorizePermission('supplier.reports.supplier-wise');

        $service = app(SupplierReportService::class);
        $filters = $this->filters();

        $rows = $service->supplierWiseQuery($filters)
            ->orderBy('suppliers.name')
            ->paginate($this->perPage);

        $summary = $service->supplierWiseSummary($filters);

        return view('livewire.admin.supplier.reports.supplier-wise-report', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'code']),
            'rows' => $rows,
            'summary' => $summary,
            'statusOptions' => [
                ['value' => '', 'label' => 'All'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'blocked', 'label' => 'Blocked'],
            ],
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array{supplier_id:?int,from_date:?string,to_date:?string,status:string,due_only:bool}
     */
    protected function filters(): array
    {
        return [
            'supplier_id' => $this->supplier_id,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'status' => $this->status,
            'due_only' => $this->due_only,
        ];
    }
}
