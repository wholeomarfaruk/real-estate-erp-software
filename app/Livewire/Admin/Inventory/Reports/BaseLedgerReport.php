<?php

namespace App\Livewire\Admin\Inventory\Reports;

use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\Project;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

abstract class BaseLedgerReport extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?int $product_id = null;

    public ?int $store_id = null;

    public ?int $project_id = null;

    public ?int $supplier_id = null;

    public string $movement_type = '';

    public string $search = '';

    public string $sort_direction = 'desc';

    public int $perPage = 15;

    protected string $paginationTheme = 'tailwind';

    protected string $permission = 'inventory.stock.ledger.view';

    protected string $pageTitle = 'Stock Ledger';

    protected string $pageDescription = 'Unified stock movement ledger';

    public function mount(): void
    {
        $this->authorizePermission($this->permission);
    }

    public function updated(string $name): void
    {
        if (in_array($name, [
            'date_from',
            'date_to',
            'product_id',
            'store_id',
            'project_id',
            'supplier_id',
            'movement_type',
            'search',
            'sort_direction',
            'perPage',
        ], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->date_from = null;
        $this->date_to = null;
        $this->product_id = null;
        $this->store_id = null;
        $this->project_id = null;
        $this->supplier_id = null;
        $this->movement_type = '';
        $this->search = '';
        $this->sort_direction = 'desc';

        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorizePermission($this->permission);

        $query = $this->baseQuery();

        $summary = (clone $query)->selectRaw(
            'COALESCE(SUM(CASE WHEN direction = ? THEN quantity ELSE 0 END), 0) AS total_in_qty,
             COALESCE(SUM(CASE WHEN direction = ? THEN quantity ELSE 0 END), 0) AS total_out_qty,
             COALESCE(SUM(total_price), 0) AS total_value',
            [StockMovementDirection::IN->value, StockMovementDirection::OUT->value]
        )->first();

        $movements = (clone $query)
            ->orderBy('movement_date', $this->normalizedSortDirection())
            ->orderBy('id', $this->normalizedSortDirection())
            ->paginate($this->perPage);

        $storesQuery = Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view($this->viewPath(), [
            'pageTitle' => $this->pageTitle,
            'pageDescription' => $this->pageDescription,
            'movements' => $movements,
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']),
            'stores' => $storesQuery->get(['id', 'name', 'code']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'movementTypes' => collect(StockMovementType::cases())
                ->map(fn (StockMovementType $type): array => ['value' => $type->value, 'label' => $type->label()])
                ->all(),
            'totalInQty' => (float) ($summary->total_in_qty ?? 0),
            'totalOutQty' => (float) ($summary->total_out_qty ?? 0),
            'totalValue' => (float) ($summary->total_value ?? 0),
        ])->layout('layouts.admin.admin');
    }

    protected function baseQuery(): Builder
    {
        $query = StockMovement::query()
            ->with([
                'product:id,name,sku',
                'store:id,name,code,type,project_id',
                'project:id,name',
                'supplier:id,name',
            ])
            ->dateBetween($this->date_from, $this->date_to)
            ->forProduct($this->product_id)
            ->forStore($this->store_id)
            ->forProject($this->project_id)
            ->forSupplier($this->supplier_id)
            ->forMovementType($this->movement_type !== '' ? $this->movement_type : null)
            ->when($this->search !== '', function (Builder $builder): void {
                $builder->where(function (Builder $subQuery): void {
                    $subQuery->where('reference_no', 'like', '%'.$this->search.'%')
                        ->orWhere('remarks', 'like', '%'.$this->search.'%')
                        ->orWhereHas('product', function (Builder $productQuery): void {
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

    protected function normalizedSortDirection(): string
    {
        return in_array($this->sort_direction, ['asc', 'desc'], true) ? $this->sort_direction : 'desc';
    }

    abstract protected function viewPath(): string;
}
