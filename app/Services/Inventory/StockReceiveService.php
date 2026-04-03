<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use App\Enums\Inventory\StockReceiveStatus;
use App\Enums\Inventory\StoreType;
use App\Models\StockReceive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockReceiveService
{
    public function __construct(
        protected StockService $stockService
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
                'posted_by' => $actorId,
                'posted_at' => now(),
            ]);

            return $lockedReceive->refresh();
        });
    }
}
