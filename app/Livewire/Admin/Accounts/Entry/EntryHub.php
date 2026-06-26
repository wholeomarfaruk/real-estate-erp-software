<?php

namespace App\Livewire\Admin\Accounts\Entry;

use App\Repositories\AccountEntryTypeRepository;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin.admin')]
class EntryHub extends Component
{
    public function mount(): void
    {
        $this->authorizePermission('accounts.entry.hub.view');
    }

    public function render(): View
    {
        $repo = app(AccountEntryTypeRepository::class);
        $categories = $repo->getCategorized();

        return view('livewire.admin.accounts.entry.hub', [
            'categories' => $categories,
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
