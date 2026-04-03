<?php

namespace App\Livewire\Admin\Inventory\Reports;

use App\Enums\Inventory\StoreType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\Project;
use App\Models\StockBalance;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

abstract class BaseBalanceReport extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public ?int $product_id = null;

    public ?int $store_id = null;

    public ?int $project_id = null;

    public string $type_filter = '';

    public string $search = '';

    public int $perPage = 15;

    protected string $paginationTheme = 'tailwind';

    protected string $permission = 'inventory.stock.report.view';

    protected string $pageTitle = 'Stock Summary';

    protected string $pageDescription = 'Current stock summary from stock balances';

    public function mount(): void
    {
        $this->authorizePermission($this->permission);
    }

    public function updated(string $name): void
    {
        if (in_array($name, [
            'product_id',
            'store_id',
            'project_id',
            'type_filter',
            'search',
            'perPage',
        ], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->product_id = null;
        $this->store_id = null;
        $this->project_id = null;
        $this->type_filter = '';
        $this->search = '';

        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizePermission($this->permission);

        $query = $this->baseQuery();

        $summary = (clone $query)->selectRaw(
            'COALESCE(SUM(quantity), 0) AS total_qty,
             COALESCE(SUM(total_value), 0) AS total_value,
             COUNT(*) AS total_rows'
        )->first();

        $balances = (clone $query)
            ->orderByDesc('quantity')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $storesQuery = Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view($this->viewPath(), [
            'pageTitle' => $this->pageTitle,
            'pageDescription' => $this->pageDescription,
            'balances' => $balances,
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']),
            'stores' => $storesQuery->get(['id', 'name', 'code', 'type', 'project_id']),
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
            ->with([
                'product:id,name,sku,minimum_stock_level',
                'store:id,name,code,type,project_id',
                'store.project:id,name',
            ])
            ->when($this->product_id, fn (Builder $builder): Builder => $builder->where('product_id', $this->product_id))
            ->when($this->store_id, fn (Builder $builder): Builder => $builder->where('store_id', $this->store_id))
            ->when($this->project_id, function (Builder $builder): void {
                $builder->whereHas('store', function (Builder $storeQuery): void {
                    $storeQuery->where('project_id', $this->project_id);
                });
            })
            ->when($this->type_filter !== '', function (Builder $builder): void {
                $builder->whereHas('store', function (Builder $storeQuery): void {
                    $storeQuery->where('type', $this->type_filter);
                });
            })
            ->when($this->search !== '', function (Builder $builder): void {
                $builder->where(function (Builder $subQuery): void {
                    $subQuery->whereHas('product', function (Builder $productQuery): void {
                        $productQuery->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('sku', 'like', '%'.$this->search.'%');
                    })
                        ->orWhereHas('store', function (Builder $storeQuery): void {
                            $storeQuery->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('code', 'like', '%'.$this->search.'%');
                        });
                });
            });

        $this->applyStoreRestriction($query, 'store_id');

        return $this->applyReportSpecificFilters($query);
    }

    protected function applyReportSpecificFilters(Builder $query): Builder
    {
        return $query;
    }

    abstract protected function viewPath(): string;
}
