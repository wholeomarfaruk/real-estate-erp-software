<?php

namespace App\Livewire\Admin\Inventory\PurchaseReturn;

use App\Enums\Inventory\PurchaseReturnStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\PurchaseReturn;
use App\Models\StockReceive;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\Inventory\PurchaseReturnService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseReturnList extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $supplierFilter = null;

    public ?int $storeFilter = null;

    public ?int $stockReceiveFilter = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.purchase_return.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSupplierFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStoreFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStockReceiveFilter(): void
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

    public function postReturn(int $purchaseReturnId): void
    {
        $this->authorizePermission('inventory.purchase_return.post');

        $purchaseReturn = PurchaseReturn::query()->find($purchaseReturnId);

        if (! $purchaseReturn) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase return not found.']);

            return;
        }

        $this->ensureStoreAccessible((int) $purchaseReturn->store_id);

        try {
            app(PurchaseReturnService::class)->postReturn($purchaseReturn, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase return posted successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelReturn(int $purchaseReturnId): void
    {
        $this->authorizePermission('inventory.purchase_return.update');

        $purchaseReturn = PurchaseReturn::query()->find($purchaseReturnId);

        if (! $purchaseReturn) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase return not found.']);

            return;
        }

        $this->ensureStoreAccessible((int) $purchaseReturn->store_id);

        try {
            app(PurchaseReturnService::class)->cancelReturn($purchaseReturn, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase return cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function deleteReturn(int $purchaseReturnId): void
    {
        $this->authorizePermission('inventory.purchase_return.delete');

        $purchaseReturn = PurchaseReturn::query()->find($purchaseReturnId);

        if (! $purchaseReturn) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase return not found.']);

            return;
        }

        $this->ensureStoreAccessible((int) $purchaseReturn->store_id);

        if ($purchaseReturn->status !== PurchaseReturnStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft purchase return can be deleted.']);

            return;
        }

        DB::transaction(function () use ($purchaseReturn): void {
            $purchaseReturn->items()->delete();
            $purchaseReturn->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase return deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.purchase_return.view');

        $query = PurchaseReturn::query()
            ->with([
                'supplier:id,name,phone',
                'store:id,name,code,type',
                'purchaseOrder:id,po_no,status',
                'stockReceive:id,receive_no,receive_date',
            ])
            ->withSum('items as grand_total', 'total_price')
            ->when($this->search !== '', function (Builder $builder): void {
                $builder->where(function (Builder $subQuery): void {
                    $subQuery->where('return_no', 'like', '%'.$this->search.'%')
                        ->orWhere('reason', 'like', '%'.$this->search.'%')
                        ->orWhere('remarks', 'like', '%'.$this->search.'%')
                        ->orWhereHas('supplier', function (Builder $supplierQuery): void {
                            $supplierQuery->where('name', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('stockReceive', function (Builder $stockReceiveQuery): void {
                            $stockReceiveQuery->where('receive_no', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('purchaseOrder', function (Builder $purchaseOrderQuery): void {
                            $purchaseOrderQuery->where('po_no', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $builder): Builder => $builder->where('status', $this->statusFilter))
            ->when($this->supplierFilter, fn (Builder $builder): Builder => $builder->where('supplier_id', $this->supplierFilter))
            ->when($this->storeFilter, fn (Builder $builder): Builder => $builder->where('store_id', $this->storeFilter))
            ->when($this->stockReceiveFilter, fn (Builder $builder): Builder => $builder->where('stock_receive_id', $this->stockReceiveFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('return_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('return_date', '<=', $this->dateTo));

        $this->applyStoreRestriction($query);

        $purchaseReturns = $query
            ->latest('return_date')
            ->latest('id')
            ->paginate(15);

        $statsQuery = PurchaseReturn::query();
        $this->applyStoreRestriction($statsQuery);

        $totalReturns = (clone $statsQuery)->count();
        $postedReturns = (clone $statsQuery)->where('status', PurchaseReturnStatus::POSTED->value)->count();
        $draftReturns = (clone $statsQuery)->where('status', PurchaseReturnStatus::DRAFT->value)->count();
        $cancelledReturns = (clone $statsQuery)->where('status', PurchaseReturnStatus::CANCELLED->value)->count();

        $storesQuery = Store::query()->active()->office()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        $stockReceivesQuery = StockReceive::query()
            ->whereNotNull('supplier_id')
            ->orderByDesc('receive_date')
            ->orderByDesc('id')
            ->when($this->supplierFilter, fn (Builder $builder): Builder => $builder->where('supplier_id', $this->supplierFilter))
            ->when($this->storeFilter, fn (Builder $builder): Builder => $builder->where('store_id', $this->storeFilter));

        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $stockReceivesQuery->whereIn('store_id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.purchase-return.purchase-return-list', [
            'purchaseReturns' => $purchaseReturns,
            'statuses' => PurchaseReturnStatus::cases(),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'stores' => $storesQuery->get(['id', 'name', 'code']),
            'stockReceives' => $stockReceivesQuery->limit(200)->get(['id', 'receive_no', 'supplier_id', 'store_id']),
            'totalReturns' => $totalReturns,
            'postedReturns' => $postedReturns,
            'draftReturns' => $draftReturns,
            'cancelledReturns' => $cancelledReturns,
        ])->layout('layouts.admin.admin');
    }
}
