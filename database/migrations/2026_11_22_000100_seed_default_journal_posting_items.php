<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $items = [
        ['group_key' => 'contra',  'slug' => 'cash-withdrawal-from-bank', 'label' => 'Cash Withdrawal from Bank', 'description' => 'Withdraw cash from a bank account.',          'transaction_type' => 'transfer'],
        ['group_key' => 'contra',  'slug' => 'bank-to-bank-transfer',     'label' => 'Bank to Bank Transfer',     'description' => 'Move funds between two bank accounts.',        'transaction_type' => 'transfer'],
        ['group_key' => 'journal', 'slug' => 'journal-entry',             'label' => 'Journal Entry',             'description' => 'Manual debit / credit between any accounts.',  'transaction_type' => 'adjustment'],
        ['group_key' => 'journal', 'slug' => 'opening-balance',           'label' => 'Opening Balance',           'description' => 'Set opening balance of an account.',          'transaction_type' => 'opening_balance'],
        ['group_key' => 'journal', 'slug' => 'adjustment-entry',          'label' => 'Adjustment Entry',          'description' => 'Correct or adjust an account balance.',       'transaction_type' => 'adjustment'],
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->items as $i => $item) {
            if (DB::table('journal_posting_items')->where('slug', $item['slug'])->exists()) {
                continue;
            }

            DB::table('journal_posting_items')->insert($item + [
                'icon'       => 'folder',
                'color'      => 'bg-gray-50 border-gray-200 text-gray-700',
                'sort_order' => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('journal_posting_items')->whereIn('slug', array_column($this->items, 'slug'))->delete();
    }
};
