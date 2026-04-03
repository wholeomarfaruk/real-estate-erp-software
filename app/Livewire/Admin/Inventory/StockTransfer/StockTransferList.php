<?php

namespace App\Livewire\Admin\Inventory\StockTransfer;

use App\Enums\Inventory\TransferStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\Store;
use App\Models\TransferTransaction;
use App\Services\Inventory\StockTransferService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockTransferList extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $senderStoreFilter = null;

    public ?int $receiverStoreFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.stock.transfer.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSenderStoreFilter(): void
    {
        $this->resetPage();
    }

    public function updatedReceiverStoreFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function requestTransfer(int $transferId): void
    {
        $this->authorizePermission('inventory.stock.transfer.request');

        $transfer = TransferTransaction::query()->find($transferId);

        if (! $transfer) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Transfer not found.']);

            return;
        }

        $this->ensureTransferAccessible($transfer);

        try {
            app(StockTransferService::class)->requestTransfer($transfer, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer requested successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function approveTransfer(int $transferId): void
    {
        $this->authorizePermission('inventory.stock.transfer.approve');

        $transfer = TransferTransaction::query()->find($transferId);

        if (! $transfer) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Transfer not found.']);

            return;
        }

        $this->ensureTransferAccessible($transfer);

        try {
            app(StockTransferService::class)->approveTransfer($transfer, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer approved successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function completeTransfer(int $transferId): void
    {
        $this->authorizePermission('inventory.stock.transfer.complete');

        $transfer = TransferTransaction::query()->find($transferId);

        if (! $transfer) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Transfer not found.']);

            return;
        }

        $this->ensureTransferAccessible($transfer);

        try {
            app(StockTransferService::class)->completeTransfer($transfer, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer completed successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelTransfer(int $transferId): void
    {
        $this->authorizePermission('inventory.stock.transfer.update');

        $transfer = TransferTransaction::query()->find($transferId);

        if (! $transfer) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Transfer not found.']);

            return;
        }

        $this->ensureTransferAccessible($transfer);

        try {
            app(StockTransferService::class)->cancelTransfer($transfer, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function deleteTransfer(int $transferId): void
    {
        $this->authorizePermission('inventory.stock.transfer.delete');

        $transfer = TransferTransaction::query()->find($transferId);

        if (! $transfer) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Transfer not found.']);

            return;
        }

        $this->ensureTransferAccessible($transfer);

        if ($transfer->status !== TransferStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft transfer can be deleted.']);

            return;
        }

        DB::transaction(function () use ($transfer): void {
            $transfer->items()->delete();
            $transfer->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Transfer deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.stock.transfer.view');

        $query = TransferTransaction::query()
            ->with(['senderStore:id,name,code', 'receiverStore:id,name,code'])
            ->withCount('items')
            ->withSum('items as transfer_total', 'total_price')
            ->when($this->search !== '', function (Builder $builder): void {
                $builder->where(function (Builder $subQuery): void {
                    $subQuery->where('transfer_no', 'like', '%'.$this->search.'%')
                        ->orWhere('remarks', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $builder): Builder => $builder->where('status', $this->statusFilter))
            ->when($this->senderStoreFilter, fn (Builder $builder): Builder => $builder->where('sender_store_id', $this->senderStoreFilter))
            ->when($this->receiverStoreFilter, fn (Builder $builder): Builder => $builder->where('receiver_store_id', $this->receiverStoreFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('transfer_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('transfer_date', '<=', $this->dateTo));

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();

            $query->where(function (Builder $subQuery) use ($storeIds): void {
                $subQuery->whereIn('sender_store_id', $storeIds === [] ? [0] : $storeIds)
                    ->orWhereIn('receiver_store_id', $storeIds === [] ? [0] : $storeIds);
            });
        }

        $transfers = $query->latest('transfer_date')->latest('id')->paginate(15);

        $statsQuery = TransferTransaction::query();
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();

            $statsQuery->where(function (Builder $subQuery) use ($storeIds): void {
                $subQuery->whereIn('sender_store_id', $storeIds === [] ? [0] : $storeIds)
                    ->orWhereIn('receiver_store_id', $storeIds === [] ? [0] : $storeIds);
            });
        }

        $totalTransfers = (clone $statsQuery)->count();
        $draftTransfers = (clone $statsQuery)->where('status', TransferStatus::DRAFT->value)->count();
        $approvedTransfers = (clone $statsQuery)->where('status', TransferStatus::APPROVED->value)->count();
        $completedTransfers = (clone $statsQuery)->where('status', TransferStatus::COMPLETED->value)->count();

        $storesQuery = Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.stock-transfer.stock-transfer-list', [
            'transfers' => $transfers,
            'statuses' => TransferStatus::cases(),
            'stores' => $storesQuery->get(['id', 'name', 'code']),
            'totalTransfers' => $totalTransfers,
            'draftTransfers' => $draftTransfers,
            'approvedTransfers' => $approvedTransfers,
            'completedTransfers' => $completedTransfers,
        ])->layout('layouts.admin.admin');
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
