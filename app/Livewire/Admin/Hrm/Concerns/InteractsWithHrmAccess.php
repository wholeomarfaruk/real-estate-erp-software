<?php

namespace App\Livewire\Admin\Hrm\Concerns;

trait InteractsWithHrmAccess
{
    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}

