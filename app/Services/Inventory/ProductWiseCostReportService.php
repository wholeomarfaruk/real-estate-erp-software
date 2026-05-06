<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\StockReceiveStatus;
use App\Models\Product;
use App\Models\StockReceiveItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductWiseCostReportService
{
    /**
     * @param  array{from_date?:string|null,to_date?:string|null,product_id?:int|null}  $filters
     */
    public function summaryQuery(array $filters = []): Builder
    {
        $productId = $filters['product_id'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        return StockReceiveItem::query()
            ->from('stock_receive_items as sri')
            ->join('stock_receives as sr', 'sr.id', '=', 'sri.stock_receive_id')
            ->join('products as p', 'p.id', '=', 'sri.product_id')
            ->where('sr.status', StockReceiveStatus::POSTED->value)
            ->when($productId, fn (Builder $builder): Builder => $builder->where('sri.product_id', $productId))
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('sr.receive_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('sr.receive_date', '<=', $toDate))
            ->selectRaw('sri.product_id')
            ->selectRaw('MAX(p.name) as product_name')
            ->selectRaw('MAX(p.sku) as product_sku')
            ->selectRaw('COALESCE(SUM(sri.quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(sri.total_price), 0) as total_cost')
            ->selectRaw('CASE WHEN SUM(sri.quantity) > 0 THEN SUM(sri.total_price) / SUM(sri.quantity) ELSE 0 END as average_cost')
            ->groupBy('sri.product_id');
    }

    /**
     * @param  array{from_date?:string|null,to_date?:string|null}  $filters
     */
    public function detailsQuery(int $productId, array $filters = []): Builder
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        return StockReceiveItem::query()
            ->from('stock_receive_items as sri')
            ->join('stock_receives as sr', 'sr.id', '=', 'sri.stock_receive_id')
            ->where('sr.status', StockReceiveStatus::POSTED->value)
            ->where('sri.product_id', $productId)
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('sr.receive_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('sr.receive_date', '<=', $toDate))
            ->selectRaw('sr.receive_date as entry_date')
            ->selectRaw("'Stock Receive' as reference_type_label")
            ->selectRaw("COALESCE(sr.receive_no, CONCAT('SR#', sr.id)) as reference_no")
            ->selectRaw('sri.quantity as quantity')
            ->selectRaw('sri.unit_price as rate')
            ->selectRaw('sri.total_price as amount')
            ->orderByDesc('sr.receive_date')
            ->orderByDesc('sri.id');
    }

    /**
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
    }
}
