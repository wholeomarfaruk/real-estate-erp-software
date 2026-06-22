<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DynamicExpenseForm extends Component
{
    public ?ExpenseCategory $category = null;
    public ?string $formComponent = null;

    public function mount(string $category = null): void
    {
        if (!$category) {
            abort(404, 'Expense category not specified');
        }

        $this->category = ExpenseCategory::where('slug', $category)
            ->where('is_active', true)
            ->firstOrFail();

        // Determine which form to load
        if ($this->category->is_locked && $this->category->form_component) {
            // Locked category with dedicated form
            $this->formComponent = $this->category->form_component;
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
