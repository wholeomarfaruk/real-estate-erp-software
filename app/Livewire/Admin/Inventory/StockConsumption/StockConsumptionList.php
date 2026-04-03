<?php

namespace App\Livewire\Admin\Inventory\StockConsumption;

use App\Enums\Inventory\StockConsumptionStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\StockConsumption;
use App\Services\Inventory\StockConsumptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockConsumptionList extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $storeFilter = null;

    public ?int $projectFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.stock.consumption.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStoreFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProjectFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function postConsumption(int $consumptionId): void
    {
        $this->authorizePermission('inventory.stock.consumption.post');

        $consumption = StockConsumption::query()->find($consumptionId);

        if (! $consumption) {
            return;
        }

        $this->ensureStoreAccessible((int) $consumption->store_id);

        try {
            app(StockConsumptionService::class)->postConsumption($consumption, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock consumption posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function deleteConsumption(int $consumptionId): void
    {
        $this->authorizePermission('inventory.stock.consumption.delete');

        $consumption = StockConsumption::query()->find($consumptionId);

        if (! $consumption) {
            return;
        }

        $this->ensureStoreAccessible((int) $consumption->store_id);

        if ($consumption->status !== StockConsumptionStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft consumption can be deleted.']);

            return;
        }

        DB::transaction(function () use ($consumption): void {
            $consumption->items()->delete();
            $consumption->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock consumption deleted successfully.']);
    }

    public function render(): View
    {
        $query = StockConsumption::query()
            ->with(['store:id,name,code,type,project_id', 'project:id,name,code', 'creator:id,name', 'poster:id,name'])
            ->when($this->search !== '', function (Builder $builder): void {
                $builder->where('consumption_no', 'like', '%'.$this->search.'%');
            })
            ->when($this->statusFilter !== '', fn (Builder $builder): Builder => $builder->where('status', $this->statusFilter))
            ->when($this->storeFilter, fn (Builder $builder): Builder => $builder->where('store_id', $this->storeFilter))
            ->when($this->projectFilter, fn (Builder $builder): Builder => $builder->where('project_id', $this->projectFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('consumption_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('consumption_date', '<=', $this->dateTo));

        $this->applyStoreRestriction($query);

        $consumptions = $query->latest('consumption_date')->latest('id')->paginate(15);

        $storesQuery = \App\Models\Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.stock-consumption.stock-consumption-list', [
            'consumptions' => $consumptions,
            'statuses' => StockConsumptionStatus::cases(),
            'stores' => $storesQuery->get(['id', 'name', 'code']),
            'projects' => \App\Models\Project::query()->orderBy('name')->get(['id', 'name', 'code']),
        ])->layout('layouts.admin.admin');
    }
}
