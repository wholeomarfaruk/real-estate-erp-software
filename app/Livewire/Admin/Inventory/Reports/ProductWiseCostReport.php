<?php

namespace App\Livewire\Admin\Inventory\Reports;

use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Services\Inventory\ProductWiseCostReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductWiseCostReport extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public ?string $from_date = null;

    public ?string $to_date = null;

    public ?int $product_id = null;

    public bool $showDetailsModal = false;

    public ?int $selectedProductId = null;

    public string $selectedProductName = '';

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.report.view');
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['from_date', 'to_date', 'product_id']);
        $this->resetPage();
    }

    public function loadDetails(int $productId): void
    {
        $this->selectedProductId = $productId;
        $this->selectedProductName = Product::query()->whereKey($productId)->value('name') ?? 'Product';
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal(): void
    {
        $this->showDetailsModal = false;
        $this->selectedProductId = null;
        $this->selectedProductName = '';
    }

    public function exportCsv(ProductWiseCostReportService $service): StreamedResponse
    {
        $this->authorizePermission('inventory.report.view');

        $summaryQuery = $service->summaryQuery($this->filters());
        $this->applyStoreRestriction($summaryQuery, 'sr.store_id');

        $rows = $summaryQuery
            ->orderBy('product_name')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Product', 'Qty', 'Total Cost', 'Avg Cost']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->product_name,
                    number_format((float) $row->total_quantity, 3, '.', ''),
                    number_format((float) $row->total_cost, 2, '.', ''),
                    number_format((float) $row->average_cost, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'inventory-product-wise-cost.csv');
    }

    public function render(ProductWiseCostReportService $service): View
    {
        $this->authorizePermission('inventory.report.view');

        $summaryQuery = $service->summaryQuery($this->filters());
        $this->applyStoreRestriction($summaryQuery, 'sr.store_id');

        $summaryRows = $summaryQuery
            ->orderBy('product_name')
            ->paginate(20);

        $detailRows = collect();

        if ($this->showDetailsModal && $this->selectedProductId) {
            $detailQuery = $service->detailsQuery($this->selectedProductId, $this->filters());
            $this->applyStoreRestriction($detailQuery, 'sr.store_id');
            $detailRows = $detailQuery->get();
        }

        return view('livewire.admin.inventory.reports.product-wise-cost-report', [
            'companyName' => config('app.name'),
            'products' => $service->products(),
            'rows' => $summaryRows,
            'detailRows' => $detailRows,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return array{from_date:?string,to_date:?string,product_id:?int}
     */
    protected function filters(): array
    {
        return [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'product_id' => $this->product_id,
        ];
    }
}
