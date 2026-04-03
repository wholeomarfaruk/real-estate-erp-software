<?php

namespace App\Livewire\Admin\Inventory\Reports;

class ProductLedger extends BaseLedgerReport
{
    protected string $pageTitle = 'Product Ledger';

    protected string $pageDescription = 'Product-wise stock movement ledger from stock movements.';

    protected function viewPath(): string
    {
        return 'livewire.admin.inventory.reports.product-ledger';
    }
}
