<?php

namespace App\Livewire\Admin\Inventory\PurchaseOrder;

use App\Enums\Inventory\PurchaseMode;
use App\Enums\Inventory\PurchaseOrderStatus;
use App\Enums\Inventory\StockReceiveStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\PurchaseOrder;
use App\Models\StockReceiveItem;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PurchaseOrderView extends Component
{
    use InteractsWithInventoryAccess;

    public PurchaseOrder $purchaseOrder;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->authorizePermission('inventory.purchase_order.view');

        $this->purchaseOrder = $purchaseOrder->load([
            'requester:id,name',
            'store:id,name,code,type',
            'supplier:id,name,phone',
            'engineerApprover:id,name',
            'chairmanApprover:id,name',
            'accountsApprover:id,name',
            'items.product:id,name,sku',
            'approvals.user:id,name',
            'funds.releaser:id,name',
            'funds.receiver:id,name',
            'settlement.settler:id,name',
            'stockReceives' => fn ($query) => $query
                ->with(['store:id,name,code', 'supplier:id,name'])
                ->withSum('items as grand_total', 'total_price')
                ->latest('receive_date'),
        ]);

        $this->ensurePurchaseOrderAccessible($this->purchaseOrder);
    }

    public function submitOrder(): void
    {
        $this->authorizePermission('inventory.purchase_order.submit');

        if ($this->purchaseOrder->status !== PurchaseOrderStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft purchase order can be submitted.']);

            return;
        }

        try {
            app(PurchaseOrderService::class)->submitForEngineerApproval($this->purchaseOrder, (int) auth()->id());
            $this->reloadPurchaseOrder();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order submitted for engineer approval.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function engineerApprove(): void
    {
        $this->authorizePermission('inventory.purchase_order.engineer_approve');

        if ($this->purchaseOrder->status !== PurchaseOrderStatus::PENDING_ENGINEER) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This purchase order is not pending engineer approval.']);

            return;
        }

        try {
            app(PurchaseOrderService::class)->engineerApprove($this->purchaseOrder, (int) auth()->id());
            $this->reloadPurchaseOrder();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order approved by engineer.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function chairmanApprove(): void
    {
        $this->authorizePermission('inventory.purchase_order.chairman_approve');

        if ($this->purchaseOrder->status !== PurchaseOrderStatus::PENDING_CHAIRMAN) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This purchase order is not pending chairman approval.']);

            return;
        }

        try {
            app(PurchaseOrderService::class)->chairmanApprove($this->purchaseOrder, (int) auth()->id());
            $this->reloadPurchaseOrder();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order approved by chairman.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function accountsApprove(): void
    {
        $this->authorizePermission('inventory.purchase_order.accounts_approve');

        if ($this->purchaseOrder->status !== PurchaseOrderStatus::PENDING_ACCOUNTS) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This purchase order is not pending accounts approval.']);

            return;
        }

        try {
            app(PurchaseOrderService::class)->accountsApprove($this->purchaseOrder, (int) auth()->id());
            $this->reloadPurchaseOrder();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order approved by accounts.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function engineerReject(string $remarks = null): void
    {
        $this->authorizePermission('inventory.purchase_order.engineer_approve');

        if ($this->purchaseOrder->status !== PurchaseOrderStatus::PENDING_ENGINEER) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This purchase order is not pending engineer approval.']);

            return;
        }

        try {
            app(PurchaseOrderService::class)->reject(
                $this->purchaseOrder,
                \App\Enums\Inventory\ApprovalStage::ENGINEER,
                (int) auth()->id(),
                \App\Enums\Inventory\ApprovalAction::REJECTED,
                $remarks
            );
            $this->reloadPurchaseOrder();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order rejected by engineer.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function chairmanReject(string $remarks = null): void
    {
        $this->authorizePermission('inventory.purchase_order.chairman_approve');

        if ($this->purchaseOrder->status !== PurchaseOrderStatus::PENDING_CHAIRMAN) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This purchase order is not pending chairman approval.']);

            return;
        }

        try {
            app(PurchaseOrderService::class)->reject(
                $this->purchaseOrder,
                \App\Enums\Inventory\ApprovalStage::CHAIRMAN,
                (int) auth()->id(),
                \App\Enums\Inventory\ApprovalAction::REJECTED,
                $remarks
            );
            $this->reloadPurchaseOrder();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order rejected by chairman.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function accountsReject(string $remarks = null): void
    {
        $this->authorizePermission('inventory.purchase_order.accounts_approve');

        if ($this->purchaseOrder->status !== PurchaseOrderStatus::PENDING_ACCOUNTS) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This purchase order is not pending accounts approval.']);

            return;
        }

        try {
            app(PurchaseOrderService::class)->reject(
                $this->purchaseOrder,
                \App\Enums\Inventory\ApprovalStage::ACCOUNTS,
                (int) auth()->id(),
                \App\Enums\Inventory\ApprovalAction::REJECTED,
                $remarks
            );
            $this->reloadPurchaseOrder();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order rejected by accounts.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function completeOrder(): void
    {
        $this->authorizePermission('inventory.purchase_order.complete');

        if ($this->purchaseOrder->status !== PurchaseOrderStatus::RECEIVED) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only received purchase order can be completed.']);

            return;
        }

        try {
            app(PurchaseOrderService::class)->completePurchaseOrder($this->purchaseOrder, (int) auth()->id());
            $this->reloadPurchaseOrder();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order completed successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelOrder(): void
    {
        $this->authorizePermission('inventory.purchase_order.update');

        try {
            app(PurchaseOrderService::class)->cancelPurchaseOrder($this->purchaseOrder, (int) auth()->id());
            $this->reloadPurchaseOrder();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        $itemIds = $this->purchaseOrder->items->pluck('id')->all();

        $receivedByItem = StockReceiveItem::query()
            ->selectRaw('purchase_order_item_id, SUM(quantity) as received_quantity')
            ->whereIn('purchase_order_item_id', $itemIds === [] ? [0] : $itemIds)
            ->whereHas('stockReceive', function ($query): void {
                $query->where('status', StockReceiveStatus::POSTED->value);
            })
            ->groupBy('purchase_order_item_id')
            ->pluck('received_quantity', 'purchase_order_item_id');

        $itemSummaries = $this->purchaseOrder->items
            ->map(function ($item) use ($receivedByItem): array {
                $requiredQty = (float) ($item->approved_quantity ?: $item->quantity);
                $receivedQty = (float) ($receivedByItem[$item->id] ?? 0);

                return [
                    'item' => $item,
                    'required_qty' => $requiredQty,
                    'received_qty' => min($requiredQty, $receivedQty),
                    'remaining_qty' => max(0, $requiredQty - $receivedQty),
                ];
            })
            ->values();

        $estimatedTotal = (float) $this->purchaseOrder->items->sum(fn ($item): float => (float) $item->estimated_total_price);
        $approvedTotal = (float) $this->purchaseOrder->items->sum(fn ($item): float => (float) ($item->approved_total_price ?? $item->estimated_total_price));
        $fundReleasedTotal = (float) $this->purchaseOrder->funds->sum(fn ($fund): float => (float) $fund->amount);
        $receivedValueTotal = (float) $this->purchaseOrder->stockReceives
            ->sum(fn ($receive): float => $receive->status === StockReceiveStatus::POSTED
                ? (float) ($receive->grand_total ?? 0)
                : 0.0);

        return view('livewire.admin.inventory.purchase-order.purchase-order-view', [
            'itemSummaries' => $itemSummaries,
            'estimatedTotal' => round($estimatedTotal, 2),
            'approvedTotal' => round($approvedTotal, 2),
            'fundReleasedTotal' => round($fundReleasedTotal, 2),
            'receivedValueTotal' => round($receivedValueTotal, 2),
            'isCashMode' => $this->purchaseOrder->purchase_mode === PurchaseMode::CASH,
        ])->layout('layouts.admin.admin');
    }

    protected function reloadPurchaseOrder(): void
    {
        $this->purchaseOrder = $this->purchaseOrder->refresh()->load([
            'requester:id,name',
            'store:id,name,code,type',
            'supplier:id,name,phone',
            'engineerApprover:id,name',
            'chairmanApprover:id,name',
            'accountsApprover:id,name',
            'items.product:id,name,sku',
            'approvals.user:id,name',
            'funds.releaser:id,name',
            'funds.receiver:id,name',
            'settlement.settler:id,name',
            'stockReceives' => fn ($query) => $query
                ->with(['store:id,name,code', 'supplier:id,name'])
                ->withSum('items as grand_total', 'total_price')
                ->latest('receive_date'),
        ]);
    }

    protected function ensurePurchaseOrderAccessible(PurchaseOrder $purchaseOrder): void
    {
        if ($this->canViewAllStores()) {
            return;
        }

        $storeIds = $this->getAccessibleStoreIds();

        abort_unless(
            in_array((int) $purchaseOrder->store_id, $storeIds, true),
            403,
            'You are not allowed to access this purchase order.'
        );
    }

    protected function canViewAllStores(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('superadmin')
            || $user->hasRole('admin')
            || $user->hasRole('accounts')
            || $user->hasRole('engineers')
            || $user->hasRole('chairman')
            || $user->hasRole('md')
            || $user->can('inventory.purchase_order.engineer_approve')
            || $user->can('inventory.purchase_order.chairman_approve')
            || $user->can('inventory.purchase_order.accounts_approve');
    }
}
