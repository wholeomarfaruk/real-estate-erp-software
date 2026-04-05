<?php

namespace App\Livewire\Admin\Supplier\Reports;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Supplier\SupplierReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ProductWiseSupplierReport extends Component
{
    use InteractsWithSupplierAccess;
    use WithPagination;

    public ?int $product_id = null;

    public ?int $supplier_id = null;

    public ?string $from_date = null;

    public ?string $to_date = null;

    public int $perPage = 20;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('supplier.reports.product-wise');
    }

    public function updatedProductId(): void
    {
        $this->resetPage();
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

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->product_id = null;
        $this->supplier_id = null;
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
        $this->authorizePermission('supplier.reports.product-wise');

        $service = app(SupplierReportService::class);
        $filters = $this->filters();

        $rows = $service->productWiseSupplierQuery($filters)
            ->orderBy('product_name')
            ->orderBy('supplier_name')
            ->paginate($this->perPage);

        $summary = $service->productWiseSupplierSummary($filters);

        return view('livewire.admin.supplier.reports.product-wise-supplier-report', [
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name', 'code']),
            'rows' => $rows,
            'summary' => $summary,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array{product_id:?int,supplier_id:?int,from_date:?string,to_date:?string}
     */
    protected function filters(): array
    {
        return [
            'product_id' => $this->product_id,
            'supplier_id' => $this->supplier_id,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
        ];
    }
}
