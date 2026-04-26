<?php

namespace App\Services\Accounts;

use App\Enums\Supplier\SupplierBillStatus;
use App\Models\Product;
use App\Models\SupplierBillItem;
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

        return SupplierBillItem::query()
            ->from('supplier_bill_items as sbi')
            ->join('supplier_bills as sb', 'sb.id', '=', 'sbi.supplier_bill_id')
            ->join('products as p', 'p.id', '=', 'sbi.product_id')
            ->whereNotNull('sbi.product_id')
            ->whereIn('sb.status', $this->postedBillStatuses())
            ->when($productId, fn (Builder $builder): Builder => $builder->where('sbi.product_id', $productId))
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('sb.bill_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('sb.bill_date', '<=', $toDate))
            ->selectRaw('sbi.product_id')
            ->selectRaw('MAX(p.name) as product_name')
            ->selectRaw('MAX(p.sku) as product_sku')
            ->selectRaw('COALESCE(SUM(sbi.qty), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(sbi.line_total), 0) as total_cost')
            ->selectRaw('CASE WHEN SUM(sbi.qty) > 0 THEN SUM(sbi.line_total) / SUM(sbi.qty) ELSE 0 END as average_cost')
            ->groupBy('sbi.product_id');
    }

    /**
     * @param  array{from_date?:string|null,to_date?:string|null}  $filters
     */
    public function detailsQuery(int $productId, array $filters = []): Builder
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        return SupplierBillItem::query()
            ->from('supplier_bill_items as sbi')
            ->join('supplier_bills as sb', 'sb.id', '=', 'sbi.supplier_bill_id')
            ->leftJoin('purchase_orders as po', 'po.id', '=', 'sb.purchase_order_id')
            ->leftJoin('stock_receives as sr', 'sr.id', '=', 'sb.stock_receive_id')
            ->where('sbi.product_id', $productId)
            ->whereIn('sb.status', $this->postedBillStatuses())
            ->when($fromDate, fn (Builder $builder): Builder => $builder->whereDate('sb.bill_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $builder): Builder => $builder->whereDate('sb.bill_date', '<=', $toDate))
            ->selectRaw('sb.bill_date as entry_date')
            ->selectRaw(
                "CASE
                    WHEN sb.reference_type = 'linked_purchase_order' THEN 'PO'
                    WHEN sb.reference_type = 'linked_stock_receive' THEN 'Stock Receive'
                    ELSE 'Bill'
                END as reference_type_label"
            )
            ->selectRaw(
                "CASE
                    WHEN sb.reference_type = 'linked_purchase_order' THEN COALESCE(po.po_no, CONCAT('PO#', sb.purchase_order_id))
                    WHEN sb.reference_type = 'linked_stock_receive' THEN COALESCE(sr.receive_no, CONCAT('SR#', sb.stock_receive_id))
                    ELSE COALESCE(sb.bill_no, CONCAT('BILL#', sb.id))
                END as reference_no"
            )
            ->selectRaw('sbi.qty as quantity')
            ->selectRaw('sbi.rate as rate')
            ->selectRaw('sbi.line_total as amount')
            ->orderByDesc('sb.bill_date')
            ->orderByDesc('sbi.id');
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

    /**
     * @return array<int, string>
     */
    protected function postedBillStatuses(): array
    {
        return [
            SupplierBillStatus::OPEN->value,
            SupplierBillStatus::PARTIAL->value,
            SupplierBillStatus::PAID->value,
            SupplierBillStatus::OVERDUE->value,
        ];
    }
}
