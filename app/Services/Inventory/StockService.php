<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function getAvailableQty(int $storeId, int $productId): float
    {
        return (float) StockBalance::query()
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->value('quantity');
    }

    public function getAverageRate(int $storeId, int $productId): float
    {
        return (float) StockBalance::query()
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->value('avg_unit_price');
    }

    public function increaseBalance(int $storeId, int $productId, float $quantity, float $unitRate): StockBalance
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($storeId, $productId, $quantity, $unitRate): StockBalance {
            $balance = $this->getOrCreateBalance($storeId, $productId, true);

            $oldQty = (float) $balance->quantity;
            $oldTotal = (float) $balance->total_value;
            $incomingValue = $this->roundPrice($quantity * $unitRate);

            $newQty = $this->roundQty($oldQty + $quantity);
            $newTotal = $this->roundPrice($oldTotal + $incomingValue);
            $newAvg = $newQty > 0 ? $this->roundPrice($newTotal / $newQty) : 0.0;

            $balance->update([
                'quantity' => $newQty,
                'avg_unit_price' => $newAvg,
                'total_value' => $newTotal,
            ]);

            return $balance->refresh();
        });
    }

    /**
     * @return array{balance: StockBalance, unit_rate: float, total_price: float}
     */
    public function decreaseBalance(int $storeId, int $productId, float $quantity, ?float $unitRate = null): array
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($storeId, $productId, $quantity, $unitRate): array {
            $balance = $this->getOrCreateBalance($storeId, $productId, true);

            $oldQty = (float) $balance->quantity;
            $oldAvg = (float) $balance->avg_unit_price;
            $oldTotal = (float) $balance->total_value;

            if ($oldQty < $quantity) {
                throw new \DomainException('Insufficient stock for the selected item.');
            }

            $rate = $this->roundPrice($unitRate ?? $oldAvg);
            $outValue = $this->roundPrice($quantity * $rate);

            $newQty = $this->roundQty($oldQty - $quantity);
            $newTotal = $this->roundPrice(max(0, $oldTotal - $outValue));
            $newAvg = $newQty > 0 ? $this->roundPrice($newTotal / $newQty) : 0.0;

            $balance->update([
                'quantity' => $newQty,
                'avg_unit_price' => $newAvg,
                'total_value' => $newTotal,
            ]);

            return [
                'balance' => $balance->refresh(),
                'unit_rate' => $rate,
                'total_price' => $outValue,
            ];
        });
    }

    /**
     * @param  array{
     *   movement_date?: Carbon|string|null,
     *   product_id: int,
     *   store_id: int,
     *   project_id?: int|null,
     *   supplier_id?: int|null,
     *   direction: StockMovementDirection|string,
     *   movement_type: StockMovementType|string,
     *   quantity: float,
     *   unit_price: float,
     *   total_price: float,
     *   balance_after?: float|null,
     *   reference_type?: string|null,
     *   reference_id?: int|null,
     *   reference_no?: string|null,
     *   remarks?: string|null,
     *   created_by?: int|null
     * }  $payload
     */
    public function createMovement(array $payload): StockMovement
    {
        return StockMovement::query()->create([
            'movement_date' => $payload['movement_date'] ?? now(),
            'product_id' => $payload['product_id'],
            'store_id' => $payload['store_id'],
            'project_id' => $payload['project_id'] ?? null,
            'supplier_id' => $payload['supplier_id'] ?? null,
            'direction' => $payload['direction'] instanceof StockMovementDirection ? $payload['direction']->value : $payload['direction'],
            'movement_type' => $payload['movement_type'] instanceof StockMovementType ? $payload['movement_type']->value : $payload['movement_type'],
            'quantity' => $this->roundQty((float) $payload['quantity']),
            'unit_price' => $this->roundPrice((float) $payload['unit_price']),
            'total_price' => $this->roundPrice((float) $payload['total_price']),
            'balance_after' => isset($payload['balance_after']) ? $this->roundQty((float) $payload['balance_after']) : null,
            'reference_type' => $payload['reference_type'] ?? null,
            'reference_id' => $payload['reference_id'] ?? null,
            'reference_no' => $payload['reference_no'] ?? null,
            'remarks' => $payload['remarks'] ?? null,
            'created_by' => $payload['created_by'] ?? null,
        ]);
    }

    protected function getOrCreateBalance(int $storeId, int $productId, bool $forUpdate = false): StockBalance
    {
        $query = StockBalance::query()
            ->where('store_id', $storeId)
            ->where('product_id', $productId);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        $balance = $query->first();

        if ($balance) {
            return $balance;
        }

        return StockBalance::query()->create([
            'store_id' => $storeId,
            'product_id' => $productId,
            'quantity' => 0,
            'avg_unit_price' => 0,
            'total_value' => 0,
        ]);
    }

    protected function roundQty(float $value): float
    {
        return round($value, 3);
    }

    protected function roundPrice(float $value): float
    {
        return round($value, 2);
    }
}
