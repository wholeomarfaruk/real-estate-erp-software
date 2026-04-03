<?php

namespace App\Livewire\Admin\Inventory\StockAdjustment;

use App\Enums\Inventory\StockAdjustmentStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\StockAdjustment;
use App\Services\Inventory\StockAdjustmentService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StockAdjustmentView extends Component
{
    use InteractsWithInventoryAccess;

    public StockAdjustment $stockAdjustment;

    public function mount(StockAdjustment $stockAdjustment): void
    {
        $this->authorizePermission('inventory.stock.adjustment.view');

        $this->stockAdjustment = $stockAdjustment->load([
            'store:id,name,code,type',
            'creator:id,name',
            'poster:id,name',
            'items.product:id,name,sku',
        ]);

        $this->ensureStoreAccessible((int) $this->stockAdjustment->store_id);
    }

    public function postAdjustment(): void
    {
        $this->authorizePermission('inventory.stock.adjustment.post');

        if ($this->stockAdjustment->status !== StockAdjustmentStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft adjustment can be posted.']);

            return;
        }

        try {
            app(StockAdjustmentService::class)->postAdjustment($this->stockAdjustment, (int) auth()->id());
            $this->reloadAdjustment();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock adjustment posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelAdjustment(): void
    {
        $this->authorizePermission('inventory.stock.adjustment.update');

        if ($this->stockAdjustment->status !== StockAdjustmentStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft adjustment can be cancelled.']);

            return;
        }

        try {
            app(StockAdjustmentService::class)->cancelAdjustment($this->stockAdjustment, (int) auth()->id());
            $this->reloadAdjustment();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock adjustment cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        $grandTotal = (float) $this->stockAdjustment->items->sum(fn ($item): float => (float) $item->total_price);

        return view('livewire.admin.inventory.stock-adjustment.stock-adjustment-view', [
            'grandTotal' => round($grandTotal, 2),
        ])->layout('layouts.admin.admin');
    }

    protected function reloadAdjustment(): void
    {
        $this->stockAdjustment = $this->stockAdjustment->refresh()->load([
            'store:id,name,code,type',
            'creator:id,name',
            'poster:id,name',
            'items.product:id,name,sku',
        ]);
    }
}
