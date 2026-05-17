<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'Income', 'slug' => 'income', 'is_locked' => true],
            ['id' => 2, 'name' => 'Expense', 'slug' => 'expense','is_locked' => true],
        ];
        $children = [
            ['id' => 3, 'name' => 'Sale', 'slug' => 'sale', 'parent_id' => 1, 'is_locked' => true],
            ['id' => 4, 'name' => 'Rent', 'slug' => 'rent', 'parent_id' => 1, 'is_locked' => true],
            ['id' => 5, 'name' => 'Purchase', 'slug' => 'purchase', 'parent_id' => 1,'is_locked' => true],
            ['id' => 6, 'name' => 'Others', 'slug' => 'others', 'parent_id' => 2, 'is_locked' => true],
        ];

        foreach ($categories as $category) {
            \App\Models\TransactionCategory::updateOrCreate(['id' => $category['id']], $category);
        }

        foreach ($children as $child) {
            \App\Models\TransactionCategory::updateOrCreate(['id' => $child['id']], $child);
        }
    }
}
