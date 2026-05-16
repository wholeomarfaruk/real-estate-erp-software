<?php

namespace App\Livewire\Admin\Inventory\PurchaseOrder;

use App\Enums\Inventory\PurchaseMode;
use App\Enums\Inventory\PurchaseOrderStatus;
use App\Enums\Inventory\StockReceiveStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\StockReceiveItem;
use App\Models\StockRequest;
use App\Models\Store;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PurchaseOrderView extends Component
{
    use InteractsWithInventoryAccess;

    public PurchaseOrder $purchaseOrder;
    public array $engineerItemApprovals = [];
    public array $chairmanItemApprovals = [];
    public $linkedRequestItemIndex = null;
    public $linkedRequestDetails = null;
    public $quantityDetails = null;
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
            'items.product:id,name',
            'items.supplier:id,name,code,phone',
            'approvals.user:id,name',
            'funds.releaser:id,name',
            'funds.receiver:id,name',
            'settlement.settler:id,name',
            'stockReceives' => fn($query) => $query
                ->with(['store:id,name,code', 'supplier:id,name'])
                ->withSum('items as grand_total', 'total_price')
                ->latest('receive_date'),
        ]);

        $this->ensurePurchaseOrderAccessible($this->purchaseOrder);
        $this->syncItemApprovals();
    }

    public function updatedEngineerItemApprovals($value, string $name): void
    {
        if (!str_ends_with($name, '.approved_total_price')) {
            return;
        }

        [$itemId] = explode('.', $name);
        $qty = (float) ($this->engineerItemApprovals[$itemId]['approved_quantity'] ?? 0);
        $unit = (float) ($this->engineerItemApprovals[$itemId]['approved_unit_price'] ?? 0);
        $this->engineerItemApprovals[$itemId]['approved_total_price'] = round($qty * $unit, 2);
        
    }

    public function updatedChairmanItemApprovals($value, string $name): void
    {
        if (!str_ends_with($name, '.approved_total_price')) {
            return;
        }

        [$itemId] = explode('.', $name);
        $qty = (float) ($this->chairmanItemApprovals[$itemId]['approved_quantity'] ?? 0);
        $unit = (float) ($this->chairmanItemApprovals[$itemId]['approved_unit_price'] ?? 0);
        $this->chairmanItemApprovals[$itemId]['approved_total_price'] = round($qty * $unit, 2);
    }

    public function saveEngineerItemApprovals(): void
    {
        $this->authorizePermission('inventory.purchase_order.engineer_approve');

        if ($this->purchaseOrder->status !== PurchaseOrderStatus::PENDING_ENGINEER) {
            return;
        }
        
        app(PurchaseOrderService::class)->updateItemApprovals($this->purchaseOrder, $this->engineerItemApprovals, 'chief_engineer');
        app(PurchaseOrderService::class)->updateStatus($this->purchaseOrder, PurchaseOrderStatus::PENDING_ENGINEER);

        $this->reloadPurchaseOrder();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Engineer item approvals saved.']);
    }

    public function saveApprovalsItems(): void
    {
        $this->authorizePermission('inventory.purchase_order.approvals.update');

     

        app(PurchaseOrderService::class)->updateItemApprovals($this->purchaseOrder, $this->chairmanItemApprovals, 'approval');
        app(PurchaseOrderService::class)->updateStatus($this->purchaseOrder, PurchaseOrderStatus::PENDING_CHAIRMAN);

        $this->reloadPurchaseOrder();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Approval Items saved.']);
    }

    public function closeQuantityModal(): void
    {
        $this->quantityDetails = null;
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
            app(PurchaseOrderService::class)->updateItemApprovals($this->purchaseOrder, $this->engineerItemApprovals, 'chief_engineer');
            app(PurchaseOrderService::class)->engineerApprove($this->purchaseOrder, (int) auth()->id());
            $this->reloadPurchaseOrder();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order approved by engineer.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function chairmanApprove(float|int|string|null $remarks = null): void
    {
        $this->authorizePermission('inventory.purchase_order.chairman_approve');

        if ($this->purchaseOrder->status !== PurchaseOrderStatus::PENDING_CHAIRMAN) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This purchase order is not pending chairman approval.']);

            return;
        }

    
        try {
           app(PurchaseOrderService::class)->updateItemApprovals($this->purchaseOrder, $this->chairmanItemApprovals, 'approval');
            app(PurchaseOrderService::class)->chairmanApprove(
                $this->purchaseOrder,
                (int) auth()->id(),
                $remarks
            );
             
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

        $estimatedTotal = (float) $this->purchaseOrder->items->sum(fn($item): float => (float) $item->estimated_total_price);
        $approvedTotal = (float) $this->purchaseOrder->items->sum(fn($item): float => (float) ($item->approved_total_price ?? $item->estimated_total_price));
        $fundReleasedTotal = (float) $this->purchaseOrder->funds->sum(fn($fund): float => (float) $fund->amount);
        $receivedValueTotal = (float) $this->purchaseOrder->stockReceives
            ->sum(fn($receive): float => $receive->status === StockReceiveStatus::POSTED
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
            'items.product:id,name',
            'items.supplier:id,name,code,phone',
            'approvals.user:id,name',
            'funds.releaser:id,name',
            'funds.receiver:id,name',
            'settlement.settler:id,name',
            'stockReceives' => fn($query) => $query
                ->with(['store:id,name,code', 'supplier:id,name'])
                ->withSum('items as grand_total', 'total_price')
                ->latest('receive_date'),
            'stockRequests' => fn($query) =>$query->select('stock_requests.id', 'request_no'),
        ]);
        $this->syncItemApprovals();
    }

    protected function syncItemApprovals(): void
    {
        $mapped = $this->purchaseOrder->items->mapWithKeys(function ($item): array {
            $qty = (float) ($item->approved_quantity ?? $item->eng_approved_quantity ??  $item->quantity ?? 0);
            $unit = (float) ($item->approved_unit_price ?? $item->eng_approved_unit_price ??  $item->estimated_unit_price ?? 0);

            return [
                (string) $item->id => [
                    'approved_quantity' => $qty,
                    'approved_unit_price' => $unit,
                    'approved_total_price' => round($qty * $unit, 2),
                ],
            ];
        })->toArray();

        $engmaped = $this->purchaseOrder->items->mapWithKeys(function ($item): array {
            $qty = (float) ($item->eng_approved_quantity ?? $item->quantity ?? 0);
            $unit = (float) ($item->eng_approved_unit_price ?? $item->estimated_unit_price ?? 0);

            return [
                (string) $item->id => [
                    'approved_quantity' => $qty,
                    'approved_unit_price' => $unit,
                    'approved_total_price' => round($qty * $unit, 2),
                ],
            ];
        })->toArray();

        $this->engineerItemApprovals = $engmaped;
        $this->chairmanItemApprovals = $mapped;
    }
    public function openLinkedRequestDetails(int $itemId): void
    {
        $this->linkedRequestItemIndex = $itemId;
        $this->linkedRequestDetails = $this->buildLinkedRequestDetails($itemId);

    }

    public function closeLinkedRequestDetails(): void
    {
        $this->linkedRequestItemIndex = null;
        $this->linkedRequestDetails = null;
    }

    private function buildLinkedRequestDetails(int $itemId): array
    {

        $item = $this->purchaseOrder->items->where('id', $itemId)->first();

        if (!$item) {
            return [];
        }



        $productId = (int) $item['product_id'];
        $product = Product::query()->find($productId);

        $linkedRequests = $this->purchaseOrder->stockRequests()->wherePivot('product_id', $item['product_id'])->with('requesterStore')->get() ?? [];

        // $linkedRequests = $this->purchaseOrderRecord->stockRequests()
        //     ->wherePivot('product_id', $productId)
        //     ->with([
        //         'items' => fn ($query) => $query->where('product_id', $productId),
        //         'requesterStore',
        //     ])
        //     ->get();

        $requestDetails = $linkedRequests->map(fn($request) => [
            'id' => $request->id,
            'request_no' => $request->request_no,
            'status' => $request->status?->label(),
            'requester_name' => $request->requesterStore?->name,
            'requested_quantity' => (float) ($request->items->first()?->approved_quantity ?: $request->items->first()?->quantity ?: 0),
            'fulfilled_quantity' => (float) ($request->items->first()?->fulfilled_quantity ?: 0),
        ])->map(function ($request) {
            $request['remaining_quantity'] = max(0, $request['requested_quantity'] - $request['fulfilled_quantity']);
            return $request;
        });

        $officeStoreIds = Store::query()->office()->pluck('id')->toArray();
        $officeStock = (float) StockBalance::query()
            ->where('product_id', $productId)
            ->whereIn('store_id', $officeStoreIds)
            ->sum('quantity');

        $totalRequested = $requestDetails->sum('requested_quantity');
        $totalFulfilled = $requestDetails->sum('fulfilled_quantity');
        $totalRemaining = $requestDetails->sum('remaining_quantity');
        $needToPurchase = max(0, $totalRemaining - $officeStock);

        return [
            'product_id' => $productId,
            'product_name' => $product?->name,
            'product_unit' => $product?->unit,
            'requests' => $requestDetails->toArray(),
            'total_requested' => $totalRequested,
            'total_fulfilled' => $totalFulfilled,
            'total_remaining' => $totalRemaining,
            'office_stock' => $officeStock,
            'need_to_purchase' => $needToPurchase,
        ];
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
        return $this->hasInventoryWideAccess($this->purchaseOrderGlobalAccessPermissions());
    }
}
