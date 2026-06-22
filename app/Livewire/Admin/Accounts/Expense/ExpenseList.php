<?php

namespace App\Livewire\Admin\Accounts\Expense;

use App\Enums\Accounts\TransactionType;
use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Models\BankingPaymentRequest;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use InteractsWithAccountsAccess;

    public function mount(): void
    {
        $this->authorizePermission('accounts.expense.list');
    }

    public function render(): View
    {
        $expenseCategories = [
            [
                'slug' => 'project',
                'name' => 'Project Expense',
                'description' => 'Labor, Material, Utility, Equipment Rent, Transportation, and other project-related costs.',
                'icon' => 'building',
                'color' => 'bg-blue-50 border-blue-200 text-blue-700',
                'route' => 'admin.accounts.expenses.create',
            ],
            [
                'slug' => 'office',
                'name' => 'Office Expense',
                'description' => 'Office Rent, Salary, Internet, Electricity, Maintenance, and Administrative expenses.',
                'icon' => 'briefcase',
                'color' => 'bg-purple-50 border-purple-200 text-purple-700',
                'route' => 'admin.accounts.expenses.office',
            ],
            [
                'slug' => 'marketing',
                'name' => 'Marketing Expense',
                'description' => 'Advertising, Promotion, Campaign, Commission, and Branding expenses.',
                'icon' => 'megaphone',
                'color' => 'bg-orange-50 border-orange-200 text-orange-700',
                'route' => 'admin.accounts.expenses.create',
            ],
        ];

        return view('livewire.admin.accounts.expense.expense-list', compact(
            'expenseCategories'
        ))->layout('layouts.admin.admin');
    }
}
