<?php

namespace App\Livewire\Admin\Inventory\Reports;

use Illuminate\Database\Eloquent\Builder;

class LowStockReport extends BaseBalanceReport
{
    protected string $pageTitle = 'Low Stock Report';

    protected string $pageDescription = 'Items where quantity is at or below minimum stock level.';

    protected function applyReportSpecificFilters(Builder $query): Builder
    {
        return $query->whereRaw(
            'stock_balances.quantity <= COALESCE((SELECT products.minimum_stock_level FROM products WHERE products.id = stock_balances.product_id), 0)'
        );
    }

    protected function viewPath(): string
    {
        return 'livewire.admin.inventory.reports.low-stock-report';
    }
}
