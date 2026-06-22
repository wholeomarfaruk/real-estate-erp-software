<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ExpenseList extends Component
{
    use InteractsWithAccountsAccess;

    public function mount(): void
    {
        $this->authorizePermission('accounts.expense.list');
    }

    public function render(): View
    {
        $expenseCategories = ExpenseCategory::active()->ordered()->get();

        return view('livewire.admin.accounts.expense.expense-list', compact(
            'expenseCategories'
        ))->layout('layouts.admin.admin');
    }
}
