<?php

namespace App\Livewire\Admin\Inventory\Dashboard;

use App\Enums\Inventory\StockMovementType;
use App\Enums\Inventory\StoreType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockConsumption;
use App\Models\StockMovement;
use App\Models\StockReceive;
use App\Models\Store;
use App\Models\TransferTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class InventoryDashboard extends Component
{
    use InteractsWithInventoryAccess;

    public int $recentLimit = 6;

    public int $summaryLimit = 8;

    public int $lowStockLimit = 8;

    public int $topConsumedLimit = 8;

    public function mount(): void
    {
        $this->authorizePermission('inventory.dashboard.view');
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.dashboard.view');

        $storeIds = $this->scopedStoreIds();

        $kpis = $this->kpiData($storeIds);

        return view('livewire.admin.inventory.dashboard.inventory-dashboard', [
            ...$kpis,
            'recentReceives' => $this->recentReceives($storeIds),
            'recentTransfers' => $this->recentTransfers($storeIds),
            'recentConsumptions' => $this->recentConsumptions($storeIds),
            'lowStockItems' => $this->lowStockItems($storeIds),
            'topConsumedProducts' => $this->topConsumedProducts($storeIds),
            'officeStoreSummaries' => $this->officeStoreSummaries($storeIds),
            'projectStoreSummaries' => $this->projectStoreSummaries($storeIds),
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return int[]|null
     */
    private function scopedStoreIds(): ?array
    {
        if ($this->canViewAllStores()) {
            return null;
        }

        $storeIds = $this->getAccessibleStoreIds();

        return $storeIds === [] ? [0] : $storeIds;
    }

    /**
     * @param  int[]|null  $storeIds
     * @return array<string, float|int>
     */
    private function kpiData(?array $storeIds): array
    {
        $storesQuery = Store::query();
        if ($storeIds !== null) {
            $storesQuery->whereIn('id', $storeIds);
        }

        $balanceQuery = StockBalance::query();
        if ($storeIds !== null) {
            $balanceQuery->whereIn('store_id', $storeIds);
        }

        $totals = (clone $balanceQuery)->selectRaw(
            'COALESCE(SUM(quantity), 0) AS total_qty, COALESCE(SUM(total_value), 0) AS total_value'
        )->first();

        $lowStockCount = (clone $balanceQuery)
            ->where('quantity', '>', 0)
            ->whereRaw(
                'stock_balances.quantity <= COALESCE((SELECT products.minimum_stock_level FROM products WHERE products.id = stock_balances.product_id), 0)'
            )
            ->count();

        $outOfStockCount = (clone $balanceQuery)
            ->where('quantity', '<=', 0)
            ->count();

        return [
            'totalProducts' => Product::query()->count(),
            'totalStores' => (clone $storesQuery)->count(),
            'totalOfficeStores' => (clone $storesQuery)->where('type', StoreType::OFFICE->value)->count(),
            'totalProjectStores' => (clone $storesQuery)->where('type', StoreType::PROJECT->value)->count(),
            'totalStockQty' => (float) ($totals->total_qty ?? 0),
            'totalStockValue' => (float) ($totals->total_value ?? 0),
            'lowStockItemsCount' => $lowStockCount,
            'outOfStockItemsCount' => $outOfStockCount,
        ];
    }

    /**
     * @param  int[]|null  $storeIds
     */
    private function recentReceives(?array $storeIds)
    {
        return StockReceive::query()
            ->with([
                'supplier:id,name',
                'store:id,name,code',
            ])
            ->withSum('items as total_amount', 'total_price')
            ->posted()
            ->when($storeIds !== null, fn (Builder $query): Builder => $query->whereIn('store_id', $storeIds))
            ->latest('receive_date')
            ->latest('id')
            ->limit($this->recentLimit)
            ->get();
    }

    /**
     * @param  int[]|null  $storeIds
     */
    private function recentTransfers(?array $storeIds)
    {
        return TransferTransaction::query()
            ->with([
                'senderStore:id,name,code,type',
                'receiverStore:id,name,code,type',
            ])
            ->withSum('items as total_amount', 'total_price')
            ->when($storeIds !== null, function (Builder $query) use ($storeIds): void {
                $query->where(function (Builder $subQuery) use ($storeIds): void {
                    $subQuery->whereIn('sender_store_id', $storeIds)
                        ->orWhereIn('receiver_store_id', $storeIds);
                });
            })
            ->latest('transfer_date')
            ->latest('id')
            ->limit($this->recentLimit)
            ->get();
    }

    /**
     * @param  int[]|null  $storeIds
     */
    private function recentConsumptions(?array $storeIds)
    {
        return StockConsumption::query()
            ->with([
                'store:id,name,code,type,project_id',
                'project:id,name',
            ])
            ->withSum('items as total_amount', 'total_price')
            ->when($storeIds !== null, fn (Builder $query): Builder => $query->whereIn('store_id', $storeIds))
            ->latest('consumption_date')
            ->latest('id')
            ->limit($this->recentLimit)
            ->get();
    }

    /**
     * @param  int[]|null  $storeIds
     */
    private function lowStockItems(?array $storeIds)
    {
        return StockBalance::query()
            ->with([
                'product:id,name,sku,minimum_stock_level',
                'store:id,name,code,type',
            ])
            ->where('quantity', '>', 0)
            ->whereRaw(
                'stock_balances.quantity <= COALESCE((SELECT products.minimum_stock_level FROM products WHERE products.id = stock_balances.product_id), 0)'
            )
            ->when($storeIds !== null, fn (Builder $query): Builder => $query->whereIn('store_id', $storeIds))
            ->orderBy('quantity')
            ->orderBy('id')
            ->limit($this->lowStockLimit)
            ->get();
    }

    /**
     * @param  int[]|null  $storeIds
     */
    private function topConsumedProducts(?array $storeIds)
    {
        return StockMovement::query()
            ->with('product:id,name,sku')
            ->selectRaw(
                'product_id, COALESCE(SUM(quantity), 0) AS consumed_qty, COALESCE(SUM(total_price), 0) AS consumed_value'
            )
            ->where('movement_type', StockMovementType::CONSUMPTION->value)
            ->when($storeIds !== null, fn (Builder $query): Builder => $query->whereIn('store_id', $storeIds))
            ->groupBy('product_id')
            ->orderByDesc('consumed_qty')
            ->limit($this->topConsumedLimit)
            ->get();
    }

    /**
     * @param  int[]|null  $storeIds
     */
    private function officeStoreSummaries(?array $storeIds)
    {
        return StockBalance::query()
            ->join('stores', 'stores.id', '=', 'stock_balances.store_id')
            ->selectRaw(
                'stores.id AS store_id, stores.name AS store_name, stores.code AS store_code,
                 COALESCE(SUM(stock_balances.quantity), 0) AS total_qty,
                 COALESCE(SUM(stock_balances.total_value), 0) AS total_value'
            )
            ->where('stores.type', StoreType::OFFICE->value)
            ->when($storeIds !== null, fn (Builder $query): Builder => $query->whereIn('stock_balances.store_id', $storeIds))
            ->groupBy('stores.id', 'stores.name', 'stores.code')
            ->orderByDesc('total_value')
            ->limit($this->summaryLimit)
            ->get();
    }

    /**
     * @param  int[]|null  $storeIds
     */
    private function projectStoreSummaries(?array $storeIds)
    {
        return StockBalance::query()
            ->join('stores', 'stores.id', '=', 'stock_balances.store_id')
            ->leftJoin('projects', 'projects.id', '=', 'stores.project_id')
            ->selectRaw(
                'stores.id AS store_id, stores.name AS store_name, stores.code AS store_code, projects.name AS project_name,
                 COALESCE(SUM(stock_balances.quantity), 0) AS total_qty,
                 COALESCE(SUM(stock_balances.total_value), 0) AS total_value'
            )
            ->where('stores.type', StoreType::PROJECT->value)
            ->when($storeIds !== null, fn (Builder $query): Builder => $query->whereIn('stock_balances.store_id', $storeIds))
            ->groupBy('stores.id', 'stores.name', 'stores.code', 'projects.name')
            ->orderByDesc('total_value')
            ->limit($this->summaryLimit)
            ->get();
    }
}
