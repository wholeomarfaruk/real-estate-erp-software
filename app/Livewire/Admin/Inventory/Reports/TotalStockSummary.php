<?php

namespace App\Livewire\Admin\Inventory\Reports;

class TotalStockSummary extends BaseBalanceReport
{
    protected string $pageTitle = 'Total Stock Summary';

    protected string $pageDescription = 'Current stock summary across all accessible stores.';

    protected function viewPath(): string
    {
        return 'livewire.admin.inventory.reports.total-stock-summary';
    }
}
