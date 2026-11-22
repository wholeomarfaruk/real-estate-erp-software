<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Journal Posting:
 *  - journal_posting_groups: editable / orderable categories (built-in ones are system rows)
 *  - items: behaviour settings, custom fields and a voucher number sequence
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_posting_groups', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->string('icon', 40)->default('custom');
            $table->string('tone', 20)->default('indigo');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('journal_posting_items', function (Blueprint $table) {
            $table->json('behaviour')->nullable()->after('field_settings');
            $table->json('custom_fields')->nullable()->after('behaviour');
            $table->unsignedInteger('voucher_seq')->default(0)->after('custom_fields');
            $table->unsignedSmallInteger('voucher_seq_year')->nullable()->after('voucher_seq');
        });

        $now = now();
        $system = [
            ['expense',  'Expenses', 'Project, office, marketing and other costs.', 'expense',  'rose'],
            ['contra',   'Contra',   'Fund movement between cash and bank.',        'contra',   'sky'],
            ['sales',    'Sales',    'Property sales, invoices and installments.',  'sales',    'emerald'],
            ['payment',  'Payments', 'Outgoing payments, salaries and refunds.',    'payment',  'amber'],
            ['purchase', 'Purchase', 'Material purchases and supplier returns.',    'purchase', 'violet'],
            ['journal',  'Journal',  'Adjustments, opening balances and corrections.', 'journal', 'slate'],
        ];
        foreach ($system as $i => [$key, $name, $desc, $icon, $tone]) {
            DB::table('journal_posting_groups')->insert([
                'key' => $key, 'name' => $name, 'description' => $desc, 'icon' => $icon, 'tone' => $tone,
                'sort_order' => $i + 1, 'is_active' => true, 'is_system' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Custom categories that so far only lived on items.
        $order = count($system);
        $custom = DB::table('journal_posting_items')
            ->select('group_key', DB::raw('MAX(group_name) as group_name'), DB::raw('MIN(icon) as icon'))
            ->whereNotIn('group_key', array_column($system, 0))
            ->groupBy('group_key')
            ->get();
        foreach ($custom as $g) {
            DB::table('journal_posting_groups')->insert([
                'key' => $g->group_key,
                'name' => $g->group_name ?: Str::headline(Str::after($g->group_key, 'custom-')),
                'description' => null, 'icon' => $g->icon ?: 'custom', 'tone' => 'indigo',
                'sort_order' => ++$order, 'is_active' => true, 'is_system' => false, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('journal_posting_items', function (Blueprint $table) {
            $table->dropColumn(['behaviour', 'custom_fields', 'voucher_seq', 'voucher_seq_year']);
        });
        Schema::dropIfExists('journal_posting_groups');
    }
};
