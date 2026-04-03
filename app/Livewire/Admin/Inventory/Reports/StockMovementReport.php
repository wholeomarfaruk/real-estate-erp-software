<?php

namespace App\Livewire\Admin\Inventory\Reports;

class StockMovementReport extends BaseLedgerReport
{
    protected string $pageTitle = 'Stock Movement Report';

    protected string $pageDescription = 'Date-wise stock movement report from unified ledger.';

    protected function viewPath(): string
    {
        return 'livewire.admin.inventory.reports.stock-movement-report';
    }
}
