<?php

namespace App\Livewire\Admin\Inventory\Reports;

use App\Enums\Inventory\StoreType;
use Illuminate\Database\Eloquent\Builder;

class ProjectStoreSummary extends BaseBalanceReport
{
    protected string $pageTitle = 'Project Store Summary';

    protected string $pageDescription = 'Stock summary limited to project stores.';

    protected function applyReportSpecificFilters(Builder $query): Builder
    {
        return $query->whereHas('store', function (Builder $storeQuery): void {
            $storeQuery->where('type', StoreType::PROJECT->value);
        });
    }

    protected function viewPath(): string
    {
        return 'livewire.admin.inventory.reports.project-store-summary';
    }
}
