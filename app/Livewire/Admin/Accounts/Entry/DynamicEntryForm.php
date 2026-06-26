<?php

namespace App\Livewire\Admin\Accounts\Entry;

use App\Models\AccountEntryType;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin.admin')]
class DynamicEntryForm extends Component
{
    public string $category = '';
    public string $slug = '';
    public ?AccountEntryType $entryType = null;

    public function mount(string $category, string $slug): void
    {
        $entry = AccountEntryType::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless($entry->category_key === $category, 404);
        abort_unless(auth()->user()?->can($entry->permission), 403);

        $this->category = $category;
        $this->slug = $slug;
        $this->entryType = $entry;
    }

    public function render(): View
    {
        return view('livewire.admin.accounts.entry.dynamic-entry-form', [
            'entryType' => $this->entryType,
        ]);
    }
}
