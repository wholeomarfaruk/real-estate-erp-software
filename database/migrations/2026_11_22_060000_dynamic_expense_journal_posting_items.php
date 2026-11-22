<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Marketing / Office / Project expense as dynamic Journal Posting items.
 *
 * The locked expense categories open hard-coded forms with no field rules,
 * approval options, voucher numbering or custom fields. These items post the
 * same double entry (Dr the feature's expense heads / Cr cash & bank) through
 * the configurable entry form. The hub hides a locked category once an
 * Expenses item debits the same feature; the old forms stay on the Expenses page.
 */
return new class extends Migration
{
    private function items(): array
    {
        $fields = [
            'profile' => 'optional', 'party_name' => 'optional', 'party_phone' => 'optional',
            'reference_no' => 'optional', 'voucher_no' => 'optional', 'notes' => 'optional',
        ];

        return [
            [
                'slug'             => 'project-expense',
                'label'            => 'Project Expense',
                'description'      => 'Labor, material, utility, equipment rent, transportation and other project costs.',
                'icon'             => 'building',
                'color'            => 'bg-blue-50 border-blue-200 text-blue-700',
                'feature'          => 'project_expense',
                'transaction_type' => 'expense',
                'field_settings'   => $fields + ['project' => 'required'],
            ],
            [
                'slug'             => 'office-expense',
                'label'            => 'Office Expense',
                'description'      => 'Office rent, internet, electricity, maintenance and administrative expenses.',
                'icon'             => 'briefcase',
                'color'            => 'bg-purple-50 border-purple-200 text-purple-700',
                'feature'          => 'office_expense',
                'transaction_type' => 'office_expense',
                'field_settings'   => $fields + ['project' => 'hidden'],
            ],
            [
                'slug'             => 'marketing-expense',
                'label'            => 'Marketing Expense',
                'description'      => 'Advertising, promotion, campaign, commission and branding expenses.',
                'icon'             => 'megaphone',
                'color'            => 'bg-orange-50 border-orange-200 text-orange-700',
                'feature'          => 'marketing_expense',
                // There is no marketing_expense transaction type — a plain expense.
                'transaction_type' => 'expense',
                'field_settings'   => $fields + ['project' => 'hidden'],
            ],
        ];
    }

    public function up(): void
    {
        $now = now();
        $groupName = DB::table('journal_posting_groups')->where('key', 'expense')->value('name');
        $sort = (int) DB::table('journal_posting_items')->where('group_key', 'expense')->max('sort_order');

        foreach ($this->items() as $item) {
            if (DB::table('journal_posting_items')->where('slug', $item['slug'])->exists()) {
                continue;
            }

            DB::table('journal_posting_items')->insert([
                'group_key'        => 'expense',
                'group_name'       => $groupName,
                'slug'             => $item['slug'],
                'label'            => $item['label'],
                'description'      => $item['description'],
                'icon'             => $item['icon'],
                'color'            => $item['color'],
                'feature_type'     => $item['feature'],
                'debit_source'     => 'feature:' . $item['feature'],
                'credit_source'    => 'cashbank',
                'transaction_type' => $item['transaction_type'],
                'url'              => null,
                'field_settings'   => json_encode($item['field_settings']),
                'behaviour'        => json_encode(['requires_approval' => true, 'default_method' => 'cash']),
                'custom_fields'    => json_encode([]),
                'sort_order'       => ++$sort,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('journal_posting_items')
            ->whereIn('slug', array_column($this->items(), 'slug'))
            ->delete();
    }
};
