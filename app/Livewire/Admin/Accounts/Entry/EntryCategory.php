<?php

namespace App\Livewire\Admin\Accounts\Entry;

use App\Services\Accounts\Entry\ConfigBasedEntryRegistry;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin.admin')]
class EntryCategory extends Component
{
    public string $category = '';

    public function mount(string $category, ConfigBasedEntryRegistry $registry): void
    {
        $this->authorizePermission('accounts.entry.category.view');
        abort_unless($registry->getCategory($category), 404);
        $this->category = $category;
    }

    public function render(ConfigBasedEntryRegistry $registry): View
    {
        $categorized = $registry->getCategorized();
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
