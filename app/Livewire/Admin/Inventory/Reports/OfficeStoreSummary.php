<?php

namespace App\Livewire\Admin\Inventory\Reports;

use App\Enums\Inventory\StoreType;
use Illuminate\Database\Eloquent\Builder;

class OfficeStoreSummary extends BaseBalanceReport
{
    protected string $pageTitle = 'Office Store Summary';

    protected string $pageDescription = 'Stock summary limited to office stores.';

    protected function applyReportSpecificFilters(Builder $query): Builder
    {
        return $query->whereHas('store', function (Builder $storeQuery): void {
            $storeQuery->where('type', StoreType::OFFICE->value);
        });
    }

    protected function viewPath(): string
    {
        return 'livewire.admin.inventory.reports.office-store-summary';
    }
}
