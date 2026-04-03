<?php

namespace App\Livewire\Admin\Inventory\StockTransfer;

use App\Enums\Inventory\TransferStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\TransferTransaction;
use App\Services\Inventory\StockTransferService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StockTransferView extends Component
{
    use InteractsWithInventoryAccess;

    public TransferTransaction $transferTransaction;

    public function mount(TransferTransaction $transferTransaction): void
    {
        $this->authorizePermission('inventory.stock.transfer.view');

        $this->transferTransaction = $transferTransaction->load([
            'senderStore:id,name,code,type',
            'receiverStore:id,name,code,type',
            'requester:id,name',
            'approver:id,name',
            'receiver:id,name',
            'items.product:id,name,sku',
        ]);

        $this->ensureTransferAccessible($this->transferTransaction);
    }

    public function requestTransfer(): void
    {
        $this->authorizePermission('inventory.stock.transfer.request');

        if ($this->transferTransaction->status !== TransferStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft transfer can be requested.']);

            return;
        }

        try {
            app(StockTransferService::class)->requestTransfer($this->transferTransaction, (int) auth()->id());
            $this->reloadTransfer();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer requested successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function approveTransfer(): void
    {
        $this->authorizePermission('inventory.stock.transfer.approve');

        if ($this->transferTransaction->status !== TransferStatus::REQUESTED) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only requested transfer can be approved.']);

            return;
        }

        try {
            app(StockTransferService::class)->approveTransfer($this->transferTransaction, (int) auth()->id());
            $this->reloadTransfer();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer approved successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function completeTransfer(): void
    {
        $this->authorizePermission('inventory.stock.transfer.complete');

        if ($this->transferTransaction->status !== TransferStatus::APPROVED) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only approved transfer can be completed.']);

            return;
        }

        try {
            app(StockTransferService::class)->completeTransfer($this->transferTransaction, (int) auth()->id());
            $this->reloadTransfer();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer completed successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelTransfer(): void
    {
        $this->authorizePermission('inventory.stock.transfer.update');

        if (! in_array($this->transferTransaction->status, [TransferStatus::DRAFT, TransferStatus::REQUESTED, TransferStatus::APPROVED], true)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This transfer cannot be cancelled.']);

            return;
        }

        try {
            app(StockTransferService::class)->cancelTransfer($this->transferTransaction, (int) auth()->id());
            $this->reloadTransfer();
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function render(): View
    {
        $totalValue = (float) $this->transferTransaction->items->sum(fn ($item): float => (float) $item->total_price);

        return view('livewire.admin.inventory.stock-transfer.stock-transfer-view', [
            'totalValue' => round($totalValue, 2),
        ])
            ->layout('layouts.admin.admin');
    }

    protected function reloadTransfer(): void
    {
        $this->transferTransaction = $this->transferTransaction->refresh()->load([
            'senderStore:id,name,code,type',
            'receiverStore:id,name,code,type',
            'requester:id,name',
            'approver:id,name',
            'receiver:id,name',
            'items.product:id,name,sku',
        ]);
    }

    protected function ensureTransferAccessible(TransferTransaction $transfer): void
    {
        if ($this->canViewAllStores()) {
            return;
        }

        $storeIds = $this->getAccessibleStoreIds();

        abort_unless(
            in_array((int) $transfer->sender_store_id, $storeIds, true)
            || in_array((int) $transfer->receiver_store_id, $storeIds, true),
            403,
            'You are not allowed to access this transfer.'
        );
    }
}
