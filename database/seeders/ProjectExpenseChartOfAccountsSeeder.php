<?php

namespace Database\Seeders;

use App\Enums\Accounts\AccountGroupType;
use App\Enums\Accounts\AccountType;
use App\Models\Account;
use Illuminate\Database\Seeder;

class ProjectExpenseChartOfAccountsSeeder extends Seeder
{
    /**
     * Seed the Project Expense Chart of Accounts
     *
     * Structure:
     * Expenses
     * └── Project Expenses
     *     ├── Labor Cost
     *     ├── Material Consumption
     *     ├── Utility Bill
     *     ├── Equipment Rent
     *     ├── Transportation
     *     └── Other Expense
     */
    public function run(): void
    {
        $parentExpense = Account::where('code', 'EXP')->firstOrFail();

        $projectExpensesParent = Account::firstOrCreate(
            ['code' => '1100'],
            [
                'name' => 'Project Expenses',
                'type' => AccountType::LEDGER->value,
                'group' => AccountGroupType::EXPENSE->value,
                'parent_id' => $parentExpense->id,
                'is_active' => true,
                'is_locked' => false,
            ]
        );

        $accounts = [
            ['code' => '1110', 'name' => 'Labor Cost'],
            ['code' => '1120', 'name' => 'Material Consumption'],
            ['code' => '1130', 'name' => 'Utility Bill'],
            ['code' => '1140', 'name' => 'Equipment Rent'],
            ['code' => '1150', 'name' => 'Transportation'],
            ['code' => '1160', 'name' => 'Other Expense'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => AccountType::LEDGER->value,
                    'group' => AccountGroupType::EXPENSE->value,
                    'parent_id' => $projectExpensesParent->id,
                    'is_active' => true,
                    'is_locked' => false,
                ]
            );
        }
    }
}
