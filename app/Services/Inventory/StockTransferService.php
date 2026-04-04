<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\StockMovementDirection;
use App\Enums\Inventory\StockMovementType;
use App\Enums\Inventory\TransferStatus;
use App\Models\TransferTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function generateTransferNo(): string
    {
        $lastId = (int) TransferTransaction::query()->max('id');

        return 'ST-'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    public function requestTransfer(TransferTransaction $transferTransaction, ?int $userId = null): TransferTransaction
    {
        $actorId = $this->resolveActorId($userId);

        return DB::transaction(function () use ($transferTransaction, $actorId): TransferTransaction {
            $lockedTransfer = TransferTransaction::query()
                ->with(['senderStore', 'receiverStore', 'items.product'])
                ->lockForUpdate()
                ->findOrFail($transferTransaction->id);

            if ($lockedTransfer->status !== TransferStatus::DRAFT) {
                throw new \DomainException('Only draft transfer can be requested.');
            }

            $this->validateTransferData($lockedTransfer);
            $this->validateStockAvailability($lockedTransfer);

            $lockedTransfer->update([
                'status' => TransferStatus::REQUESTED->value,
                'requested_by' => $actorId,
                'requested_at' => now(),
            ]);

            return $lockedTransfer->refresh();
        });
    }

    public function approveTransfer(TransferTransaction $transferTransaction, ?int $userId = null): TransferTransaction
    {
        $actorId = $this->resolveActorId($userId);

        return DB::transaction(function () use ($transferTransaction, $actorId): TransferTransaction {
            $lockedTransfer = TransferTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transferTransaction->id);

            if ($lockedTransfer->status !== TransferStatus::REQUESTED) {
                throw new \DomainException('Only requested transfer can be approved.');
            }

            $lockedTransfer->update([
                'status' => TransferStatus::APPROVED->value,
                'approved_by' => $actorId,
                'approved_at' => now(),
            ]);

            return $lockedTransfer->refresh();
        });
    }

    public function completeTransfer(TransferTransaction $transferTransaction, ?int $userId = null): TransferTransaction
    {
        $actorId = $this->resolveActorId($userId);

        return DB::transaction(function () use ($transferTransaction, $actorId): TransferTransaction {
            $lockedTransfer = TransferTransaction::query()
                ->with(['senderStore', 'receiverStore', 'items', 'items.product'])
                ->lockForUpdate()
                ->findOrFail($transferTransaction->id);

            if ($lockedTransfer->status === TransferStatus::COMPLETED) {
                throw new \DomainException('Transfer is already completed.');
            }

            if ($lockedTransfer->status !== TransferStatus::APPROVED) {
                throw new \DomainException('Only approved transfer can be completed.');
            }

            $this->validateTransferData($lockedTransfer);
            $this->validateStockAvailability($lockedTransfer);

            foreach ($lockedTransfer->items as $item) {
                $quantity = (float) ($item->received_quantity ?: $item->quantity);

                if ($quantity <= 0) {
                    throw new \DomainException('Transfer quantity must be greater than zero.');
                }

                $sourceAvgRate = $this->stockService->getAverageRate(
                    storeId: (int) $lockedTransfer->sender_store_id,
                    productId: (int) $item->product_id
                );

                $decreased = $this->stockService->decreaseBalance(
                    storeId: (int) $lockedTransfer->sender_store_id,
                    productId: (int) $item->product_id,
                    quantity: $quantity,
                    unitRate: $sourceAvgRate
                );

                $sourceBalance = $decreased['balance'];
                $unitRate = (float) $decreased['unit_rate'];
                $totalPrice = (float) $decreased['total_price'];

                $destinationBalance = $this->stockService->increaseBalance(
                    storeId: (int) $lockedTransfer->receiver_store_id,
                    productId: (int) $item->product_id,
                    quantity: $quantity,
                    unitRate: $unitRate
                );

                $item->update([
                    'received_quantity' => $quantity,
                    'unit_price' => $unitRate,
                    'total_price' => $totalPrice,
                    'checked_by_sender_at' => $item->checked_by_sender_at ?: now(),
                    'checked_by_receiver_at' => $item->checked_by_receiver_at ?: now(),
                ]);

                $this->stockService->createMovement([
                    'movement_date' => $lockedTransfer->transfer_date,
                    'product_id' => (int) $item->product_id,
                    'store_id' => (int) $lockedTransfer->sender_store_id,
                    'project_id' => $lockedTransfer->senderStore?->project_id,
                    'direction' => StockMovementDirection::OUT,
                    'movement_type' => StockMovementType::TRANSFER_OUT,
                    'quantity' => $quantity,
                    'unit_price' => $unitRate,
                    'total_price' => $totalPrice,
                    'balance_after' => (float) $sourceBalance->quantity,
                    'reference_type' => 'transfer',
                    'reference_id' => (int) $lockedTransfer->id,
                    'reference_no' => $lockedTransfer->transfer_no,
                    'remarks' => $item->remarks ?: $lockedTransfer->remarks,
                    'created_by' => $actorId,
                ]);

                $this->stockService->createMovement([
                    'movement_date' => $lockedTransfer->transfer_date,
                    'product_id' => (int) $item->product_id,
                    'store_id' => (int) $lockedTransfer->receiver_store_id,
                    'project_id' => $lockedTransfer->receiverStore?->project_id,
                    'direction' => StockMovementDirection::IN,
                    'movement_type' => StockMovementType::TRANSFER_IN,
                    'quantity' => $quantity,
                    'unit_price' => $unitRate,
                    'total_price' => $totalPrice,
                    'balance_after' => (float) $destinationBalance->quantity,
                    'reference_type' => 'transfer',
                    'reference_id' => (int) $lockedTransfer->id,
                    'reference_no' => $lockedTransfer->transfer_no,
                    'remarks' => $item->remarks ?: $lockedTransfer->remarks,
                    'created_by' => $actorId,
                ]);
            }

            $lockedTransfer->update([
                'status' => TransferStatus::COMPLETED->value,
                'received_by' => $actorId,
                'received_at' => now(),
            ]);

            app(StockRequestService::class)->recalculateLinkedRequestsForTransfer($lockedTransfer->refresh(), $actorId);

            return $lockedTransfer->refresh();
        });
    }

    public function cancelTransfer(TransferTransaction $transferTransaction, ?int $userId = null): TransferTransaction
    {
        $this->resolveActorId($userId);

        return DB::transaction(function () use ($transferTransaction): TransferTransaction {
            $lockedTransfer = TransferTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transferTransaction->id);

            if (! in_array($lockedTransfer->status, [
                TransferStatus::DRAFT,
                TransferStatus::REQUESTED,
                TransferStatus::APPROVED,
            ], true)) {
                throw new \DomainException('Only draft, requested, or approved transfer can be cancelled.');
            }

            $lockedTransfer->update([
                'status' => TransferStatus::CANCELLED->value,
            ]);

            return $lockedTransfer->refresh();
        });
    }

    protected function validateTransferData(TransferTransaction $transferTransaction): void
    {
        if ((int) $transferTransaction->sender_store_id === (int) $transferTransaction->receiver_store_id) {
            throw new \DomainException('Sender and receiver stores cannot be the same.');
        }

        if (! $transferTransaction->senderStore || ! $transferTransaction->receiverStore) {
            throw new \DomainException('Selected sender or receiver store is invalid.');
        }

        if ($transferTransaction->items->isEmpty()) {
            throw new \DomainException('At least one transfer item is required.');
        }

        foreach ($transferTransaction->items as $item) {
            $quantity = (float) ($item->received_quantity ?: $item->quantity);

            if ($quantity <= 0) {
                throw new \DomainException('Transfer item quantity must be greater than zero.');
            }
        }
    }

    protected function validateStockAvailability(TransferTransaction $transferTransaction): void
    {
        $requiredByProduct = [];
        $productNames = [];

        foreach ($transferTransaction->items as $item) {
            $quantity = (float) ($item->received_quantity ?: $item->quantity);
            $productId = (int) $item->product_id;

            $requiredByProduct[$productId] = ($requiredByProduct[$productId] ?? 0) + $quantity;
            $productNames[$productId] = $item->product?->name ?? 'Selected product';
        }

        foreach ($requiredByProduct as $productId => $requiredQty) {
            $availableQty = $this->stockService->getAvailableQty(
                storeId: (int) $transferTransaction->sender_store_id,
                productId: (int) $productId
            );

            if ($availableQty < $requiredQty) {
                throw new \DomainException(
                    ($productNames[$productId] ?? 'Selected product').' has insufficient stock in sender store. Available: '
                    .number_format($availableQty, 3).', Required: '.number_format($requiredQty, 3).'.'
                );
            }
        }
    }

    protected function resolveActorId(?int $userId): int
    {
        $actorId = $userId ?? (int) Auth::id();

        if ($actorId <= 0) {
            throw new \DomainException('A valid user is required for transfer workflow actions.');
        }

        return $actorId;
    }
}
