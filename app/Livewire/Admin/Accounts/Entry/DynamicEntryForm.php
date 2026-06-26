<?php

namespace App\Livewire\Admin\Accounts\Entry;

use App\DTOs\Accounts\EntryDefinition;
use App\Services\Accounts\Entry\ConfigBasedEntryRegistry;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin.admin')]
class DynamicEntryForm extends Component
{
    public string $category = '';
    public string $slug = '';
    public ?EntryDefinition $entryDef = null;

    public function mount(string $category, string $slug, ConfigBasedEntryRegistry $registry): void
    {
        $entry = $registry->find($slug);
        abort_if($entry === null, 404);
        abort_unless($entry->categoryKey === $category, 404);

        $permission = $entry->permission;
        abort_unless(auth()->user()?->can($permission), 403);

        $this->category = $category;
        $this->slug = $slug;
        $this->entryDef = $entry;

        // If entry has a route override, redirect immediately
        if ($entry->routeOverride) {
            $this->redirect(
                route($entry->routeOverride['name'], $entry->routeOverride['params'] ?? []),
                navigate: true
            );
        }
    }

    public function render(): View
    {
        return view('livewire.admin.accounts.entry.dynamic-entry-form', [
            'entryDef' => $this->entryDef,
        ]);
    }
}
