<?php

namespace App\Livewire\Admin\Inventory\Reports;

use Illuminate\Database\Eloquent\Builder;

class OutOfStockReport extends BaseBalanceReport
{
    protected string $pageTitle = 'Out Of Stock Report';

    protected string $pageDescription = 'Items with zero or negative quantity.';

    protected function applyReportSpecificFilters(Builder $query): Builder
    {
        return $query->where('quantity', '<=', 0);
    }

    protected function viewPath(): string
    {
        return 'livewire.admin.inventory.reports.out-of-stock-report';
    }
}
