<?php

namespace App\Livewire\Admin\Accounts\Entry;

use App\Repositories\AccountEntryTypeRepository;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin.admin')]
class EntryCategory extends Component
{
    public string $category = '';

    public function mount(string $category): void
    {
        $this->authorizePermission('accounts.entry.category.view');
        $repo = app(AccountEntryTypeRepository::class);
        abort_unless($repo->findCategory($category), 404);
        $this->category = $category;
    }

    public function render(): View
    {
        $repo = app(AccountEntryTypeRepository::class);
        $categorized = $repo->getCategorized();
        $categoryData = $categorized[$this->category] ?? null;

        abort_unless($categoryData !== null, 404);

        return view('livewire.admin.accounts.entry.category', [
            'categoryData' => $categoryData,
            'allCategories' => $categorized,
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
