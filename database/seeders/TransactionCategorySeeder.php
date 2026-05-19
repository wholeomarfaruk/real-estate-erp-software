<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            [
                'name' => 'Income',
                'type' => 'income',
                'children' => [
                    'Property Sale',
                    'Property Rent',
                    'Service Income',
                    'Other Income',
                ],
            ],
            [
                'name' => 'Expense',
                'type' => 'expense',
                'children' => [
                    'Project Purchase',
                    'Office Expense',
                    'Staff Salary',
                    'Utility Bills',
                    'Marketing',
                    'Others',
                ],
            ],
            [
                'name' => 'Advance',
                'type' => 'advance',
                'children' => [
                    'Employee Advance',
                    'Supplier Advance',
                    'Customer Advance',
                ],
            ],
            [
                'name' => 'Transfer',
                'type' => 'transfer',
                'children' => [],
            ],
            [
                'name' => 'Adjustment',
                'type' => 'adjustment',
                'children' => [
                    'Advance Adjustment',
                    'Correction',
                ],
            ],
            [
                'name' => 'Opening Balance',
                'type' => 'opening_balance',
                'children' => [],
            ],
        ];

        foreach ($structure as $group) {
            $parent = TransactionCategory::query()->updateOrCreate(
                ['slug' => Str::slug($group['name'])],
                [
                    'name'      => $group['name'],
                    'type'      => $group['type'],
                    'is_active' => true,
                    'is_locked' => true,
                    'parent_id' => null,
                ]
            );

            foreach ($group['children'] as $childName) {
                TransactionCategory::query()->updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'name'      => $childName,
                        'type'      => $group['type'],
                        'is_active' => true,
                        'is_locked' => true,
                        'parent_id' => $parent->id,
                    ]
                );
            }
        }
    }
}
