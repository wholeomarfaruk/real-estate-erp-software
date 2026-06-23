<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['key' => 'project_expense', 'label' => 'Project Expense', 'sort_order' => 1],
            ['key' => 'office_expense', 'label' => 'Office Expense', 'sort_order' => 2],
            ['key' => 'marketing_expense', 'label' => 'Marketing Expense', 'sort_order' => 3],
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(
                ['key' => $feature['key']],
                [
                    'label' => $feature['label'],
                    'is_locked' => true,
                    'is_active' => true,
                    'sort_order' => $feature['sort_order'],
                ]
            );
        }
    }
}
