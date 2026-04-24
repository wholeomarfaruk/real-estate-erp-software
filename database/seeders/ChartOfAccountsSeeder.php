<?php

namespace Database\Seeders;

use App\Enums\Accounts\AccountType;
use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            [
                'name' => 'Assets',
                'type' => AccountType::ASSET->value,
                'children' => [
                    'Cash',
                    'Bank',
                    'Accounts Receivable',
                    'Advance to Supplier',
                    'Employee Advance',
                    'Inventory',
                ],
            ],
            [
                'name' => 'Liabilities',
                'type' => AccountType::LIABILITY->value,
                'children' => [
                    'Accounts Payable',
                    'Supplier Payable',
                    'Salary Payable',
                    'Advance Received',
                ],
            ],
            [
                'name' => 'Income',
                'type' => AccountType::INCOME->value,
                'children' => [
                    'Property Sale',
                    'Rent Income',
                    'Other Income',
                ],
            ],
            [
                'name' => 'Expenses',
                'type' => AccountType::EXPENSE->value,
                'children' => [
                    'Purchase Expense',
                    'Salary Expense',
                    'Project Expense',
                    'Office Expense',
                    'Utility Expense',
                ],
            ],
            [
                'name' => 'Equity',
                'type' => AccountType::EQUITY->value,
                'children' => [
                    'Owner Capital',
                    'Drawings',
                ],
            ],
        ];

        foreach ($structure as $group) {
            $parent = Account::query()->firstOrCreate(
                [
                    'name' => $group['name'],
                    'type' => $group['type'],
                    'parent_id' => null,
                ],
                [
                    'is_active' => true,
                ]
            );

            if (! $parent->is_active) {
                $parent->is_active = true;
                $parent->save();
            }

            foreach ($group['children'] as $childName) {
                $child = Account::query()->firstOrCreate(
                    [
                        'name' => $childName,
                        'type' => $group['type'],
                        'parent_id' => $parent->id,
                    ],
                    [
                        'is_active' => true,
                    ]
                );

                if (! $child->is_active) {
                    $child->is_active = true;
                    $child->save();
                }
            }
        }
    }
}
