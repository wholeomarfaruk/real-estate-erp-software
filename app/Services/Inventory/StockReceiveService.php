<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\PurchaseOrderStatus;
use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use App\Enums\Inventory\StockReceiveStatus;
use App\Enums\Inventory\StoreType;
use App\Models\PurchaseOrder;
use App\Models\StockReceive;
use App\Models\StockReceiveItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockReceiveService
{
    public function __construct(
        protected StockService $stockService,
        protected PurchaseOrderService $purchaseOrderService
    ) {}

    public function generateReceiveNo(): string
    {
        $lastId = (int) StockReceive::query()->max('id');

        return 'SR-'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    public function postReceive(StockReceive $stockReceive, ?int $userId = null): StockReceive
    {
        $actorId = $userId ?? (int) Auth::id();

        if ($actorId <= 0) {
            throw new \DomainException('A valid user is required to post stock receive.');
        }

        return DB::transaction(function () use ($stockReceive, $actorId): StockReceive {
            $lockedReceive = StockReceive::query()
                ->with(['store', 'items', 'items.product'])
                ->lockForUpdate()
                ->findOrFail($stockReceive->id);

            if ($lockedReceive->status === StockReceiveStatus::POSTED) {
                throw new \DomainException('This stock receive is already posted.');
            }

            if ($lockedReceive->status === StockReceiveStatus::CANCELLED) {
                throw new \DomainException('Cancelled stock receive cannot be posted.');
            }

            if (! $lockedReceive->store || $lockedReceive->store->type !== StoreType::OFFICE) {
                throw new \DomainException('Stock receive is allowed only for office stores.');
            }

            if ($lockedReceive->items->isEmpty()) {
                throw new \DomainException('At least one item is required before posting.');
            }

            $purchaseOrder = null;

            if ($lockedReceive->purchase_order_id) {
                $purchaseOrder = PurchaseOrder::query()
                    ->with(['items', 'items.product'])
                    ->lockForUpdate()
                    ->find($lockedReceive->purchase_order_id);

                if (! $purchaseOrder) {
                    throw new \DomainException('Linked purchase order not found.');
                }

                if (! in_array($purchaseOrder->status, [
                    PurchaseOrderStatus::APPROVED,
                    PurchaseOrderStatus::PARTIALLY_RECEIVED,
                ], true)) {
                    throw new \DomainException('Stock receive can be posted only against approved or partially received purchase order.');
                }

                if ($purchaseOrder->supplier_id && $lockedReceive->supplier_id && (int) $purchaseOrder->supplier_id !== (int) $lockedReceive->supplier_id) {
                    throw new \DomainException('Receive supplier does not match linked purchase order supplier.');
                }

                if (! $lockedReceive->supplier_id && $purchaseOrder->supplier_id) {
                    $lockedReceive->supplier_id = (int) $purchaseOrder->supplier_id;
                }

                $this->validatePurchaseOrderItems($lockedReceive, $purchaseOrder);
            }

            foreach ($lockedReceive->items as $item) {
                $quantity = (float) $item->quantity;
                $unitPrice = (float) $item->unit_price;
                $totalPrice = round($quantity * $unitPrice, 2);

                if ($quantity <= 0) {
                    throw new \DomainException('Item quantity must be greater than zero.');
                }

                $balance = $this->stockService->increaseBalance(
                    storeId: (int) $lockedReceive->store_id,
                    productId: (int) $item->product_id,
                    quantity: $quantity,
                    unitRate: $unitPrice
                );

                $item->update([
                    'total_price' => $totalPrice,
                ]);

                $this->stockService->createMovement([
                    'movement_date' => $lockedReceive->receive_date,
                    'product_id' => (int) $item->product_id,
                    'store_id' => (int) $lockedReceive->store_id,
                    'project_id' => $lockedReceive->store->project_id,
                    'supplier_id' => $lockedReceive->supplier_id,
                    'direction' => StockMovementDirection::IN,
                    'movement_type' => StockMovementType::PURCHASE,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'balance_after' => (float) $balance->quantity,
                    'reference_type' => 'stock_receive',
                    'reference_id' => (int) $lockedReceive->id,
                    'reference_no' => $lockedReceive->receive_no,
                    'remarks' => $item->remarks ?: $lockedReceive->remarks,
                    'created_by' => $actorId,
                ]);
            }

            $lockedReceive->update([
                'status' => StockReceiveStatus::POSTED->value,
                'supplier_id' => $lockedReceive->supplier_id,
                'posted_by' => $actorId,
                'posted_at' => now(),
            ]);

            if ($purchaseOrder) {
                $this->purchaseOrderService->recalculateReceiveStatus($purchaseOrder);
            }

            return $lockedReceive->refresh();
        });
    }

    protected function validatePurchaseOrderItems(StockReceive $stockReceive, PurchaseOrder $purchaseOrder): void
    {
        $poItems = $purchaseOrder->items->keyBy('id');

        $postedReceivedByPoItem = StockReceiveItem::query()
            ->selectRaw('purchase_order_item_id, SUM(quantity) as received_quantity')
            ->whereIn('purchase_order_item_id', $poItems->keys()->all() === [] ? [0] : $poItems->keys()->all())
            ->whereHas('stockReceive', function ($query): void {
                $query->where('status', StockReceiveStatus::POSTED->value);
            })
            ->groupBy('purchase_order_item_id')
            ->pluck('received_quantity', 'purchase_order_item_id');

        $currentReceiveByPoItem = [];

        foreach ($stockReceive->items as $item) {
            $poItemId = (int) ($item->purchase_order_item_id ?? 0);
            if ($poItemId <= 0) {
                throw new \DomainException('Each stock receive row must be linked to a purchase order item for PO based receive.');
            }

            $poItem = $poItems->get($poItemId);
            if (! $poItem) {
                throw new \DomainException('Receive item references an invalid purchase order item.');
            }

            if ((int) $poItem->product_id !== (int) $item->product_id) {
                throw new \DomainException('Receive product does not match linked purchase order item.');
            }

            $currentReceiveByPoItem[$poItemId] = ($currentReceiveByPoItem[$poItemId] ?? 0) + (float) $item->quantity;
        }

        foreach ($currentReceiveByPoItem as $poItemId => $currentQty) {
            $poItem = $poItems->get($poItemId);

            if (! $poItem) {
                continue;
            }

            $requiredQty = (float) ($poItem->approved_quantity ?: $poItem->quantity);
            $alreadyPostedQty = (float) ($postedReceivedByPoItem[$poItemId] ?? 0);

            if ($alreadyPostedQty + $currentQty > $requiredQty + 0.0001) {
                throw new \DomainException(
                    ($poItem->product?->name ?? 'Purchase order item')
                    .' receive quantity exceeds pending quantity. Pending: '
                    .number_format(max(0, $requiredQty - $alreadyPostedQty), 3)
                );
            }
        }
    }
}
