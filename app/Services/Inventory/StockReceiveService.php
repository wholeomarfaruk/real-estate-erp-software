<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\PurchaseOrderStatus;
use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use App\Enums\Inventory\StockReceiveStatus;
use App\Enums\Inventory\StoreType;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\StockReceive;
use App\Models\StockReceiveItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockReceiveService
{
    public function __construct(
        protected StockService $stockService,
        protected PurchaseOrderService $purchaseOrderService,
        protected PurchaseReturnService $purchaseReturnService
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

                if ((int) $purchaseOrder->store_id !== (int) $lockedReceive->store_id) {
                    throw new \DomainException('Receive store does not match linked purchase order store.');
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

    /**
     * @param  array{
     *   receive_date:string,
     *   supplier_voucher?:string|null,
     *   remarks?:string|null,
     *   items:array<int, array{
     *      id:int|string|null,
     *      quantity:float|int|string,
     *      unit_price:float|int|string,
     *      remarks?:string|null
     *   }>
     * }  $payload
     */
    public function updatePostedReceive(StockReceive $stockReceive, array $payload, ?int $userId = null): StockReceive
    {
        $actorId = $userId ?? (int) Auth::id();

        if ($actorId <= 0) {
            throw new \DomainException('A valid user is required to update stock receive.');
        }

        return DB::transaction(function () use ($stockReceive, $payload, $actorId): StockReceive {
            $lockedReceive = StockReceive::query()
                ->with([
                    'store',
                    'purchaseOrder.items.product',
                    'purchaseOrder.settlement',
                    'items.purchaseOrderItem.product',
                ])
                ->lockForUpdate()
                ->findOrFail($stockReceive->id);

            $this->ensurePostedReceiveEditable($lockedReceive);

            $normalizedItems = $this->validateAdjustedReceiveItems($lockedReceive, $payload['items'] ?? []);

            $lockedReceive->update([
                'receive_date' => $payload['receive_date'],
                'supplier_voucher' => $payload['supplier_voucher'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
            ]);

            foreach ($normalizedItems as $itemId => $row) {
                $lockedReceive->items
                    ->firstWhere('id', $itemId)
                    ?->update([
                        'quantity' => $row['quantity'],
                        'unit_price' => $row['unit_price'],
                        'total_price' => $row['total_price'],
                        'remarks' => $row['remarks'],
                    ]);
            }

            $lockedReceive->refresh()->load([
                'store',
                'purchaseOrder.items.product',
                'purchaseOrder.settlement',
                'items.purchaseOrderItem.product',
            ]);

            $affectedPairs = $lockedReceive->items
                ->map(fn (StockReceiveItem $item): array => [
                    'store_id' => (int) $lockedReceive->store_id,
                    'product_id' => (int) $item->product_id,
                ])
                ->unique(fn (array $pair): string => $pair['store_id'].'-'.$pair['product_id'])
                ->values()
                ->all();

            $this->syncPostedReceiveMovements($lockedReceive, $actorId);
            $this->rebuildAffectedBalances($affectedPairs);

            if ($lockedReceive->purchaseOrder) {
                $this->purchaseOrderService->recalculateReceiveStatus($lockedReceive->purchaseOrder);
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

    protected function ensurePostedReceiveEditable(StockReceive $stockReceive): void
    {
        if ($stockReceive->status !== StockReceiveStatus::POSTED) {
            throw new \DomainException('Only posted stock receive can be adjusted with this action.');
        }

        if ($stockReceive->purchaseOrder?->settlement?->settled_at) {
            throw new \DomainException('Stock receive cannot be edited after settlement is completed.');
        }
    }

    /**
     * @param  array<int, array{
     *   id:int|string|null,
     *   quantity:float|int|string,
     *   unit_price:float|int|string,
     *   remarks?:string|null
     * }>  $items
     * @return array<int, array{
     *   quantity:float,
     *   unit_price:float,
     *   total_price:float,
     *   remarks:?string
     * }>
     */
    protected function validateAdjustedReceiveItems(StockReceive $stockReceive, array $items): array
    {
        $currentItems = $stockReceive->items->keyBy(fn (StockReceiveItem $item): int => (int) $item->id);
        $currentItemIds = $currentItems->keys()->all();

        if ($currentItemIds === []) {
            throw new \DomainException('This posted stock receive has no items to edit.');
        }

        $submittedIds = collect($items)
            ->map(fn (array $row): int => (int) ($row['id'] ?? 0))
            ->all();

        sort($currentItemIds);
        sort($submittedIds);

        if ($submittedIds !== $currentItemIds) {
            throw new \DomainException('Posted receive item structure cannot be changed. Edit existing rows only.');
        }

        $returnedMap = $this->purchaseReturnService->postedReturnedQtyMap($currentItemIds);
        $normalized = [];
        $requestedByPoItem = [];
        $otherPostedByPoItem = [];

        if ($stockReceive->purchaseOrder) {
            $poItemIds = $stockReceive->purchaseOrder->items->pluck('id')->all();

            $otherPostedByPoItem = StockReceiveItem::query()
                ->selectRaw('purchase_order_item_id, SUM(quantity) as received_quantity')
                ->whereIn('purchase_order_item_id', $poItemIds === [] ? [0] : $poItemIds)
                ->where('stock_receive_id', '!=', (int) $stockReceive->id)
                ->whereHas('stockReceive', function ($query): void {
                    $query->where('status', StockReceiveStatus::POSTED->value);
                })
                ->groupBy('purchase_order_item_id')
                ->pluck('received_quantity', 'purchase_order_item_id')
                ->all();
        }

        foreach ($items as $row) {
            $itemId = (int) ($row['id'] ?? 0);
            $currentItem = $currentItems->get($itemId);

            if (! $currentItem) {
                throw new \DomainException('One or more receive items are invalid.');
            }

            $quantity = round((float) ($row['quantity'] ?? 0), 3);
            $unitPrice = round((float) ($row['unit_price'] ?? 0), 2);

            if ($quantity <= 0) {
                throw new \DomainException('Receive quantity must be greater than zero.');
            }

            if ($unitPrice < 0) {
                throw new \DomainException('Unit price cannot be negative.');
            }

            $alreadyReturnedQty = round((float) ($returnedMap[$itemId] ?? 0), 3);
            if ($quantity + 0.0001 < $alreadyReturnedQty) {
                throw new \DomainException('Receive quantity cannot be less than already returned quantity.');
            }

            $normalized[$itemId] = [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => round($quantity * $unitPrice, 2),
                'remarks' => $row['remarks'] ?? null,
            ];

            if ($stockReceive->purchaseOrder && $currentItem->purchase_order_item_id) {
                $requestedByPoItem[(int) $currentItem->purchase_order_item_id] = ($requestedByPoItem[(int) $currentItem->purchase_order_item_id] ?? 0) + $quantity;
            }
        }

        if ($stockReceive->purchaseOrder) {
            $poItems = $stockReceive->purchaseOrder->items->keyBy('id');

            foreach ($requestedByPoItem as $poItemId => $requestedQty) {
                $poItem = $poItems->get($poItemId);
                if (! $poItem) {
                    throw new \DomainException('Receive item references an invalid purchase order item.');
                }

                $requiredQty = (float) ($poItem->approved_quantity ?: $poItem->quantity);
                $otherPostedQty = (float) ($otherPostedByPoItem[$poItemId] ?? 0);

                if ($otherPostedQty + $requestedQty > $requiredQty + 0.0001) {
                    throw new \DomainException(
                        ($poItem->product?->name ?? 'Purchase order item')
                        .' receive quantity exceeds pending quantity. Max allowed: '
                        .number_format(max(0, $requiredQty - $otherPostedQty), 3)
                    );
                }
            }
        }

        return $normalized;
    }

    protected function syncPostedReceiveMovements(StockReceive $stockReceive, int $actorId): void
    {
        $existingMovements = StockMovement::query()
            ->where('reference_type', 'stock_receive')
            ->where('reference_id', (int) $stockReceive->id)
            ->orderBy('id')
            ->get();

        $items = $stockReceive->items
            ->sortBy('id')
            ->values();

        if ($existingMovements->count() === $items->count() && $existingMovements->isNotEmpty()) {
            foreach ($items as $index => $item) {
                $existingMovements[$index]->update([
                    'movement_date' => $stockReceive->receive_date,
                    'product_id' => (int) $item->product_id,
                    'store_id' => (int) $stockReceive->store_id,
                    'project_id' => $stockReceive->store?->project_id,
                    'supplier_id' => $stockReceive->supplier_id,
                    'direction' => StockMovementDirection::IN->value,
                    'movement_type' => StockMovementType::PURCHASE->value,
                    'quantity' => round((float) $item->quantity, 3),
                    'unit_price' => round((float) $item->unit_price, 2),
                    'total_price' => round((float) $item->total_price, 2),
                    'balance_after' => null,
                    'reference_no' => $stockReceive->receive_no,
                    'remarks' => $item->remarks ?: $stockReceive->remarks,
                    'created_by' => $actorId,
                ]);
            }

            return;
        }

        if ($existingMovements->isNotEmpty()) {
            StockMovement::query()
                ->where('reference_type', 'stock_receive')
                ->where('reference_id', (int) $stockReceive->id)
                ->delete();
        }

        foreach ($items as $item) {
            $this->stockService->createMovement([
                'movement_date' => $stockReceive->receive_date,
                'product_id' => (int) $item->product_id,
                'store_id' => (int) $stockReceive->store_id,
                'project_id' => $stockReceive->store?->project_id,
                'supplier_id' => $stockReceive->supplier_id,
                'direction' => StockMovementDirection::IN,
                'movement_type' => StockMovementType::PURCHASE,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'balance_after' => null,
                'reference_type' => 'stock_receive',
                'reference_id' => (int) $stockReceive->id,
                'reference_no' => $stockReceive->receive_no,
                'remarks' => $item->remarks ?: $stockReceive->remarks,
                'created_by' => $actorId,
            ]);
        }
    }

    /**
     * @param  array<int, array{store_id:int, product_id:int}>  $pairs
     */
    protected function rebuildAffectedBalances(array $pairs): void
    {
        foreach ($pairs as $pair) {
            $this->stockService->rebuildBalanceFromMovements(
                storeId: (int) $pair['store_id'],
                productId: (int) $pair['product_id']
            );
        }
    }
}
