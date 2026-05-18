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
                'children' => [
                    'Property Sale',
                    'Property Rent',
                ],
            ],
            [
                'name' => 'Expense',
                'children' => [
                    'Project Purchase',
                    'Office Expense',
                    'Others',
                ],
            ],
        ];

        foreach ($structure as $group) {
            $parent = TransactionCategory::query()->updateOrCreate(
                ['slug' => Str::slug($group['name'])],
                [
                    'name'      => $group['name'],
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
                        'is_active' => true,
                        'is_locked' => true,
                        'parent_id' => $parent->id,
                    ]
                );
            }
        }
    }
}
