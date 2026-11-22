<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove transaction categories.
 *
 * Categories stopped affecting the ledger when transactions.transaction_category_id
 * was dropped (2026_06_20_100000). What was left were labels on requests / funds /
 * old expenses and the cost-item picker on project estimates. The Chart of Accounts
 * covers all of it: estimate lines now pick an expense account (estimate_items.account_id),
 * the same accounts actual project costs post to.
 */
return new class extends Migration
{
    private const TABLES = ['banking_payment_requests', 'purchase_funds', 'estimate_items', 'expenses'];

    public function up(): void
    {
        if (Schema::hasTable('estimate_items') && ! Schema::hasColumn('estimate_items', 'account_id')) {
            Schema::table('estimate_items', function (Blueprint $table) {
                $table->foreignId('account_id')->nullable()->after('material_id')
                    ->constrained('accounts')->nullOnDelete();
            });
        }

        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'transaction_category_id')) {
                continue;
            }

            $hasForeign = collect(Schema::getForeignKeys($tableName))
                ->contains(fn ($fk) => $fk['columns'] === ['transaction_category_id']);

            Schema::table($tableName, function (Blueprint $table) use ($hasForeign) {
                if ($hasForeign) {
                    $table->dropForeign(['transaction_category_id']);
                }
                $table->dropColumn('transaction_category_id');
            });
        }

        Schema::dropIfExists('transaction_categories');
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaction_categories')) {
            Schema::create('transaction_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('type')->nullable();
                $table->string('slug')->unique();
                $table->string('code')->nullable();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_locked')->default(false);
                $table->foreignId('parent_id')->nullable()->constrained('transaction_categories')->nullOnDelete();
                $table->timestamps();
            });
        }

        foreach (self::TABLES as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'transaction_category_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('transaction_category_id')->nullable()
                        ->constrained('transaction_categories')->nullOnDelete();
                });
            }
        }

        if (Schema::hasColumn('estimate_items', 'account_id')) {
            Schema::table('estimate_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('account_id');
            });
        }
    }
};
