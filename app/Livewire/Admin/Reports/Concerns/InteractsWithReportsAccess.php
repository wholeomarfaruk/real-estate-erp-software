<?php

namespace App\Livewire\Admin\Reports\Concerns;

trait InteractsWithReportsAccess
{
    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Unauthorized action.');
    }
}
