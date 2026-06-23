<?php

namespace App\Services\Reports\Inventory;

use App\Models\Product;
use App\Models\StockBalance;
use Illuminate\Support\Collection;

/**
 * Stocks Report.
 *
 * One row per product/store stock balance: product, sku, store, type, quantity,
 * average unit price and total value. Built into the standard report payload
 * shape (title / slug / columns / rows / summary / meta).
 */
class StocksReportService
{
    public function build(array $filters): array
    {
        // Aggregate stock across all stores — one row per product.
        $query = StockBalance::query()
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.category_id');

        $productId = $filters['product_id'] ?? null;
        if ($productId) {
            $query->where('stock_balances.product_id', $productId);
        }

        $balances = $query
            ->groupBy('stock_balances.product_id', 'products.name', 'products.unit', 'product_categories.name')
            ->orderBy('products.name')
            ->selectRaw(
                'stock_balances.product_id AS product_id,
                 products.name AS product_name,
                 products.unit AS product_unit,
                 product_categories.name AS category_name,
                 COALESCE(SUM(stock_balances.quantity), 0) AS quantity,
                 COALESCE(SUM(stock_balances.total_value), 0) AS total_value,
                 CASE
                     WHEN SUM(stock_balances.quantity) > 0
                         THEN ROUND(SUM(stock_balances.total_value) / SUM(stock_balances.quantity), 2)
                     ELSE 0
                 END AS avg_unit_price'
            )
            ->get();

        $rows = $balances->values()->map(function ($b, int $i): array {
            return [
                'sl_no'         => $i + 1,
                'product_id'    => $b->product_id,
                'product'       => $b->product_name ?? '—',
                'category'      => $b->category_name ?? '—',
                'unit'          => $b->product_unit ?? '—',
                'quantity'      => (float) $b->quantity,
                'avg_unit_price'=> (float) $b->avg_unit_price,
                'total_value'   => (float) $b->total_value,
            ];
        })->all();

        $summary = [
            'total_rows'     => count($rows),
            'total_quantity' => collect($rows)->sum('quantity'),
            'total_value'    => collect($rows)->sum('total_value'),
        ];

        $meta = [
            'company_name' => config('app.name'),
            'report_title' => 'Stocks Report',
            'report_slug'  => 'stocks-report',
            'generated_at' => now()->format('d-M-Y H:i A'),
            'generated_by' => auth()->user()?->name ?? 'System',
            'from_date'    => '-',
            'to_date'      => '-',
            'file_name'    => 'stocks-report-' . now()->format('Y-m-d-His'),
            'notes'        => $filters['notes'] ?? '',
        ];

        return [
            'title'   => 'Stocks Report',
            'slug'    => 'stocks-report',
            'columns' => $this->columns(),
            'rows'    => $rows,
            'summary' => $summary,
            'meta'    => $meta,
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'sl_no',          'label' => 'Sl No',          'align' => 'center', 'type' => 'text'],
            ['key' => 'product_id',     'label' => 'Product ID',     'align' => 'center', 'type' => 'text'],
            ['key' => 'product',        'label' => 'Product',        'align' => 'left',   'type' => 'text'],
            ['key' => 'category',       'label' => 'Category',       'align' => 'left',   'type' => 'text'],
            ['key' => 'unit',           'label' => 'Unit',           'align' => 'center', 'type' => 'text'],
            ['key' => 'quantity',       'label' => 'Total Stock',    'align' => 'right',  'type' => 'number'],
            ['key' => 'avg_unit_price', 'label' => 'Avg Unit Price', 'align' => 'right',  'type' => 'money'],
            ['key' => 'total_value',    'label' => 'Total Value',    'align' => 'right',  'type' => 'money'],
        ];
    }

    public function getProducts(): Collection
    {
        return Product::orderBy('name')->get(['id', 'name', 'sku']);
    }
}
