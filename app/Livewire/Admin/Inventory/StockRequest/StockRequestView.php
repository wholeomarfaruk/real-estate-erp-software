<?php

namespace App\Livewire\Admin\Inventory\StockRequest;

use App\Enums\Inventory\StockRequestStatus;
use App\Enums\Inventory\TransferStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\StockRequest;
use App\Models\TransferTransaction;
use App\Services\Inventory\StockRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class StockRequestView extends Component
{
    use InteractsWithInventoryAccess;

    public StockRequest $stockRequest;

    /**
     * @var array<int, float>
     */
    public array $approvalQuantities = [];

    public ?string $approvalRemarks = null;

    public ?string $rejectionRemarks = null;

    public ?int $transfer_transaction_id = null;

    public function mount(StockRequest $stockRequest): void
    {
        $this->authorizePermission('inventory.stock_request.view');

        $this->stockRequest = $stockRequest;
        $this->reloadStockRequest();
    }

    public function submitRequest(): void
    {
        $this->authorizePermission('inventory.stock_request.submit');

        if ($this->stockRequest->status !== StockRequestStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft stock request can be submitted.']);

            return;
        }

        try {
            app(StockRequestService::class)->submitRequest($this->stockRequest, (int) auth()->id());
            $this->reloadStockRequest();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request submitted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function approveRequest(): void
    {
        $this->authorizePermission('inventory.stock_request.approve');

        if ($this->stockRequest->status !== StockRequestStatus::PENDING) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only pending stock request can be approved.']);

            return;
        }

        try {
            app(StockRequestService::class)->approveRequest(
                stockRequest: $this->stockRequest,
                userId: (int) auth()->id(),
                approvedQuantities: $this->approvalQuantities,
                remarks: $this->approvalRemarks
            );

            $this->approvalRemarks = null;
            $this->reloadStockRequest();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request approved successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function rejectRequest(): void
    {
        $this->authorizePermission('inventory.stock_request.reject');

        if ($this->stockRequest->status !== StockRequestStatus::PENDING) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only pending stock request can be rejected.']);

            return;
        }

        try {
            app(StockRequestService::class)->rejectRequest(
                stockRequest: $this->stockRequest,
                userId: (int) auth()->id(),
                remarks: $this->rejectionRemarks
            );

            $this->rejectionRemarks = null;
            $this->reloadStockRequest();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request rejected successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelRequest(): void
    {
        $this->authorizePermission('inventory.stock_request.update');

        if (! in_array($this->stockRequest->status, [
            StockRequestStatus::DRAFT,
            StockRequestStatus::PENDING,
            StockRequestStatus::APPROVED,
        ], true)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This stock request cannot be cancelled.']);

            return;
        }

        try {
            app(StockRequestService::class)->cancelRequest($this->stockRequest, (int) auth()->id());
            $this->reloadStockRequest();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function linkTransfer(): void
    {
        $this->authorizePermission('inventory.stock_request.update');

        if (! $this->transfer_transaction_id) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please select a transfer transaction to link.']);

            return;
        }

        $transfer = TransferTransaction::query()->find($this->transfer_transaction_id);

        if (! $transfer) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Selected transfer transaction not found.']);

            return;
        }

        try {
            app(StockRequestService::class)->linkTransfer($this->stockRequest, $transfer, (int) auth()->id());
            $this->transfer_transaction_id = null;
            $this->reloadStockRequest();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer linked successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function recalculateFulfillment(): void
    {
        $this->authorizePermission('inventory.stock_request.approve');

        try {
            app(StockRequestService::class)->recalculateFulfillmentStatus($this->stockRequest, (int) auth()->id());
            $this->reloadStockRequest();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Fulfillment status recalculated successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function markFulfilled(): void
    {
        $this->authorizePermission('inventory.stock_request.approve');

        try {
            app(StockRequestService::class)->markFulfilled($this->stockRequest, (int) auth()->id());
            $this->reloadStockRequest();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock request marked as fulfilled.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        $targetQty = (float) $this->stockRequest->items->sum(function ($item): float {
            return (float) ($item->approved_quantity ?? $item->quantity);
        });

        $fulfilledQty = (float) $this->stockRequest->items->sum('fulfilled_quantity');
        $remainingQty = max(0, round($targetQty - $fulfilledQty, 3));

        return view('livewire.admin.inventory.stock-request.stock-request-view', [
            'targetQty' => round($targetQty, 3),
            'fulfilledQty' => round($fulfilledQty, 3),
            'remainingQty' => round($remainingQty, 3),
            'transferCandidates' => $this->transferCandidates,
        ])->layout('layouts.admin.admin');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, TransferTransaction>
     */
    public function getTransferCandidatesProperty()
    {
        $alreadyLinkedTransferIds = $this->stockRequest->transfers->pluck('id')->all();

        return TransferTransaction::query()
            ->whereIn('status', [
                TransferStatus::REQUESTED->value,
                TransferStatus::APPROVED->value,
                TransferStatus::COMPLETED->value,
            ])
            ->where('receiver_store_id', (int) $this->stockRequest->requester_store_id)
            ->when($this->stockRequest->source_store_id, fn (Builder $builder): Builder => $builder->where('sender_store_id', (int) $this->stockRequest->source_store_id))
            ->whereNotIn('id', $alreadyLinkedTransferIds === [] ? [0] : $alreadyLinkedTransferIds)
            ->latest('transfer_date')
            ->latest('id')
            ->get(['id', 'transfer_no', 'transfer_date', 'status', 'sender_store_id', 'receiver_store_id']);
    }

    protected function reloadStockRequest(): void
    {
        $this->stockRequest = $this->stockRequest->fresh([
            'requesterStore:id,name,code,type',
            'sourceStore:id,name,code,type',
            'project:id,name,code',
            'requester:id,name',
            'approver:id,name',
            'rejecter:id,name',
            'fulfiller:id,name',
            'items.product:id,name,sku',
            'transfers.senderStore:id,name,code',
            'transfers.receiverStore:id,name,code',
        ]);

        if (! $this->stockRequest) {
            abort(404, 'Stock request not found.');
        }

        $this->ensureRequestAccessible($this->stockRequest);
        $this->seedApprovalQuantities();
    }

    protected function seedApprovalQuantities(): void
    {
        $this->approvalQuantities = $this->stockRequest->items
            ->mapWithKeys(fn ($item): array => [
                (int) $item->id => (float) ($item->approved_quantity ?? $item->quantity),
            ])
            ->all();
    }

    protected function ensureRequestAccessible(StockRequest $stockRequest): void
    {
        if ($this->canViewAllStores()) {
            return;
        }

        $storeIds = $this->getAccessibleStoreIds();

        abort_unless(
            in_array((int) $stockRequest->requester_store_id, $storeIds, true)
            || ($stockRequest->source_store_id && in_array((int) $stockRequest->source_store_id, $storeIds, true)),
            403,
            'You are not allowed to access this stock request.'
        );
    }
}
