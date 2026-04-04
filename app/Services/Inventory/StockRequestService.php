<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\StockRequestStatus;
use App\Enums\Inventory\TransferStatus;
use App\Models\StockRequest;
use App\Models\StockRequestTransferLink;
use App\Models\TransferTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockRequestService
{
    public function generateRequestNo(): string
    {
        $lastId = (int) StockRequest::query()->max('id');

        return 'SRQ-'.str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    public function submitRequest(StockRequest $stockRequest, ?int $userId = null): StockRequest
    {
        $actorId = $this->resolveActorId($userId);

        return DB::transaction(function () use ($stockRequest, $actorId): StockRequest {
            $lockedRequest = StockRequest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($stockRequest->id);

            if ($lockedRequest->status !== StockRequestStatus::DRAFT) {
                throw new \DomainException('Only draft stock request can be submitted.');
            }

            if ($lockedRequest->items->isEmpty()) {
                throw new \DomainException('At least one item is required before submission.');
            }

            $lockedRequest->update([
                'status' => StockRequestStatus::PENDING->value,
                'requested_by' => $actorId,
            ]);

            return $lockedRequest->refresh();
        });
    }

    /**
     * @param  array<int, float|int|string>|null  $approvedQuantities
     */
    public function approveRequest(
        StockRequest $stockRequest,
        ?int $userId = null,
        ?array $approvedQuantities = null,
        ?string $remarks = null
    ): StockRequest {
        $actorId = $this->resolveActorId($userId);

        return DB::transaction(function () use ($stockRequest, $actorId, $approvedQuantities, $remarks): StockRequest {
            $lockedRequest = StockRequest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($stockRequest->id);

            if ($lockedRequest->status !== StockRequestStatus::PENDING) {
                throw new \DomainException('Only pending stock request can be approved.');
            }

            if ($lockedRequest->items->isEmpty()) {
                throw new \DomainException('Stock request has no items to approve.');
            }

            $hasPositiveApprovedQty = false;

            foreach ($lockedRequest->items as $item) {
                $requestedQty = round((float) $item->quantity, 3);
                $approvedQty = $this->resolveApprovedQuantity(
                    itemId: (int) $item->id,
                    defaultValue: (float) ($item->approved_quantity ?? $requestedQty),
                    approvedQuantities: $approvedQuantities
                );

                if ($approvedQty < 0) {
                    throw new \DomainException('Approved quantity cannot be negative.');
                }

                if ($approvedQty > $requestedQty + 0.0001) {
                    throw new \DomainException(
                        ($item->product?->name ?? 'Selected item').
                        ' approved quantity cannot exceed requested quantity.'
                    );
                }

                if ($approvedQty > 0) {
                    $hasPositiveApprovedQty = true;
                }

                $item->update([
                    'approved_quantity' => $approvedQty,
                ]);
            }

            if (! $hasPositiveApprovedQty) {
                throw new \DomainException('At least one approved quantity must be greater than zero.');
            }

            $lockedRequest->update([
                'status' => StockRequestStatus::APPROVED->value,
                'approved_by' => $actorId,
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'remarks' => $this->appendWorkflowRemarks(
                    existingRemarks: $lockedRequest->remarks,
                    workflowLabel: 'Approval Note',
                    note: $remarks
                ),
            ]);

            return $this->recalculateFulfillmentStatus($lockedRequest, $actorId);
        });
    }

    public function rejectRequest(StockRequest $stockRequest, ?int $userId = null, ?string $remarks = null): StockRequest
    {
        $actorId = $this->resolveActorId($userId);

        return DB::transaction(function () use ($stockRequest, $actorId, $remarks): StockRequest {
            $lockedRequest = StockRequest::query()
                ->lockForUpdate()
                ->findOrFail($stockRequest->id);

            if ($lockedRequest->status !== StockRequestStatus::PENDING) {
                throw new \DomainException('Only pending stock request can be rejected.');
            }

            $lockedRequest->update([
                'status' => StockRequestStatus::REJECTED->value,
                'rejected_by' => $actorId,
                'rejected_at' => now(),
                'remarks' => $this->appendWorkflowRemarks(
                    existingRemarks: $lockedRequest->remarks,
                    workflowLabel: 'Rejection Note',
                    note: $remarks
                ),
            ]);

            return $lockedRequest->refresh();
        });
    }

    public function cancelRequest(StockRequest $stockRequest, ?int $userId = null): StockRequest
    {
        $this->resolveActorId($userId);

        return DB::transaction(function () use ($stockRequest): StockRequest {
            $lockedRequest = StockRequest::query()
                ->lockForUpdate()
                ->findOrFail($stockRequest->id);

            if (! in_array($lockedRequest->status, [
                StockRequestStatus::DRAFT,
                StockRequestStatus::PENDING,
                StockRequestStatus::APPROVED,
            ], true)) {
                throw new \DomainException('Only draft, pending, or approved stock request can be cancelled.');
            }

            $lockedRequest->update([
                'status' => StockRequestStatus::CANCELLED->value,
            ]);

            return $lockedRequest->refresh();
        });
    }

    public function markFulfilled(StockRequest $stockRequest, ?int $userId = null): StockRequest
    {
        $actorId = $this->resolveActorId($userId);
        $refreshed = $this->recalculateFulfillmentStatus($stockRequest, $actorId);

        if ($refreshed->status !== StockRequestStatus::FULFILLED) {
            throw new \DomainException('This stock request is not fully fulfilled yet.');
        }

        return $refreshed;
    }

    public function recalculateFulfillmentStatus(StockRequest $stockRequest, ?int $userId = null): StockRequest
    {
        return DB::transaction(function () use ($stockRequest, $userId): StockRequest {
            $lockedRequest = StockRequest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($stockRequest->id);

            if (in_array($lockedRequest->status, [
                StockRequestStatus::DRAFT,
                StockRequestStatus::PENDING,
                StockRequestStatus::REJECTED,
                StockRequestStatus::CANCELLED,
            ], true)) {
                return $lockedRequest->refresh();
            }

            $completedQtyByProduct = $this->completedTransferQtyByProduct((int) $lockedRequest->id);
            $remainingByProduct = $completedQtyByProduct;

            $totalTargetQty = 0.0;
            $totalFulfilledQty = 0.0;

            foreach ($lockedRequest->items()->orderBy('id')->get() as $item) {
                $targetQty = round((float) ($item->approved_quantity ?? $item->quantity), 3);
                $targetQty = max(0.0, $targetQty);

                $productId = (int) $item->product_id;
                $availableQty = round((float) ($remainingByProduct[$productId] ?? 0), 3);
                $fulfilledQty = round(min($targetQty, $availableQty), 3);

                $remainingByProduct[$productId] = round(max(0, $availableQty - $fulfilledQty), 3);

                $item->update([
                    'fulfilled_quantity' => $fulfilledQty,
                ]);

                $totalTargetQty += $targetQty;
                $totalFulfilledQty += $fulfilledQty;
            }

            $nextStatus = StockRequestStatus::APPROVED;

            if ($totalTargetQty > 0.0001) {
                if ($totalFulfilledQty <= 0.0001) {
                    $nextStatus = StockRequestStatus::APPROVED;
                } elseif ($totalFulfilledQty + 0.0001 < $totalTargetQty) {
                    $nextStatus = StockRequestStatus::PARTIALLY_FULFILLED;
                } else {
                    $nextStatus = StockRequestStatus::FULFILLED;
                }
            }

            $updateData = [
                'status' => $nextStatus->value,
            ];

            if ($nextStatus === StockRequestStatus::FULFILLED) {
                $updateData['fulfilled_by'] = $userId ?: $lockedRequest->fulfilled_by;
                $updateData['fulfilled_at'] = $lockedRequest->fulfilled_at ?: now();
            } else {
                $updateData['fulfilled_by'] = null;
                $updateData['fulfilled_at'] = null;
            }

            $lockedRequest->update($updateData);

            return $lockedRequest->refresh()->load('items');
        });
    }

    public function linkTransfer(
        StockRequest $stockRequest,
        TransferTransaction $transferTransaction,
        ?int $userId = null
    ): StockRequest {
        $this->resolveActorId($userId);

        return DB::transaction(function () use ($stockRequest, $transferTransaction, $userId): StockRequest {
            $lockedRequest = StockRequest::query()
                ->with(['items', 'requesterStore', 'sourceStore'])
                ->lockForUpdate()
                ->findOrFail($stockRequest->id);

            $lockedTransfer = TransferTransaction::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($transferTransaction->id);

            if (! in_array($lockedRequest->status, [
                StockRequestStatus::APPROVED,
                StockRequestStatus::PARTIALLY_FULFILLED,
            ], true)) {
                throw new \DomainException('Only approved or partially fulfilled stock request can be linked with transfer.');
            }

            if (! in_array($lockedTransfer->status, [
                TransferStatus::REQUESTED,
                TransferStatus::APPROVED,
                TransferStatus::COMPLETED,
            ], true)) {
                throw new \DomainException('Only requested, approved, or completed transfer can be linked.');
            }

            if ((int) $lockedTransfer->receiver_store_id !== (int) $lockedRequest->requester_store_id) {
                throw new \DomainException('Linked transfer receiver store must match requester store.');
            }

            if ($lockedRequest->source_store_id && (int) $lockedTransfer->sender_store_id !== (int) $lockedRequest->source_store_id) {
                throw new \DomainException('Linked transfer sender store must match selected source store.');
            }

            if (! $lockedRequest->source_store_id) {
                $lockedRequest->update([
                    'source_store_id' => (int) $lockedTransfer->sender_store_id,
                ]);
            }

            StockRequestTransferLink::query()->firstOrCreate([
                'stock_request_id' => (int) $lockedRequest->id,
                'transfer_transaction_id' => (int) $lockedTransfer->id,
            ]);

            return $this->recalculateFulfillmentStatus($lockedRequest, $userId);
        });
    }

    public function recalculateLinkedRequestsForTransfer(TransferTransaction $transferTransaction, ?int $userId = null): void
    {
        if ($transferTransaction->status !== TransferStatus::COMPLETED) {
            return;
        }

        $requestIds = StockRequestTransferLink::query()
            ->where('transfer_transaction_id', $transferTransaction->id)
            ->pluck('stock_request_id')
            ->all();

        foreach ($requestIds as $requestId) {
            $stockRequest = StockRequest::query()->find($requestId);
            if (! $stockRequest) {
                continue;
            }

            $this->recalculateFulfillmentStatus($stockRequest, $userId);
        }
    }

    /**
     * @return array<int, float>
     */
    protected function completedTransferQtyByProduct(int $stockRequestId): array
    {
        return DB::table('stock_request_transfer_links as links')
            ->join('transfer_transactions as transfers', 'transfers.id', '=', 'links.transfer_transaction_id')
            ->join('transfer_items as items', 'items.transfer_transaction_id', '=', 'transfers.id')
            ->where('links.stock_request_id', $stockRequestId)
            ->where('transfers.status', TransferStatus::COMPLETED->value)
            ->groupBy('items.product_id')
            ->selectRaw('items.product_id, SUM(COALESCE(items.received_quantity, items.quantity)) as fulfilled_qty')
            ->pluck('fulfilled_qty', 'items.product_id')
            ->map(fn ($qty): float => round((float) $qty, 3))
            ->all();
    }

    /**
     * @param  array<int, float|int|string>|null  $approvedQuantities
     */
    protected function resolveApprovedQuantity(int $itemId, float $defaultValue, ?array $approvedQuantities): float
    {
        if (! is_array($approvedQuantities) || ! array_key_exists($itemId, $approvedQuantities)) {
            return round($defaultValue, 3);
        }

        return round((float) $approvedQuantities[$itemId], 3);
    }

    protected function appendWorkflowRemarks(?string $existingRemarks, string $workflowLabel, ?string $note): ?string
    {
        $trimmed = trim((string) $note);
        if ($trimmed === '') {
            return $existingRemarks;
        }

        if (! $existingRemarks) {
            return '['.$workflowLabel.'] '.$trimmed;
        }

        return rtrim($existingRemarks).PHP_EOL.'['.$workflowLabel.'] '.$trimmed;
    }

    protected function resolveActorId(?int $userId): int
    {
        $actorId = $userId ?? (int) Auth::id();

        if ($actorId <= 0) {
            throw new \DomainException('A valid user is required for stock request actions.');
        }

        return $actorId;
    }
}
