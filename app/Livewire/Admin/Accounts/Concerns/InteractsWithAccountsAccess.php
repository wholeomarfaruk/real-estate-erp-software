<?php

namespace App\Livewire\Admin\Accounts\Concerns;

trait InteractsWithAccountsAccess
{
    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
