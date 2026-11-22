<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Models\ExpenseCategory;
use App\Models\JournalPostingItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DynamicExpenseForm extends Component
{
    public ?ExpenseCategory $category = null;
    public ?string $formComponent = null;

    public function mount(string $category = null): void
    {
        $category = $category ?: request()->query('category');
        $projectId = (int) request()->query('project_id') ?: null;

        // "Add expense" from a project page has no category → the Project Expense item.
        if (! $category && $projectId) {
            $item = JournalPostingItem::where('slug', 'project-expense')->first()
                ?? ExpenseCategory::where('slug', 'project')->first()?->journalItem;
            if ($item) {
                $this->redirectRoute('admin.accounts.journal-posting.entry', ['item' => $item->slug, 'project_id' => $projectId], navigate: true);
                return;
            }
            $category = 'project';
        }

        if (!$category) {
            abort(404, 'Expense category not specified');
        }

        $this->category = ExpenseCategory::where('slug', $category)
            ->where('is_active', true)
            ->firstOrFail();

        // A dynamic Journal Posting item replaces this category's form.
        if ($item = $this->category->journalItem) {
            $this->redirectRoute('admin.accounts.journal-posting.entry', array_filter([
                'item' => $item->slug,
                'project_id' => $projectId,
            ]), navigate: true);
            return;
        }

        // Determine which form to load
        if ($this->category->is_locked && $this->category->form_component) {
            // Locked category with dedicated form
            $class = $this->category->form_component;
            if (str_starts_with($class, 'App\\Livewire\\')) {
                $relative = substr($class, strlen('App\\Livewire\\'));
                $segments = explode('\\', $relative);
                $kebabSegments = array_map(fn($seg) => \Illuminate\Support\Str::kebab($seg), $segments);
                $this->formComponent = implode('.', $kebabSegments);
            } else {
                $this->formComponent = $class;
            }
        } else {
            // Dynamic category - use generic form
            $this->formComponent = 'admin.accounts.expense.generic-expense-form';
        }
    }

    public function render(): View
    {
        return view('livewire.admin.accounts.expense.dynamic-expense-form', [
            'category' => $this->category,
        ])->layout('layouts.admin.admin');
    }
}
