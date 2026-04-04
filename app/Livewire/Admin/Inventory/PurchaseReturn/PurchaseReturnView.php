<?php

namespace App\Livewire\Admin\Inventory\PurchaseReturn;

use App\Enums\Inventory\PurchaseReturnStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\PurchaseReturn;
use App\Services\Inventory\PurchaseReturnService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PurchaseReturnView extends Component
{
    use InteractsWithInventoryAccess;

    public PurchaseReturn $purchaseReturn;

    public function mount(PurchaseReturn $purchaseReturn): void
    {
        $this->authorizePermission('inventory.purchase_return.view');

        $this->purchaseReturn = $purchaseReturn->load([
            'supplier:id,name,phone,contact_person',
            'store:id,name,code,type',
            'purchaseOrder:id,po_no,status',
            'stockReceive:id,receive_no,receive_date,purchase_order_id',
            'creator:id,name',
            'poster:id,name',
            'items.product:id,name,sku',
            'items.stockReceiveItem:id,stock_receive_id,purchase_order_item_id,product_id,quantity,unit_price',
            'items.purchaseOrderItem:id,purchase_order_id,product_id',
        ]);

        $this->ensureStoreAccessible((int) $this->purchaseReturn->store_id);
    }

    public function postReturn(): void
    {
        $this->authorizePermission('inventory.purchase_return.post');

        if ($this->purchaseReturn->status !== PurchaseReturnStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft purchase return can be posted.']);

            return;
        }

        try {
            app(PurchaseReturnService::class)->postReturn($this->purchaseReturn, (int) auth()->id());
            $this->reloadPurchaseReturn();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase return posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelReturn(): void
    {
        $this->authorizePermission('inventory.purchase_return.update');

        if ($this->purchaseReturn->status !== PurchaseReturnStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft purchase return can be cancelled.']);

            return;
        }

        try {
            app(PurchaseReturnService::class)->cancelReturn($this->purchaseReturn, (int) auth()->id());
            $this->reloadPurchaseReturn();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase return cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        $grandTotal = (float) $this->purchaseReturn->items->sum(fn ($item): float => (float) $item->total_price);

        return view('livewire.admin.inventory.purchase-return.purchase-return-view', [
            'grandTotal' => round($grandTotal, 2),
        ])->layout('layouts.admin.admin');
    }

    protected function reloadPurchaseReturn(): void
    {
        $this->purchaseReturn = $this->purchaseReturn->refresh()->load([
            'supplier:id,name,phone,contact_person',
            'store:id,name,code,type',
            'purchaseOrder:id,po_no,status',
            'stockReceive:id,receive_no,receive_date,purchase_order_id',
            'creator:id,name',
            'poster:id,name',
            'items.product:id,name,sku',
            'items.stockReceiveItem:id,stock_receive_id,purchase_order_item_id,product_id,quantity,unit_price',
            'items.purchaseOrderItem:id,purchase_order_id,product_id',
        ]);
    }
}
