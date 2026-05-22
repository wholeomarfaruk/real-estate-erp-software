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
    'income' => [
        'Property Sale',
        'Property Rent',
        'Booking Money',
        'Installment Receive',
        'Service Income',
        'Commission Income',
        'Other Income',
    ],

    'expense' => [
        'Project Expense' => [
            'Material Purchase',
            'Labor Cost',
            'Contractor Payment',
            'Utility Bills',
            'Transport & Fuel',
            'Maintenance',
            'Equipment Rent',
            'Security Expense',
        ],

        'Office Expense' => [
            'Office Rent',
            'Internet & Communication',
            'Printing & Stationery',
            'Software Subscription',
        ],

        'Payroll' => [
            'Staff Salary',
            'Bonus',
            'Allowance',
        ],

        'Marketing' => [
            'Advertisement',
            'Campaign Expense',
            'Client Entertainment',
        ],

        'Government & Legal' => [
            'Trade License',
            'VAT & Tax',
            'Registration Fees',
            'Legal Fees',
        ],

        'Others' => [],
    ],

    'advance' => [
        'Employee Advance',
        'Supplier Advance',
        'Customer Advance',
    ],

    'transfer' => [
        'Bank Transfer',
        'Cash Transfer',
        'MFS Transfer',
    ],

    'adjustment' => [
        'Advance Adjustment',
        'Due Adjustment',
        'Correction',
        'Balance Adjustment',
    ],

    'opening_balance' => [],
];

      foreach ($structure as $type => $categories) {

    // ROOT TYPE
    $typeRoot = TransactionCategory::updateOrCreate(
        [
            'slug' => Str::slug($type),
        ],
        [
            'name' => Str::headline($type),
            'type' => $type,
            'parent_id' => null,
            'is_active' => true,
            'is_locked' => true,
        ]
    );

    foreach ($categories as $category => $children) {

        // SIMPLE CHILD ITEM
        if (is_int($category)) {

            TransactionCategory::updateOrCreate(
                [
                    'slug' => Str::slug($children),
                ],
                [
                    'name' => $children,
                    'type' => $type,
                    'parent_id' => $typeRoot->id,
                    'is_active' => true,
                    'is_locked' => true,
                ]
            );

            continue;
        }

        // CATEGORY
        $categoryItem = TransactionCategory::updateOrCreate(
            [
                'slug' => Str::slug($category),
            ],
            [
                'name' => $category,
                'type' => $type,
                'parent_id' => $typeRoot->id,
                'is_active' => true,
                'is_locked' => true,
            ]
        );

        // SUB CATEGORY
        foreach ($children as $childName) {

            TransactionCategory::updateOrCreate(
                [
                    'slug' => Str::slug($childName),
                ],
                [
                    'name' => $childName,
                    'type' => $type,
                    'parent_id' => $categoryItem->id,
                    'is_active' => true,
                    'is_locked' => true,
                ]
            );
        }
    }
}
    }
}
