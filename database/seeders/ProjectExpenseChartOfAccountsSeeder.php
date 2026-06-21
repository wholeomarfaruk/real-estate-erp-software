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
     * Account Structure:
     * 1100 - PROJECT EXPENSES (Parent)
     * ├── 1110 - LABOR COST
     * │   ├── 1111 - Skilled Labor
     * │   ├── 1112 - Unskilled Labor
     * │   └── 1113 - Supervisor/Management Labor
     * ├── 1120 - MATERIAL CONSUMPTION
     * │   ├── 1121 - Concrete
     * │   ├── 1122 - Steel Reinforcement
     * │   ├── 1123 - Brick & Masonry
     * │   ├── 1124 - Electrical Materials
     * │   ├── 1125 - Plumbing Materials
     * │   └── 1126 - Finishing Materials
     * ├── 1130 - UTILITY BILLS
     * │   ├── 1131 - Electricity
     * │   ├── 1132 - Water
     * │   └── 1133 - Gas
     * ├── 1140 - EQUIPMENT RENT
     * │   ├── 1141 - Machinery Rent
     * │   ├── 1142 - Vehicle Rent
     * │   └── 1143 - Tool & Equipment Rent
     * ├── 1150 - TRANSPORTATION
     * │   ├── 1151 - Material Transport
     * │   ├── 1152 - Worker Transport
     * │   └── 1153 - Equipment Transport
     * └── 1160 - OTHER EXPENSE
     *     ├── 1161 - Permits & Licenses
     *     ├── 1162 - Site Maintenance
     *     ├── 1163 - Safety Equipment
     *     └── 1164 - Miscellaneous
     */
    public function run(): void
    {
        // Get or create the parent Expenses account
        $parentExpense = Account::where('code', 'EXP')->firstOrFail();

        // Create Project Expenses parent account (1100)
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

        // Define the complete hierarchy
        $accounts = [
            // 1110 - LABOR COST
            [
                'code' => '1110',
                'name' => 'Labor Cost',
                'parent' => $projectExpensesParent->id,
                'is_header' => true,
                'children' => [
                    ['code' => '1111', 'name' => 'Skilled Labor', 'parent' => '1110'],
                    ['code' => '1112', 'name' => 'Unskilled Labor', 'parent' => '1110'],
                    ['code' => '1113', 'name' => 'Supervisor/Management Labor', 'parent' => '1110'],
                ]
            ],
            // 1120 - MATERIAL CONSUMPTION
            [
                'code' => '1120',
                'name' => 'Material Consumption',
                'parent' => $projectExpensesParent->id,
                'is_header' => true,
                'children' => [
                    ['code' => '1121', 'name' => 'Concrete', 'parent' => '1120'],
                    ['code' => '1122', 'name' => 'Steel Reinforcement', 'parent' => '1120'],
                    ['code' => '1123', 'name' => 'Brick & Masonry', 'parent' => '1120'],
                    ['code' => '1124', 'name' => 'Electrical Materials', 'parent' => '1120'],
                    ['code' => '1125', 'name' => 'Plumbing Materials', 'parent' => '1120'],
                    ['code' => '1126', 'name' => 'Finishing Materials', 'parent' => '1120'],
                ]
            ],
            // 1130 - UTILITY BILLS
            [
                'code' => '1130',
                'name' => 'Utility Bills',
                'parent' => $projectExpensesParent->id,
                'is_header' => true,
                'children' => [
                    ['code' => '1131', 'name' => 'Electricity', 'parent' => '1130'],
                    ['code' => '1132', 'name' => 'Water', 'parent' => '1130'],
                    ['code' => '1133', 'name' => 'Gas', 'parent' => '1130'],
                ]
            ],
            // 1140 - EQUIPMENT RENT
            [
                'code' => '1140',
                'name' => 'Equipment Rent',
                'parent' => $projectExpensesParent->id,
                'is_header' => true,
                'children' => [
                    ['code' => '1141', 'name' => 'Machinery Rent', 'parent' => '1140'],
                    ['code' => '1142', 'name' => 'Vehicle Rent', 'parent' => '1140'],
                    ['code' => '1143', 'name' => 'Tool & Equipment Rent', 'parent' => '1140'],
                ]
            ],
            // 1150 - TRANSPORTATION
            [
                'code' => '1150',
                'name' => 'Transportation',
                'parent' => $projectExpensesParent->id,
                'is_header' => true,
                'children' => [
                    ['code' => '1151', 'name' => 'Material Transport', 'parent' => '1150'],
                    ['code' => '1152', 'name' => 'Worker Transport', 'parent' => '1150'],
                    ['code' => '1153', 'name' => 'Equipment Transport', 'parent' => '1150'],
                ]
            ],
            // 1160 - OTHER EXPENSE
            [
                'code' => '1160',
                'name' => 'Other Expense',
                'parent' => $projectExpensesParent->id,
                'is_header' => true,
                'children' => [
                    ['code' => '1161', 'name' => 'Permits & Licenses', 'parent' => '1160'],
                    ['code' => '1162', 'name' => 'Site Maintenance', 'parent' => '1160'],
                    ['code' => '1163', 'name' => 'Safety Equipment', 'parent' => '1160'],
                    ['code' => '1164', 'name' => 'Miscellaneous', 'parent' => '1160'],
                ]
            ],
        ];

        // Keep track of parent accounts for children lookup
        $accountCache = [];

        // First pass: Create parent/header accounts
        foreach ($accounts as $account) {
            $parent = Account::firstOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => AccountType::LEDGER->value,
                    'group' => AccountGroupType::EXPENSE->value,
                    'parent_id' => $account['parent'],
                    'is_active' => true,
                    'is_locked' => false,
                ]
            );

            $accountCache[$account['code']] = $parent->id;
        }

        // Second pass: Create child/leaf accounts
        foreach ($accounts as $account) {
            if (isset($account['children'])) {
                $parentId = $accountCache[$account['code']];

                foreach ($account['children'] as $child) {
                    Account::firstOrCreate(
                        ['code' => $child['code']],
                        [
                            'name' => $child['name'],
                            'type' => AccountType::LEDGER->value,
                            'group' => AccountGroupType::EXPENSE->value,
                            'parent_id' => $parentId,
                            'is_active' => true,
                            'is_locked' => false,
                        ]
                    );
                }
            }
        }
    }
}
