<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use Illuminate\Database\Seeder;

class ExpenseTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['key' => 'office',    'name' => 'Office Expense'],
            ['key' => 'project',   'name' => 'Project Expense'],
            ['key' => 'supplier',  'name' => 'Supplier Expense'],
            ['key' => 'marketing', 'name' => 'Marketing Expense'],
            ['key' => 'other',     'name' => 'Other Expense'],
        ];

        foreach ($types as $t) {
            ExpenseType::query()->updateOrCreate(['key' => $t['key']], $t);
        }
    }
}
