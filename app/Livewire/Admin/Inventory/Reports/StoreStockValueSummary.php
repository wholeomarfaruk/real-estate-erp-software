<?php

namespace App\Livewire\Admin\Inventory\Reports;

use App\Enums\Inventory\StoreType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Project;
use App\Models\StockBalance;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StoreStockValueSummary extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public ?int $store_id = null;

    public ?int $project_id = null;

    public string $type_filter = '';

    public string $search = '';

    public int $perPage = 15;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.stock.report.view');
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['store_id', 'project_id', 'type_filter', 'search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->store_id = null;
        $this->project_id = null;
        $this->type_filter = '';
        $this->search = '';

        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.stock.report.view');

        $query = $this->baseQuery();

        $rows = (clone $query)->orderBy('store_name')->paginate($this->perPage);

        $summary = DB::query()
            ->fromSub($query, 'store_summary')
            ->selectRaw('COALESCE(SUM(quantity), 0) AS total_qty, COALESCE(SUM(total_value), 0) AS total_value, COUNT(*) AS total_rows')
            ->first();

        $storesQuery = Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.reports.store-stock-value-summary', [
            'pageTitle' => 'Store Stock Value Summary',
            'pageDescription' => 'Aggregated quantity and value by store.',
            'rows' => $rows,
            'stores' => $storesQuery->get(['id', 'name', 'code']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'storeTypes' => [
                ['value' => StoreType::OFFICE->value, 'label' => StoreType::OFFICE->label()],
                ['value' => StoreType::PROJECT->value, 'label' => StoreType::PROJECT->label()],
            ],
            'totalQty' => (float) ($summary->total_qty ?? 0),
            'totalValue' => (float) ($summary->total_value ?? 0),
            'totalRows' => (int) ($summary->total_rows ?? 0),
        ])->layout('layouts.admin.admin');
    }

    protected function baseQuery(): Builder
    {
        $query = StockBalance::query()
            ->join('stores', 'stores.id', '=', 'stock_balances.store_id')
            ->leftJoin('projects', 'projects.id', '=', 'stores.project_id')
            ->when($this->store_id, fn ($builder) => $builder->where('stock_balances.store_id', $this->store_id))
            ->when($this->project_id, fn ($builder) => $builder->where('stores.project_id', $this->project_id))
            ->when($this->type_filter !== '', fn ($builder) => $builder->where('stores.type', $this->type_filter))
            ->when($this->search !== '', function ($builder): void {
                $builder->where(function ($subQuery): void {
                    $subQuery->where('stores.name', 'like', '%'.$this->search.'%')
                        ->orWhere('stores.code', 'like', '%'.$this->search.'%')
                        ->orWhere('projects.name', 'like', '%'.$this->search.'%');
                });
            });

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $query->whereIn('stock_balances.store_id', $storeIds === [] ? [0] : $storeIds);
        }

        return $query->selectRaw(
            'stock_balances.store_id,
             stores.name AS store_name,
             stores.code AS store_code,
             stores.type AS store_type,
             projects.name AS project_name,
             COALESCE(SUM(stock_balances.quantity), 0) AS quantity,
             CASE
                 WHEN SUM(stock_balances.quantity) > 0
                     THEN ROUND(SUM(stock_balances.total_value) / SUM(stock_balances.quantity), 2)
                 ELSE 0
             END AS avg_unit_price,
             COALESCE(SUM(stock_balances.total_value), 0) AS total_value'
        )->groupBy('stock_balances.store_id', 'stores.name', 'stores.code', 'stores.type', 'projects.name');
    }
}
