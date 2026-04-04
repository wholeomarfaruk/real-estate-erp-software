<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\PurchaseReturnStatus;
use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use App\Enums\Inventory\StockReceiveStatus;
use App\Enums\Inventory\StoreType;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function generateReturnNo(): string
    {
        $lastId = (int) PurchaseReturn::query()->max('id');

        return 'PR-'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    public function postReturn(PurchaseReturn $purchaseReturn, ?int $userId = null): PurchaseReturn
    {
        $actorId = $this->resolveActorId($userId);

        return DB::transaction(function () use ($purchaseReturn, $actorId): PurchaseReturn {
            $lockedReturn = PurchaseReturn::query()
                ->with([
                    'store',
                    'stockReceive',
                    'items',
                    'items.product',
                    'items.stockReceiveItem',
                    'items.stockReceiveItem.purchaseOrderItem',
                ])
                ->lockForUpdate()
                ->findOrFail($purchaseReturn->id);

            if ($lockedReturn->status === PurchaseReturnStatus::POSTED) {
                throw new \DomainException('This purchase return is already posted.');
            }

            if ($lockedReturn->status === PurchaseReturnStatus::CANCELLED) {
                throw new \DomainException('Cancelled purchase return cannot be posted.');
            }

            $this->validateDraftBeforePost($lockedReturn);

            $stockReceiveItemIds = $lockedReturn->items
                ->pluck('stock_receive_item_id')
                ->filter(fn ($id): bool => (int) $id > 0)
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $alreadyReturnedMap = $this->postedReturnedQtyMap($stockReceiveItemIds, (int) $lockedReturn->id);

            $requiredByProduct = [];
            $availableByProduct = [];
            $lineValues = [];

            foreach ($lockedReturn->items as $item) {
                $quantity = round((float) $item->quantity, 3);

                if ($quantity <= 0) {
                    throw new \DomainException('Return quantity must be greater than zero.');
                }

                $receiveItem = $item->stockReceiveItem;
                if (! $receiveItem) {
                    throw new \DomainException('Each return row must be linked to a stock receive item.');
                }

                if ((int) $receiveItem->stock_receive_id !== (int) $lockedReturn->stock_receive_id) {
                    throw new \DomainException('Return item does not belong to the selected stock receive.');
                }

                if ((int) $receiveItem->product_id !== (int) $item->product_id) {
                    throw new \DomainException('Return item product does not match linked stock receive item.');
                }

                if ($item->purchase_order_item_id && $receiveItem->purchase_order_item_id && (int) $item->purchase_order_item_id !== (int) $receiveItem->purchase_order_item_id) {
                    throw new \DomainException('Return item purchase order reference mismatch.');
                }

                $productId = (int) $item->product_id;
                $originalQty = (float) $receiveItem->quantity;
                $alreadyReturned = (float) ($alreadyReturnedMap[(int) $receiveItem->id] ?? 0);
                $returnableQty = $this->calculateReturnableQty($originalQty, $alreadyReturned);
                $availableQty = $availableByProduct[$productId]
                    ??= $this->availableQty((int) $lockedReturn->store_id, $productId);
                $maxReturnQty = $this->calculateMaxReturnQty($returnableQty, $availableQty);

                if ($quantity > $maxReturnQty + 0.0001) {
                    throw new \DomainException(
                        ($item->product?->name ?? 'Selected product').
                        ' return quantity exceeds allowed max. Max: '.number_format($maxReturnQty, 3)
                    );
                }

                $requiredByProduct[$productId] = ($requiredByProduct[$productId] ?? 0) + $quantity;

                $unitPrice = round((float) $receiveItem->unit_price, 2);
                $totalPrice = round($quantity * $unitPrice, 2);

                $lineValues[$item->id] = [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ];
            }

            foreach ($requiredByProduct as $productId => $requiredQty) {
                $availableQty = (float) ($availableByProduct[$productId] ?? 0);

                if ($requiredQty > $availableQty + 0.0001) {
                    throw new \DomainException(
                        'Insufficient stock for return on selected store. Available: '
                        .number_format($availableQty, 3).', Required: '.number_format($requiredQty, 3)
                    );
                }
            }

            foreach ($lockedReturn->items as $item) {
                $line = $lineValues[$item->id] ?? null;
                if (! $line) {
                    continue;
                }

                $decreased = $this->stockService->decreaseBalance(
                    storeId: (int) $lockedReturn->store_id,
                    productId: (int) $item->product_id,
                    quantity: (float) $line['quantity'],
                    unitRate: (float) $line['unit_price']
                );

                $balance = $decreased['balance'];

                $item->update([
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total_price' => $line['total_price'],
                ]);

                $this->stockService->createMovement([
                    'movement_date' => $lockedReturn->return_date,
                    'product_id' => (int) $item->product_id,
                    'store_id' => (int) $lockedReturn->store_id,
                    'project_id' => $lockedReturn->store?->project_id,
                    'supplier_id' => (int) $lockedReturn->supplier_id,
                    'direction' => StockMovementDirection::OUT,
                    'movement_type' => StockMovementType::RETURN,
                    'quantity' => (float) $line['quantity'],
                    'unit_price' => (float) $line['unit_price'],
                    'total_price' => (float) $line['total_price'],
                    'balance_after' => (float) $balance->quantity,
                    'reference_type' => 'purchase_return',
                    'reference_id' => (int) $lockedReturn->id,
                    'reference_no' => $lockedReturn->return_no,
                    'remarks' => $item->remarks ?: $lockedReturn->remarks,
                    'created_by' => $actorId,
                ]);
            }

            $lockedReturn->update([
                'status' => PurchaseReturnStatus::POSTED->value,
                'posted_by' => $actorId,
                'posted_at' => now(),
            ]);

            return $lockedReturn->refresh();
        });
    }

    public function cancelReturn(PurchaseReturn $purchaseReturn, ?int $userId = null): PurchaseReturn
    {
        $this->resolveActorId($userId);

        return DB::transaction(function () use ($purchaseReturn): PurchaseReturn {
            $lockedReturn = PurchaseReturn::query()
                ->lockForUpdate()
                ->findOrFail($purchaseReturn->id);

            if ($lockedReturn->status !== PurchaseReturnStatus::DRAFT) {
                throw new \DomainException('Only draft purchase return can be cancelled.');
            }

            $lockedReturn->update([
                'status' => PurchaseReturnStatus::CANCELLED->value,
            ]);

            return $lockedReturn->refresh();
        });
    }

    /**
     * @param  int[]  $stockReceiveItemIds
     * @return array<int, float>
     */
    public function postedReturnedQtyMap(array $stockReceiveItemIds, ?int $excludePurchaseReturnId = null): array
    {
        $ids = collect($stockReceiveItemIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return PurchaseReturnItem::query()
            ->selectRaw('stock_receive_item_id, SUM(quantity) as returned_quantity')
            ->whereIn('stock_receive_item_id', $ids)
            ->whereHas('purchaseReturn', function ($query) use ($excludePurchaseReturnId): void {
                $query->where('status', PurchaseReturnStatus::POSTED->value);

                if ($excludePurchaseReturnId) {
                    $query->where('id', '!=', $excludePurchaseReturnId);
                }
            })
            ->groupBy('stock_receive_item_id')
            ->pluck('returned_quantity', 'stock_receive_item_id')
            ->map(fn ($qty): float => round((float) $qty, 3))
            ->all();
    }

    public function calculateReturnableQty(float $originalQuantity, float $alreadyReturnedQuantity): float
    {
        return max(0.0, round($originalQuantity - $alreadyReturnedQuantity, 3));
    }

    public function calculateMaxReturnQty(float $returnableQuantity, float $availableQuantity): float
    {
        return max(0.0, round(min($returnableQuantity, $availableQuantity), 3));
    }

    public function availableQty(int $storeId, int $productId): float
    {
        return round($this->stockService->getAvailableQty($storeId, $productId), 3);
    }

    public function validateDraftBeforePost(PurchaseReturn $purchaseReturn): void
    {
        if (! $purchaseReturn->supplier_id || ! $purchaseReturn->store_id || ! $purchaseReturn->stock_receive_id) {
            throw new \DomainException('Supplier, store, and stock receive are required before posting.');
        }

        if (! $purchaseReturn->store || $purchaseReturn->store->type !== StoreType::OFFICE) {
            throw new \DomainException('Purchase return is allowed only for office stores.');
        }

        if ($purchaseReturn->items->isEmpty()) {
            throw new \DomainException('At least one return item is required before posting.');
        }

        $stockReceive = $purchaseReturn->stockReceive;

        if (! $stockReceive) {
            throw new \DomainException('Linked stock receive not found.');
        }

        if ($stockReceive->status !== StockReceiveStatus::POSTED) {
            throw new \DomainException('Purchase return can be posted only against a posted stock receive.');
        }

        if ((int) $stockReceive->supplier_id !== (int) $purchaseReturn->supplier_id) {
            throw new \DomainException('Selected stock receive does not belong to the selected supplier.');
        }

        if ((int) $stockReceive->store_id !== (int) $purchaseReturn->store_id) {
            throw new \DomainException('Selected stock receive does not belong to the selected store.');
        }

        if ($purchaseReturn->purchase_order_id && $stockReceive->purchase_order_id && (int) $purchaseReturn->purchase_order_id !== (int) $stockReceive->purchase_order_id) {
            throw new \DomainException('Linked purchase order does not match the selected stock receive.');
        }

        if (! $purchaseReturn->purchase_order_id && $stockReceive->purchase_order_id) {
            $purchaseReturn->update([
                'purchase_order_id' => (int) $stockReceive->purchase_order_id,
            ]);
        }
    }

    protected function resolveActorId(?int $userId): int
    {
        $actorId = $userId ?? (int) Auth::id();

        if ($actorId <= 0) {
            throw new \DomainException('A valid user is required for purchase return actions.');
        }

        return $actorId;
    }
}
