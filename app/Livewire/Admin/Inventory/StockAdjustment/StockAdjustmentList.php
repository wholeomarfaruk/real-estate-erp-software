<?php

namespace App\Livewire\Admin\Inventory\StockAdjustment;

use App\Enums\Inventory\StockAdjustmentStatus;
use App\Enums\Inventory\StockAdjustmentType;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\StockAdjustment;
use App\Models\Store;
use App\Services\Inventory\StockAdjustmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockAdjustmentList extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $storeFilter = null;

    public string $typeFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.stock.adjustment.view');
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

    public function updatedTypeFilter(): void
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

    public function postAdjustment(int $stockAdjustmentId): void
    {
        $this->authorizePermission('inventory.stock.adjustment.post');

        $stockAdjustment = StockAdjustment::query()->find($stockAdjustmentId);

        if (! $stockAdjustment) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock adjustment not found.']);

            return;
        }

        $this->ensureStoreAccessible((int) $stockAdjustment->store_id);

        try {
            app(StockAdjustmentService::class)->postAdjustment($stockAdjustment, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock adjustment posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelAdjustment(int $stockAdjustmentId): void
    {
        $this->authorizePermission('inventory.stock.adjustment.update');

        $stockAdjustment = StockAdjustment::query()->find($stockAdjustmentId);

        if (! $stockAdjustment) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock adjustment not found.']);

            return;
        }

        $this->ensureStoreAccessible((int) $stockAdjustment->store_id);

        try {
            app(StockAdjustmentService::class)->cancelAdjustment($stockAdjustment, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock adjustment cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function deleteAdjustment(int $stockAdjustmentId): void
    {
        $this->authorizePermission('inventory.stock.adjustment.delete');

        $stockAdjustment = StockAdjustment::query()->find($stockAdjustmentId);

        if (! $stockAdjustment) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Stock adjustment not found.']);

            return;
        }

        $this->ensureStoreAccessible((int) $stockAdjustment->store_id);

        if ($stockAdjustment->status !== StockAdjustmentStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft adjustment can be deleted.']);

            return;
        }

        DB::transaction(function () use ($stockAdjustment): void {
            $stockAdjustment->items()->delete();
            $stockAdjustment->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock adjustment deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.stock.adjustment.view');

        $query = StockAdjustment::query()
            ->with(['store:id,name,code,type', 'creator:id,name', 'poster:id,name'])
            ->withSum('items as grand_total', 'total_price')
            ->when($this->search !== '', function (Builder $builder): void {
                $builder->where(function (Builder $subQuery): void {
                    $subQuery->where('adjustment_no', 'like', '%'.$this->search.'%')
                        ->orWhere('reason', 'like', '%'.$this->search.'%')
                        ->orWhere('remarks', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $builder): Builder => $builder->where('status', $this->statusFilter))
            ->when($this->typeFilter !== '', fn (Builder $builder): Builder => $builder->where('adjustment_type', $this->typeFilter))
            ->when($this->storeFilter, fn (Builder $builder): Builder => $builder->where('store_id', $this->storeFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('adjustment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('adjustment_date', '<=', $this->dateTo));

        $this->applyStoreRestriction($query);

        $adjustments = $query->latest('adjustment_date')->latest('id')->paginate(15);

        $statsQuery = StockAdjustment::query();
        $this->applyStoreRestriction($statsQuery);

        $totalAdjustments = (clone $statsQuery)->count();
        $postedAdjustments = (clone $statsQuery)->where('status', StockAdjustmentStatus::POSTED->value)->count();
        $draftAdjustments = (clone $statsQuery)->where('status', StockAdjustmentStatus::DRAFT->value)->count();
        $adjustmentInCount = (clone $statsQuery)->where('adjustment_type', StockAdjustmentType::IN->value)->count();
        $adjustmentOutCount = (clone $statsQuery)->where('adjustment_type', StockAdjustmentType::OUT->value)->count();

        $storesQuery = Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.stock-adjustment.stock-adjustment-list', [
            'adjustments' => $adjustments,
            'statuses' => StockAdjustmentStatus::cases(),
            'types' => StockAdjustmentType::cases(),
            'stores' => $storesQuery->get(['id', 'name', 'code']),
            'totalAdjustments' => $totalAdjustments,
            'postedAdjustments' => $postedAdjustments,
            'draftAdjustments' => $draftAdjustments,
            'adjustmentInCount' => $adjustmentInCount,
            'adjustmentOutCount' => $adjustmentOutCount,
        ])->layout('layouts.admin.admin');
    }
}
