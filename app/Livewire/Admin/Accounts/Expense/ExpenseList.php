<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ExpenseList extends Component
{
    use InteractsWithAccountsAccess;

    public bool $showCreateModal = false;

    protected $listeners = [
        'expenseCategoryCreated' => 'handleCategoryCreated',
    ];

    public function mount(): void
    {
        $this->authorizePermission('accounts.expense.list');
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function handleCategoryCreated(): void
    {
        $this->closeCreateModal();
    }

    public function render(): View
    {
        $expenseCategories = ExpenseCategory::active()->ordered()->get();

        return view('livewire.admin.accounts.expense.expense-list', compact(
            'expenseCategories'
        ))->layout('layouts.admin.admin');
    }
}
