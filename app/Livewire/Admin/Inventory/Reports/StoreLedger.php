<?php

namespace App\Livewire\Admin\Inventory\Reports;

class StoreLedger extends BaseLedgerReport
{
    protected string $pageTitle = 'Store Ledger';

    protected string $pageDescription = 'Store-wise stock movement ledger from stock movements.';

    protected function viewPath(): string
    {
        return 'livewire.admin.inventory.reports.store-ledger';
    }
}
