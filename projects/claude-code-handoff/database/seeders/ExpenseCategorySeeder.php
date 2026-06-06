<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Mason Labour',        'type' => 'labour'],
            ['name' => 'Helper Labour',       'type' => 'labour'],
            ['name' => 'Electrician Labour',  'type' => 'labour'],
            ['name' => 'Plumber Labour',      'type' => 'labour'],
            ['name' => 'Transport',           'type' => 'other'],
            ['name' => 'Generator Fuel',      'type' => 'other'],
            ['name' => 'Security',            'type' => 'other'],
            ['name' => 'Site Office Expense', 'type' => 'other'],
            ['name' => 'Consultant Fee',      'type' => 'other'],
            ['name' => 'Equipment Rent',      'type' => 'other'],
            ['name' => 'Utility',             'type' => 'other'],
            ['name' => 'Miscellaneous',       'type' => 'other'],
        ];

        foreach ($categories as $c) {
            ExpenseCategory::updateOrCreate(
                ['slug' => Str::slug($c['name'])],
                ['name' => $c['name'], 'type' => $c['type'], 'is_active' => true],
            );
        }
    }
}
