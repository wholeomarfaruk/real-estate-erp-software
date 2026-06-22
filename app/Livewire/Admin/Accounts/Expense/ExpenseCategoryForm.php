<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\ExpenseCategory;
use App\Models\TransactionCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ExpenseCategoryForm extends Component
{
    use InteractsWithAccountsAccess;

    public string $slug = '';
    public string $name = '';
    public string $description = '';
    public string $icon = 'folder';
    public string $color = 'bg-gray-50 border-gray-200 text-gray-700';
    public ?int $transaction_category_id = null;

    protected $rules = [
        'slug' => 'required|string|lowercase|max:80|unique:expense_categories,slug',
        'name' => 'required|string|max:120',
        'description' => 'nullable|string|max:500',
        'icon' => 'required|string|max:50',
        'color' => 'required|string|max:100',
        'transaction_category_id' => 'nullable|integer|exists:transaction_categories,id',
    ];

    public function mount(): void
    {
        $this->authorizePermission('accounts.expense.create');
    }

    public function save(): void
    {
        try {
            $this->authorizePermission('accounts.expense.create');

            $this->validate();

            ExpenseCategory::create([
                'slug' => $this->slug,
                'name' => $this->name,
                'description' => $this->description,
                'icon' => $this->icon,
                'color' => $this->color,
                'transaction_category_id' => $this->transaction_category_id,
                'is_locked' => false,
                'is_active' => true,
                'sort_order' => ExpenseCategory::max('sort_order') + 1 ?? 1,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->dispatch('toast', type: 'success', message: 'Expense category created successfully');
            $this->dispatch('expenseCategoryCreated');
            $this->closeModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function closeModal(): void
    {
        $this->reset();
        $this->dispatch('closeExpenseCategoryModal');
    }

    public function render(): View
    {
        $iconOptions = [
            'folder' => 'Folder',
            'briefcase' => 'Briefcase',
            'building' => 'Building',
            'megaphone' => 'Megaphone',
            'cog' => 'Settings',
            'tool' => 'Tool',
            'box' => 'Box',
            'package' => 'Package',
            'tag' => 'Tag',
            'layers' => 'Layers',
        ];

        $colorOptions = [
            'bg-blue-50 border-blue-200 text-blue-700' => 'Blue',
            'bg-purple-50 border-purple-200 text-purple-700' => 'Purple',
            'bg-orange-50 border-orange-200 text-orange-700' => 'Orange',
            'bg-green-50 border-green-200 text-green-700' => 'Green',
            'bg-red-50 border-red-200 text-red-700' => 'Red',
            'bg-pink-50 border-pink-200 text-pink-700' => 'Pink',
            'bg-indigo-50 border-indigo-200 text-indigo-700' => 'Indigo',
            'bg-yellow-50 border-yellow-200 text-yellow-700' => 'Yellow',
            'bg-gray-50 border-gray-200 text-gray-700' => 'Gray',
        ];

        $transactionCategories = TransactionCategory::where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('livewire.admin.accounts.expense.expense-category-form', [
            'iconOptions' => $iconOptions,
            'colorOptions' => $colorOptions,
            'transactionCategories' => $transactionCategories,
        ]);
    }
}
