<?php

namespace App\Services\Inventory;

use App\Accounting\PostingContext;
use App\Enums\Accounts\EntryMethod;
use App\Enums\Inventory\StockConsumptionStatus;
use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use App\Models\StockConsumption;
use App\Services\Accounts\PostingEngine;
use App\Services\Accounts\TransactionService;
use Illuminate\Support\Facades\DB;

class StockConsumptionService
{
    public function __construct(
        protected StockService $stockService,
        protected TransactionService $transactionService,
    ) {}

    public function postConsumption(StockConsumption $stockConsumption, int $userId, ?string $workPhase = null): StockConsumption
    {
        // dd($workPhase);
        return DB::transaction(function () use ($stockConsumption, $userId, $workPhase): StockConsumption {
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

                            // Link transaction to project so it appears in project expenses view
            $projectId = $lockedConsumption->project_id ?: $lockedConsumption->store?->project_id;

                $this->stockService->createMovement([
                    'movement_date' => $lockedConsumption->consumption_date,
                    'product_id' => (int) $item->product_id,
                    'store_id' => (int) $lockedConsumption->store_id,
                    'project_id' => $projectId,
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

            $totalValue = $lockedConsumption->items->sum('total_price');

            $txn = app(PostingEngine::class)->record(
                'inventory.material_consumption',
                new PostingContext(
                    amount: $totalValue,
                    datetime: $lockedConsumption->consumption_date->format('Y-m-d') . ' 00:00:00',
                    referenceType: \App\Models\Project::class,
                    referenceId: (int) $projectId,
                    referenceNo: $lockedConsumption->consumption_no,
                    notes: 'Material Consumption #' . $lockedConsumption->consumption_no,
                    actorId: $userId,
                    method: EntryMethod::JOURNAL->value,

                ),
            );

            // Link transaction to project so it appears in project expenses view
            if ($projectId) {
                $externalData = [
                    'stock_consumption_id' => (int) $lockedConsumption->id,
                    'stock_consumption_no' => $lockedConsumption->consumption_no,
                    'store_id' => (int) $lockedConsumption->store_id,
                    'store_name' => $lockedConsumption->store?->name,
                    'consumption_date' => $lockedConsumption->consumption_date->format('Y-m-d'),
                    'items_count' => $lockedConsumption->items->count(),
                    'project_work_phase' => $workPhase,
                ];

                $txn->update([
                    'reference_type' => \App\Models\Project::class,
                    'reference_id' => (int) $projectId,
                    'external_data' => $externalData,
                ]);
            }

            $lockedConsumption->update([
                'status' => StockConsumptionStatus::POSTED->value,
                'posted_by' => $userId,
                'posted_at' => now(),
                'project_id' => $projectId,
                'transaction_id' => $txn->id,
            ]);

            return $lockedConsumption->refresh();
        });
    }

    public function cancelConsumption(StockConsumption $stockConsumption, int $userId): StockConsumption
    {
        return DB::transaction(function () use ($stockConsumption, $userId): StockConsumption {
            $locked = StockConsumption::query()
                ->with(['store', 'items', 'items.product', 'transaction'])
                ->lockForUpdate()
                ->findOrFail($stockConsumption->id);

            if ($locked->status !== StockConsumptionStatus::POSTED) {
                throw new \DomainException('Only posted consumption can be cancelled.');
            }

            foreach ($locked->items as $item) {
                $this->stockService->increaseBalance(
                    storeId: (int) $locked->store_id,
                    productId: (int) $item->product_id,
                    quantity: (float) $item->quantity,
                    unitRate: (float) $item->unit_price,
                );

                $this->stockService->createMovement([
                    'movement_date' => now()->toDateString(),
                    'product_id' => (int) $item->product_id,
                    'store_id' => (int) $locked->store_id,
                    'project_id' => $locked->project_id,
                    'direction' => StockMovementDirection::IN,
                    'movement_type' => StockMovementType::CONSUMPTION_REVERSAL,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'balance_after' => 0,
                    'reference_type' => StockConsumption::class,
                    'reference_id' => (int) $locked->id,
                    'reference_no' => $locked->consumption_no,
                    'remarks' => 'Cancellation of ' . $locked->consumption_no,
                    'created_by' => $userId,
                ]);
            }

            if ($locked->transaction) {
                $reversal = $this->transactionService->reverse(
                    $locked->transaction,
                    $userId,
                    'Cancellation of Material Consumption #' . $locked->consumption_no,
                );

                // Link reversal transaction to project with external_data
                $reversalExternalData = $locked->transaction->external_data ?? [];
                $reversalExternalData['cancelled_at'] = now()->format('Y-m-d H:i:s');
                $reversalExternalData['cancelled_by_user_id'] = $userId;

                $reversal->update([
                    'reference_type' => \App\Models\Project::class,
                    'reference_id' => (int) $locked->project_id,
                    'external_data' => $reversalExternalData,
                ]);
            }

            $locked->update(['status' => StockConsumptionStatus::CANCELLED]);

            return $locked->refresh();
        });
    }
}
