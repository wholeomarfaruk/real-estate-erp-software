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
    public bool $showEditModal = false;
    public ?int $editingCategoryId = null;

    protected $listeners = [
        'expenseCategoryCreated' => 'handleCategoryCreated',
        'expenseCategoryUpdated' => 'handleCategoryUpdated',
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

    public function openEditModal(int $categoryId): void
    {
        $category = ExpenseCategory::findOrFail($categoryId);

        if ($category->isLocked()) {
            $this->dispatch('toast', type: 'error', message: 'Locked categories cannot be edited.');
            return;
        }

        $this->editingCategoryId = $categoryId;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingCategoryId = null;
    }

    public function handleCategoryUpdated(): void
    {
        $this->closeEditModal();
    }

    public function deleteCategory(int $categoryId): void
    {
        $this->authorizePermission('accounts.expense.delete');

        $category = ExpenseCategory::findOrFail($categoryId);

        if ($category->isLocked()) {
            $this->dispatch('toast', type: 'error', message: 'Locked categories cannot be deleted.');
            return;
        }

        $category->delete();

        $this->dispatch('toast', type: 'success', message: 'Expense category deleted successfully');
    }

    public function render(): View
    {
        $expenseCategories = ExpenseCategory::active()->ordered()->get();

        return view('livewire.admin.accounts.expense.expense-list', compact(
            'expenseCategories'
        ))->layout('layouts.admin.admin');
    }
}
