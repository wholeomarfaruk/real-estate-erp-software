<?php

namespace App\Livewire\Admin\Supplier\Reports;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Services\Supplier\SupplierReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierAgingReport extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public ?int $supplier_id = null;

    public ?string $as_on_date = null;

    public int $perPage = 20;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.reports.aging');
        $this->as_on_date = now()->toDateString();
    }

    public function updatedSupplierId(): void
    {
        $this->resetPage();
    }

    public function updatedAsOnDate(): void
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
        $this->as_on_date = now()->toDateString();
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
        $this->authorizePermission('supplier.reports.aging');

        $service = app(SupplierReportService::class);
        $filters = $this->filters();

        $rows = $service->supplierAgingQuery($filters)
            ->orderByDesc('total_due')
            ->orderBy('suppliers.name')
            ->paginate($this->perPage);

        $summary = $service->supplierAgingSummary($filters);

        return view('livewire.admin.supplier.reports.supplier-aging-report', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'code']),
            'rows' => $rows,
            'summary' => $summary,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array{supplier_id:?int,as_on_date:?string}
     */
    protected function filters(): array
    {
        return [
            'supplier_id' => $this->supplier_id,
            'as_on_date' => $this->as_on_date,
        ];
    }
}
