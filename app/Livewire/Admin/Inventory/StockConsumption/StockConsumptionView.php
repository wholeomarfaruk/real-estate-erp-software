<?php

namespace App\Livewire\Admin\Inventory\StockConsumption;

use App\Enums\Inventory\StockConsumptionStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\StockConsumption;
use App\Services\Inventory\StockConsumptionService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StockConsumptionView extends Component
{
    use InteractsWithInventoryAccess;

    public StockConsumption $stockConsumption;

    public function mount(StockConsumption $stockConsumption): void
    {
        $this->authorizePermission('inventory.stock.consumption.view');

        $this->stockConsumption = $stockConsumption->load([
            'store:id,name,code,type,project_id',
            'project:id,name,code',
            'creator:id,name',
            'poster:id,name',
            'items.product:id,name,sku',
        ]);

        $this->ensureStoreAccessible((int) $this->stockConsumption->store_id);
    }

    public function postConsumption(): void
    {
        $this->authorizePermission('inventory.stock.consumption.post');

        if ($this->stockConsumption->status !== StockConsumptionStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft consumption can be posted.']);

            return;
        }

        try {
            app(StockConsumptionService::class)->postConsumption($this->stockConsumption, (int) auth()->id());
            $this->stockConsumption = $this->stockConsumption->refresh()->load(['store', 'project', 'creator', 'poster', 'items.product']);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock consumption posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.inventory.stock-consumption.stock-consumption-view')
            ->layout('layouts.admin.admin');
    }
}
