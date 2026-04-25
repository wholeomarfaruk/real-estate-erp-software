<?php

namespace App\Livewire\Admin\Inventory\PurchaseOrder;

use App\Enums\Inventory\PurchaseMode;
use App\Enums\Inventory\PurchaseOrderStatus;
use App\Livewire\Admin\Inventory\Concerns\InteractsWithInventoryAccess;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\Inventory\PurchaseOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderList extends Component
{
    use InteractsWithInventoryAccess;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $storeFilter = null;

    public ?int $supplierFilter = null;

    public string $purchaseModeFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizePermission('inventory.purchase_order.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStoreFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSupplierFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPurchaseModeFilter(): void
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

    public function submitOrder(int $purchaseOrderId): void
    {
        $this->authorizePermission('inventory.purchase_order.submit');

        $purchaseOrder = PurchaseOrder::query()->find($purchaseOrderId);
        if (! $purchaseOrder) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase order not found.']);

            return;
        }

        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        try {
            app(PurchaseOrderService::class)->submitForEngineerApproval($purchaseOrder, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order submitted for engineer approval.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function engineerApproveOrder(int $purchaseOrderId): void
    {
        $this->authorizePermission('inventory.purchase_order.engineer_approve');

        $purchaseOrder = PurchaseOrder::query()->find($purchaseOrderId);
        if (! $purchaseOrder) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase order not found.']);

            return;
        }

        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        try {
            app(PurchaseOrderService::class)->engineerApprove($purchaseOrder, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order approved by engineer.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function chairmanApproveOrder(int $purchaseOrderId, float|int|string|null $approvedAmount = null): void
    {
        $this->authorizePermission('inventory.purchase_order.chairman_approve');

        $purchaseOrder = PurchaseOrder::query()->find($purchaseOrderId);
        if (! $purchaseOrder) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase order not found.']);

            return;
        }

        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        try {
            if ($approvedAmount === null || trim((string) $approvedAmount) === '') {
                throw new \DomainException('Approved amount is required.');
            }

            app(PurchaseOrderService::class)->chairmanApproveWithAmount(
                $purchaseOrder,
                (float) $approvedAmount,
                (int) auth()->id()
            );
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order approved by chairman.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function accountsApproveOrder(int $purchaseOrderId): void
    {
        $this->authorizePermission('inventory.purchase_order.accounts_approve');

        $purchaseOrder = PurchaseOrder::query()->find($purchaseOrderId);
        if (! $purchaseOrder) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase order not found.']);

            return;
        }

        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        try {
            app(PurchaseOrderService::class)->accountsApprove($purchaseOrder, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order approved by accounts.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function completeOrder(int $purchaseOrderId): void
    {
        $this->authorizePermission('inventory.purchase_order.complete');

        $purchaseOrder = PurchaseOrder::query()->find($purchaseOrderId);
        if (! $purchaseOrder) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase order not found.']);

            return;
        }

        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        try {
            app(PurchaseOrderService::class)->completePurchaseOrder($purchaseOrder, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order marked as completed.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function cancelOrder(int $purchaseOrderId): void
    {
        $this->authorizePermission('inventory.purchase_order.update');

        $purchaseOrder = PurchaseOrder::query()->find($purchaseOrderId);
        if (! $purchaseOrder) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase order not found.']);

            return;
        }

        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        try {
            app(PurchaseOrderService::class)->cancelPurchaseOrder($purchaseOrder, (int) auth()->id());
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order cancelled successfully.']);
        } catch (\Throwable $throwable) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $throwable->getMessage()]);
        }
    }

    public function makeDraft(int $purchaseOrderId): void
    {
        $this->authorizePermission('inventory.purchase_order.update');

        $purchaseOrder = PurchaseOrder::query()->with('items')->find($purchaseOrderId);
        if (! $purchaseOrder) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase order not found.']);

            return;
        }

        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        if (! in_array($purchaseOrder->status, [
            PurchaseOrderStatus::PENDING_ENGINEER,
            PurchaseOrderStatus::PENDING_CHAIRMAN,
            PurchaseOrderStatus::PENDING_ACCOUNTS,
        ], true)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only pending approval purchase orders can be moved to draft.']);

            return;
        }

        DB::transaction(function () use ($purchaseOrder): void {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::DRAFT->value,
                'engineer_approved_by' => null,
                'engineer_approved_at' => null,
                'chairman_approved_by' => null,
                'chairman_approved_at' => null,
                'accounts_approved_by' => null,
                'accounts_approved_at' => null,
                'approved_amount' => 0,
            ]);

            $purchaseOrder->items()->update([
                'approved_quantity' => null,
                'approved_unit_price' => null,
                'approved_total_price' => null,
            ]);
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order moved back to draft.']);
    }

    public function deleteOrder(int $purchaseOrderId): void
    {
        $this->authorizePermission('inventory.purchase_order.delete');

        $purchaseOrder = PurchaseOrder::query()->find($purchaseOrderId);
        if (! $purchaseOrder) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Purchase order not found.']);

            return;
        }

        $this->ensurePurchaseOrderAccessible($purchaseOrder);

        if ($purchaseOrder->status !== PurchaseOrderStatus::DRAFT) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Only draft purchase order can be deleted.']);

            return;
        }

        if ($purchaseOrder->stockReceives()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Linked stock receive exists. Draft cannot be deleted.']);

            return;
        }

        DB::transaction(function () use ($purchaseOrder): void {
            $purchaseOrder->delete();
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order deleted successfully.']);
    }

    public function render(): View
    {
        $this->authorizePermission('inventory.purchase_order.view');

        $query = PurchaseOrder::query()
            ->with([
                'requester:id,name',
                'store:id,name,code,type',
                'supplier:id,name',
            ])
            ->withCount('items')
            ->withSum('items as estimated_total', 'estimated_total_price')
            ->withSum('funds as released_total', 'amount')
            ->when($this->search !== '', function (Builder $builder): void {
                $builder->where(function (Builder $subQuery): void {
                    $subQuery->where('po_no', 'like', '%'.$this->search.'%')
                        ->orWhere('remarks', 'like', '%'.$this->search.'%')
                        ->orWhereHas('supplier', function (Builder $supplierQuery): void {
                            $supplierQuery->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $builder): Builder => $builder->where('status', $this->statusFilter))
            ->when($this->storeFilter, fn (Builder $builder): Builder => $builder->where('store_id', $this->storeFilter))
            ->when($this->supplierFilter, fn (Builder $builder): Builder => $builder->where('supplier_id', $this->supplierFilter))
            ->when($this->purchaseModeFilter !== '', fn (Builder $builder): Builder => $builder->where('purchase_mode', $this->purchaseModeFilter))
            ->when($this->dateFrom, fn (Builder $builder): Builder => $builder->whereDate('order_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $builder): Builder => $builder->whereDate('order_date', '<=', $this->dateTo));

        $this->applyStoreRestriction($query);

        $purchaseOrders = $query->latest('order_date')->latest('id')->paginate(15);

        $statsQuery = PurchaseOrder::query();
        $this->applyStoreRestriction($statsQuery, 'store_id');

        $totalOrders = (clone $statsQuery)->count();
        $draftOrders = (clone $statsQuery)->where('status', PurchaseOrderStatus::DRAFT->value)->count();
        $pendingOrders = (clone $statsQuery)->whereIn('status', [
            PurchaseOrderStatus::PENDING_ENGINEER->value,
            PurchaseOrderStatus::PENDING_CHAIRMAN->value,
            PurchaseOrderStatus::PENDING_ACCOUNTS->value,
        ])->count();
        $approvedOrders = (clone $statsQuery)->whereIn('status', [
            PurchaseOrderStatus::APPROVED->value,
            PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
            PurchaseOrderStatus::RECEIVED->value,
            PurchaseOrderStatus::COMPLETED->value,
        ])->count();

        $storesQuery = Store::query()->active()->orderBy('name');
        if (! $this->canViewAllStores()) {
            $storeIds = $this->getAccessibleStoreIds();
            $storesQuery->whereIn('id', $storeIds === [] ? [0] : $storeIds);
        }

        return view('livewire.admin.inventory.purchase-order.purchase-order-list', [
            'purchaseOrders' => $purchaseOrders,
            'statuses' => PurchaseOrderStatus::cases(),
            'purchaseModes' => PurchaseMode::cases(),
            'stores' => $storesQuery->get(['id', 'name', 'code']),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'totalOrders' => $totalOrders,
            'draftOrders' => $draftOrders,
            'pendingOrders' => $pendingOrders,
            'approvedOrders' => $approvedOrders,
        ])->layout('layouts.admin.admin');
    }

    protected function ensurePurchaseOrderAccessible(PurchaseOrder $purchaseOrder): void
    {
        if ($this->canViewAllStores()) {
            return;
        }

        $storeIds = $this->getAccessibleStoreIds();

        abort_unless(
            in_array((int) $purchaseOrder->store_id, $storeIds, true),
            403,
            'You are not allowed to access this purchase order.'
        );
    }

    protected function canViewAllStores(): bool
    {
        return $this->hasInventoryWideAccess($this->purchaseOrderGlobalAccessPermissions());
    }
}
