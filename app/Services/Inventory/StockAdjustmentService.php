<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\StockAdjustmentStatus;
use App\Enums\Inventory\StockAdjustmentType;
use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function generateAdjustmentNo(): string
    {
        $lastId = (int) StockAdjustment::query()->max('id');

        return 'SA-'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    public function postAdjustment(StockAdjustment $stockAdjustment, ?int $userId = null): StockAdjustment
    {
        $actorId = $this->resolveActorId($userId);

        return DB::transaction(function () use ($stockAdjustment, $actorId): StockAdjustment {
            $lockedAdjustment = StockAdjustment::query()
                ->with(['store', 'items', 'items.product'])
                ->lockForUpdate()
                ->findOrFail($stockAdjustment->id);

            if ($lockedAdjustment->status === StockAdjustmentStatus::POSTED) {
                throw new \DomainException('This adjustment is already posted.');
            }

            if ($lockedAdjustment->status === StockAdjustmentStatus::CANCELLED) {
                throw new \DomainException('Cancelled adjustment cannot be posted.');
            }

            if ($lockedAdjustment->items->isEmpty()) {
                throw new \DomainException('At least one item is required before posting.');
            }

            if ($lockedAdjustment->adjustment_type === StockAdjustmentType::OUT) {
                $this->validateStockAvailability($lockedAdjustment);
            }

            foreach ($lockedAdjustment->items as $item) {
                $quantity = (float) $item->quantity;

                if ($quantity <= 0) {
                    throw new \DomainException('Adjustment quantity must be greater than zero.');
                }

                if ($lockedAdjustment->adjustment_type === StockAdjustmentType::IN) {
                    $unitRate = round((float) $item->unit_price, 2);
                    $totalPrice = round($quantity * $unitRate, 2);

                    $balance = $this->stockService->increaseBalance(
                        storeId: (int) $lockedAdjustment->store_id,
                        productId: (int) $item->product_id,
                        quantity: $quantity,
                        unitRate: $unitRate
                    );

                    $item->update([
                        'unit_price' => $unitRate,
                        'total_price' => $totalPrice,
                    ]);

                    $this->stockService->createMovement([
                        'movement_date' => $lockedAdjustment->adjustment_date,
                        'product_id' => (int) $item->product_id,
                        'store_id' => (int) $lockedAdjustment->store_id,
                        'project_id' => $lockedAdjustment->store?->project_id,
                        'direction' => StockMovementDirection::IN,
                        'movement_type' => StockMovementType::ADJUSTMENT_IN,
                        'quantity' => $quantity,
                        'unit_price' => $unitRate,
                        'total_price' => $totalPrice,
                        'balance_after' => (float) $balance->quantity,
                        'reference_type' => 'stock_adjustment',
                        'reference_id' => (int) $lockedAdjustment->id,
                        'reference_no' => $lockedAdjustment->adjustment_no,
                        'remarks' => $item->remarks ?: $lockedAdjustment->remarks,
                        'created_by' => $actorId,
                    ]);

                    continue;
                }

                $currentAvgRate = $this->stockService->getAverageRate(
                    storeId: (int) $lockedAdjustment->store_id,
                    productId: (int) $item->product_id
                );

                $decreased = $this->stockService->decreaseBalance(
                    storeId: (int) $lockedAdjustment->store_id,
                    productId: (int) $item->product_id,
                    quantity: $quantity,
                    unitRate: $currentAvgRate
                );

                $balance = $decreased['balance'];
                $unitRate = (float) $decreased['unit_rate'];
                $totalPrice = (float) $decreased['total_price'];

                $item->update([
                    'unit_price' => $unitRate,
                    'total_price' => $totalPrice,
                ]);

                $this->stockService->createMovement([
                    'movement_date' => $lockedAdjustment->adjustment_date,
                    'product_id' => (int) $item->product_id,
                    'store_id' => (int) $lockedAdjustment->store_id,
                    'project_id' => $lockedAdjustment->store?->project_id,
                    'direction' => StockMovementDirection::OUT,
                    'movement_type' => StockMovementType::ADJUSTMENT_OUT,
                    'quantity' => $quantity,
                    'unit_price' => $unitRate,
                    'total_price' => $totalPrice,
                    'balance_after' => (float) $balance->quantity,
                    'reference_type' => 'stock_adjustment',
                    'reference_id' => (int) $lockedAdjustment->id,
                    'reference_no' => $lockedAdjustment->adjustment_no,
                    'remarks' => $item->remarks ?: $lockedAdjustment->remarks,
                    'created_by' => $actorId,
                ]);
            }

            $lockedAdjustment->update([
                'status' => StockAdjustmentStatus::POSTED->value,
                'posted_by' => $actorId,
                'posted_at' => now(),
            ]);

            return $lockedAdjustment->refresh();
        });
    }

    public function cancelAdjustment(StockAdjustment $stockAdjustment, ?int $userId = null): StockAdjustment
    {
        $this->resolveActorId($userId);

        return DB::transaction(function () use ($stockAdjustment): StockAdjustment {
            $lockedAdjustment = StockAdjustment::query()
                ->lockForUpdate()
                ->findOrFail($stockAdjustment->id);

            if ($lockedAdjustment->status !== StockAdjustmentStatus::DRAFT) {
                throw new \DomainException('Only draft adjustment can be cancelled.');
            }

            $lockedAdjustment->update([
                'status' => StockAdjustmentStatus::CANCELLED->value,
            ]);

            return $lockedAdjustment->refresh();
        });
    }

    protected function validateStockAvailability(StockAdjustment $stockAdjustment): void
    {
        $requiredByProduct = [];
        $productNames = [];

        foreach ($stockAdjustment->items as $item) {
            $productId = (int) $item->product_id;
            $quantity = (float) $item->quantity;

            $requiredByProduct[$productId] = ($requiredByProduct[$productId] ?? 0) + $quantity;
            $productNames[$productId] = $item->product?->name ?? 'Selected product';
        }

        foreach ($requiredByProduct as $productId => $requiredQty) {
            $availableQty = $this->stockService->getAvailableQty(
                storeId: (int) $stockAdjustment->store_id,
                productId: $productId
            );

            if ($availableQty < $requiredQty) {
                throw new \DomainException(
                    ($productNames[$productId] ?? 'Selected product').' has insufficient stock for adjustment out. Available: '
                    .number_format($availableQty, 3).', Required: '.number_format($requiredQty, 3).'.'
                );
            }
        }
    }

    protected function resolveActorId(?int $userId): int
    {
        $actorId = $userId ?? (int) Auth::id();

        if ($actorId <= 0) {
            throw new \DomainException('A valid user is required for stock adjustment actions.');
        }

        return $actorId;
    }
}
