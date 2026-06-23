<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\FeatureAccountMapping;
use Illuminate\Database\Seeder;

class FeatureAccountMappingSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing mappings first
        FeatureAccountMapping::truncate();

        $this->seedProjectExpenseFeature();
        $this->seedOfficeExpenseFeature();
        $this->seedMarketingExpenseFeature();
    }

    private function seedProjectExpenseFeature(): void
    {
        // Get root expense parent
        $rootParent = Account::where('group', 'expense')
            ->whereNull('parent_id')
            ->first();

        if (!$rootParent) {
            return;
        }

        // Get Project Expenses parent
        $projectExpensesParent = $rootParent->children()
            ->where('name', 'Project Expenses')
            ->where('is_active', true)
            ->first();

        if (!$projectExpensesParent) {
            return;
        }

        // Add all leaf children of Project Expenses
        foreach ($projectExpensesParent->children()->where('is_active', true)->get() as $child) {
            FeatureAccountMapping::create([
                'feature_key' => 'project_expense',
                'parent_account_id' => $rootParent->id,
                'child_account_id' => $child->id,
                'is_enabled' => true,
            ]);
        }
    }

    private function seedOfficeExpenseFeature(): void
    {
        // Get root expense parent
        $rootParent = Account::where('group', 'expense')
            ->whereNull('parent_id')
            ->first();

        if (!$rootParent) {
            return;
        }

        // Get Office Expenses parent
        $officeExpensesParent = $rootParent->children()
            ->where('name', 'Office Expenses')
            ->where('is_active', true)
            ->first();

        if (!$officeExpensesParent) {
            return;
        }

        // Add all leaf children of Office Expenses
        foreach ($officeExpensesParent->children()->where('is_active', true)->get() as $child) {
            FeatureAccountMapping::create([
                'feature_key' => 'office_expense',
                'parent_account_id' => $rootParent->id,
                'child_account_id' => $child->id,
                'is_enabled' => true,
            ]);
        }
    }

    private function seedMarketingExpenseFeature(): void
    {
        // Get root expense parent
        $rootParent = Account::where('group', 'expense')
            ->whereNull('parent_id')
            ->first();

        if (!$rootParent) {
            return;
        }

        // Get Marketing Expenses parent
        $marketingExpensesParent = $rootParent->children()
            ->where('name', 'Marketing Expenses')
            ->where('is_active', true)
            ->first();

        if (!$marketingExpensesParent) {
            return;
        }

        // Add all leaf children of Marketing Expenses
        foreach ($marketingExpensesParent->children()->where('is_active', true)->get() as $child) {
            FeatureAccountMapping::create([
                'feature_key' => 'marketing_expense',
                'parent_account_id' => $rootParent->id,
                'child_account_id' => $child->id,
                'is_enabled' => true,
            ]);
        }
    }
}
