<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\StockConsumptionStatus;
use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use App\Models\StockConsumption;
use Illuminate\Support\Facades\DB;

class StockConsumptionService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function postConsumption(StockConsumption $stockConsumption, int $userId): StockConsumption
    {
        return DB::transaction(function () use ($stockConsumption, $userId): StockConsumption {
            $lockedConsumption = StockConsumption::query()
                ->with(['store', 'items', 'items.product'])
                ->lockForUpdate()
                ->findOrFail($stockConsumption->id);

            if ($lockedConsumption->status === StockConsumptionStatus::POSTED) {
                throw new \DomainException('This consumption is already posted.');
            }

            if ($lockedConsumption->status === StockConsumptionStatus::CANCELLED) {
                throw new \DomainException('Cancelled consumption cannot be posted.');
            }

            if ($lockedConsumption->items->isEmpty()) {
                throw new \DomainException('At least one item is required before posting consumption.');
            }

            foreach ($lockedConsumption->items as $item) {
                $quantity = (float) $item->quantity;

                $decreased = $this->stockService->decreaseBalance(
                    storeId: (int) $lockedConsumption->store_id,
                    productId: (int) $item->product_id,
                    quantity: $quantity
                );

                $balance = $decreased['balance'];
                $unitRate = $decreased['unit_rate'];
                $totalPrice = $decreased['total_price'];

                $item->update([
                    'unit_price' => $unitRate,
                    'total_price' => $totalPrice,
                ]);

                $this->stockService->createMovement([
                    'movement_date' => $lockedConsumption->consumption_date,
                    'product_id' => (int) $item->product_id,
                    'store_id' => (int) $lockedConsumption->store_id,
                    'project_id' => $lockedConsumption->project_id ?: $lockedConsumption->store?->project_id,
                    'direction' => StockMovementDirection::OUT,
                    'movement_type' => StockMovementType::CONSUMPTION,
                    'quantity' => $quantity,
                    'unit_price' => $unitRate,
                    'total_price' => $totalPrice,
                    'balance_after' => (float) $balance->quantity,
                    'reference_type' => StockConsumption::class,
                    'reference_id' => (int) $lockedConsumption->id,
                    'reference_no' => $lockedConsumption->consumption_no,
                    'remarks' => $item->remarks ?: $lockedConsumption->remarks,
                    'created_by' => $userId,
                ]);
            }

            $lockedConsumption->update([
                'status' => StockConsumptionStatus::POSTED->value,
                'posted_by' => $userId,
                'posted_at' => now(),
                'project_id' => $lockedConsumption->project_id ?: $lockedConsumption->store?->project_id,
            ]);

            return $lockedConsumption->refresh();
        });
    }
}
