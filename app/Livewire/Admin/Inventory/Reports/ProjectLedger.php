<?php

namespace App\Livewire\Admin\Inventory\Reports;

use Illuminate\Database\Eloquent\Builder;

class ProjectLedger extends BaseLedgerReport
{
    protected string $pageTitle = 'Project Ledger';

    protected string $pageDescription = 'Project-linked stock movement ledger.';

    protected function applyReportSpecificFilters(Builder $query): Builder
    {
        return $query->whereNotNull('project_id');
    }

    protected function viewPath(): string
    {
        return 'livewire.admin.inventory.reports.project-ledger';
    }
}
