<?php

namespace App\Livewire\Admin\Inventory\Reports;

use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use Illuminate\Database\Eloquent\Builder;

class SupplierPurchaseHistory extends BaseLedgerReport
{
    protected string $pageTitle = 'Supplier Purchase History';

    protected string $pageDescription = 'Supplier-wise purchase and receive movement history.';

    protected function applyReportSpecificFilters(Builder $query): Builder
    {
        return $query
            ->whereNotNull('supplier_id')
            ->where('direction', StockMovementDirection::IN->value)
            ->whereIn('movement_type', [
                StockMovementType::PURCHASE->value,
                StockMovementType::RECEIVE->value,
            ]);
    }

    protected function viewPath(): string
    {
        return 'livewire.admin.inventory.reports.supplier-purchase-history';
    }
}
