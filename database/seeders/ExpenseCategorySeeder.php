<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Seed locked categories (hardcoded pages)
        ExpenseCategory::upsert([
            [
                'slug' => 'project',
                'name' => 'Project Expense',
                'description' => 'Labor, Material, Utility, Equipment Rent, Transportation, and other project-related costs.',
                'icon' => 'building',
                'color' => 'bg-blue-50 border-blue-200 text-blue-700',
                'feature_type' => 'project_expense',
                'form_component' => 'App\Livewire\Admin\Accounts\Expense\ProjectExpenseForm',
                'transaction_category_id' => null,
                'is_locked' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'office',
                'name' => 'Office Expense',
                'description' => 'Office Rent, Salary, Internet, Electricity, Maintenance, and Administrative expenses.',
                'icon' => 'briefcase',
                'color' => 'bg-purple-50 border-purple-200 text-purple-700',
                'feature_type' => 'office_expense',
                'form_component' => 'App\Livewire\Admin\Accounts\Expense\OfficeExpenseForm',
                'transaction_category_id' => null,
                'is_locked' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'marketing',
                'name' => 'Marketing Expense',
                'description' => 'Advertising, Promotion, Campaign, Commission, and Branding expenses.',
                'icon' => 'megaphone',
                'color' => 'bg-orange-50 border-orange-200 text-orange-700',
                'feature_type' => 'marketing_expense',
                'form_component' => 'App\Livewire\Admin\Accounts\Expense\MarketingExpenseForm',
                'transaction_category_id' => null,
                'is_locked' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ], 'slug');
    }
}
