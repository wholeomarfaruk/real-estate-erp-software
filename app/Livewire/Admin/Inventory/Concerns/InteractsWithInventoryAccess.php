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

    protected function canViewAllStores(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('superadmin')
            || $user->hasRole('admin')
            || $user->hasRole('accounts')
            || $user->can('inventory.stock.report.view');
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
