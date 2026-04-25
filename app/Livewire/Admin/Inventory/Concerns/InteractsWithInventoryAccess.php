<?php

namespace App\Livewire\Admin\Inventory\Concerns;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithInventoryAccess
{
    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }

    protected function hasInventoryWideAccess(array $permissions): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('superadmin')) {
            return true;
        }

        return $permissions !== [] && $user->canAny($permissions);
    }

    /**
     * @return string[]
     */
    protected function inventoryGlobalAccessPermissions(): array
    {
        return [
            'inventory.dashboard.view',
            'inventory.stock.ledger.view',
            'inventory.stock.report.view',
            'inventory.stock.receive.view',
            'inventory.stock.receive.create',
            'inventory.stock.receive.update',
            'inventory.stock.receive.post',
            'inventory.stock.receive.delete',
            'inventory.purchase_order.view',
            'inventory.purchase_order.create',
            'inventory.purchase_order.update',
            'inventory.purchase_order.submit',
            'inventory.purchase_order.engineer_approve',
            'inventory.purchase_order.chairman_approve',
            'inventory.purchase_order.accounts_approve',
            'inventory.purchase_order.fund_release',
            'inventory.purchase_order.settle',
            'inventory.purchase_order.complete',
            'inventory.purchase_order.delete',
            'inventory.purchase_return.view',
            'inventory.purchase_return.create',
            'inventory.purchase_return.update',
            'inventory.purchase_return.post',
            'inventory.purchase_return.delete',
            'inventory.stock_request.view',
            'inventory.stock_request.create',
            'inventory.stock_request.update',
            'inventory.stock_request.submit',
            'inventory.stock_request.approve',
            'inventory.stock_request.reject',
            'inventory.stock_request.delete',
            'inventory.stock.transfer.view',
            'inventory.stock.transfer.create',
            'inventory.stock.transfer.update',
            'inventory.stock.transfer.request',
            'inventory.stock.transfer.approve',
            'inventory.stock.transfer.complete',
            'inventory.stock.transfer.delete',
            'inventory.stock.adjustment.view',
            'inventory.stock.adjustment.create',
            'inventory.stock.adjustment.update',
            'inventory.stock.adjustment.post',
            'inventory.stock.adjustment.delete',
            'inventory.stock.consumption.view',
            'inventory.stock.consumption.create',
            'inventory.stock.consumption.update',
            'inventory.stock.consumption.post',
            'inventory.stock.consumption.delete',
        ];
    }

    /**
     * @return string[]
     */
    protected function purchaseOrderGlobalAccessPermissions(): array
    {
        return [
            'inventory.purchase_order.view',
            'inventory.purchase_order.create',
            'inventory.purchase_order.update',
            'inventory.purchase_order.submit',
            'inventory.purchase_order.engineer_approve',
            'inventory.purchase_order.chairman_approve',
            'inventory.purchase_order.accounts_approve',
            'inventory.purchase_order.fund_release',
            'inventory.purchase_order.settle',
            'inventory.purchase_order.complete',
            'inventory.purchase_order.delete',
        ];
    }

    /**
     * @return string[]
     */
    protected function stockReceiveGlobalAccessPermissions(): array
    {
        return [
            'inventory.stock.receive.view',
            'inventory.stock.receive.create',
            'inventory.stock.receive.update',
            'inventory.stock.receive.post',
            'inventory.stock.receive.delete',
        ];
    }

    protected function canViewAllStores(): bool
    {
        return $this->hasInventoryWideAccess($this->inventoryGlobalAccessPermissions());
    }

    /**
     * @return int[]
     */
    protected function getAccessibleStoreIds(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($this->canViewAllStores()) {
            return Store::query()->pluck('id')->all();
        }

        return Store::query()
            ->managedBy($user->id)
            ->pluck('id')
            ->all();
    }

    protected function applyStoreRestriction(Builder $query, string $column = 'store_id'): Builder
    {
        if ($this->canViewAllStores()) {
            return $query;
        }

        $storeIds = $this->getAccessibleStoreIds();

        return $query->whereIn($column, $storeIds === [] ? [0] : $storeIds);
    }

    protected function ensureStoreAccessible(int $storeId): void
    {
        if ($this->canViewAllStores()) {
            return;
        }

        abort_unless(in_array($storeId, $this->getAccessibleStoreIds(), true), 403, 'You are not allowed to access this store.');
    }
}
