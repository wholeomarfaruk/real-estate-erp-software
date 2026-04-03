<?php

namespace App\Livewire\Admin\Inventory\Reports;

use App\Enums\Inventory\StoreType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\StockBalance;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ProductStockSummary extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public ?int $product_id = null;

    public string $type_filter = '';

    public string $search = '';

    public int $perPage = 15;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.stock.report.view');
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['product_id', 'type_filter', 'search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->product_id = null;
        $this->type_filter = '';
        $this->search = '';

        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.stock.report.view');

        $query = $this->baseQuery();

        $rows = (clone $query)
            ->orderBy('product_name')
            ->paginate($this->perPage);

        $summary = DB::query()
            ->fromSub($query, 'product_summary')
            ->selectRaw('COALESCE(SUM(quantity), 0) AS total_qty, COALESCE(SUM(total_value), 0) AS total_value, COUNT(*) AS total_rows')
            ->first();

        return view('livewire.admin.inventory.reports.product-stock-summary', [
            'pageTitle' => 'Product Stock Summary',
            'pageDescription' => 'Aggregated quantity and value by product across stores.',
            'rows' => $rows,
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']),
            'storeTypes' => [
                ['value' => StoreType::OFFICE->value, 'label' => StoreType::OFFICE->label()],
                ['value' => StoreType::PROJECT->value, 'label' => StoreType::PROJECT->label()],
            ],
            'totalQty' => (float) ($summary->total_qty ?? 0),
            'totalValue' => (float) ($summary->total_value ?? 0),
            'totalRows' => (int) ($summary->total_rows ?? 0),
        ])->layout('layouts.admin.admin');
    }

    protected function baseQuery(): Builder
    {
        $query = StockBalance::query()
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->join('stores', 'stores.id', '=', 'stock_balances.store_id')
            ->when($this->product_id, fn ($builder) => $builder->where('stock_balances.product_id', $this->product_id))
            ->when($this->type_filter !== '', fn ($builder) => $builder->where('stores.type', $this->type_filter))
            ->when($this->search !== '', function ($builder): void {
                $builder->where(function ($subQuery): void {
                    $subQuery->where('products.name', 'like', '%'.$this->search.'%')
                        ->orWhere('products.sku', 'like', '%'.$this->search.'%');
                });
            });

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $query->whereIn('stock_balances.store_id', $storeIds === [] ? [0] : $storeIds);
        }

        return $query->selectRaw(
            'stock_balances.product_id,
             products.name AS product_name,
             products.sku AS product_sku,
             COALESCE(SUM(stock_balances.quantity), 0) AS quantity,
             CASE
                 WHEN SUM(stock_balances.quantity) > 0
                     THEN ROUND(SUM(stock_balances.total_value) / SUM(stock_balances.quantity), 2)
                 ELSE 0
             END AS avg_unit_price,
             COALESCE(SUM(stock_balances.total_value), 0) AS total_value'
        )->groupBy('stock_balances.product_id', 'products.name', 'products.sku');
    }
}
