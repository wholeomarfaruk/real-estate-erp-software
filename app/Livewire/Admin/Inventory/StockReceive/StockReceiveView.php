<?php

namespace App\Livewire\Admin\Inventory\StockReceive;

use App\Enums\Inventory\StockReceiveStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\StockReceive;
use App\Services\Inventory\StockReceiveService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class StockReceiveView extends Component
{
    use InteractsWithInventoryAccess;

    public StockReceive $stockReceive;

    public function mount(StockReceive $stockReceive): void
    {
        $this->authorizePermission('inventory.stock.receive.view');

        $this->stockReceive = $stockReceive->load([
            'supplier:id,name,contact_person,phone',
            'store:id,name,code,type',
            'creator:id,name',
            'poster:id,name',
            'items.product:id,name,sku',
        ]);

        $this->ensureStoreAccessible((int) $this->stockReceive->store_id);
    }

    public function postReceive(): void
    {
        $this->authorizePermission('inventory.stock.receive.post');

        if ($this->stockReceive->status !== StockReceiveStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft receive can be posted.']);

            return;
        }

        try {
            app(StockReceiveService::class)->postReceive($this->stockReceive);
            $this->stockReceive = $this->stockReceive->refresh()->load(['items.product', 'supplier', 'store', 'creator', 'poster']);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock receive posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelReceive(): void
    {
        $this->authorizePermission('inventory.stock.receive.update');

        if ($this->stockReceive->status !== StockReceiveStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft receive can be cancelled.']);

            return;
        }

        DB::transaction(function (): void {
            $this->stockReceive->update([
                'status' => StockReceiveStatus::CANCELLED->value,
            ]);
        });

        $this->stockReceive = $this->stockReceive->refresh()->load(['items.product', 'supplier', 'store', 'creator', 'poster']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock receive cancelled successfully.']);
    }

    public function render(): View
    {
        $grandTotal = (float) $this->stockReceive->items->sum(fn ($item): float => (float) $item->total_price);

        return view('livewire.admin.inventory.stock-receive.stock-receive-view', [
            'grandTotal' => round($grandTotal, 2),
        ])
            ->layout('layouts.admin.admin');
    }
}
