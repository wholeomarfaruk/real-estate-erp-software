<?php

namespace App\Livewire\Admin\Supplier\Concerns;

trait InteractsWithSupplierAccess
{
    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
