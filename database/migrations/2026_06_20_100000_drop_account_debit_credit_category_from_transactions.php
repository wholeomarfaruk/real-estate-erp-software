<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove the denormalized header columns from `transactions`. The double-entry
 * `transaction_lines` table is now the source of truth for per-account debit/credit
 * movements, so `account_id`, `debit`, `credit` and `transaction_category_id` are no
 * longer stored on the parent transaction row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('transactions', 'account_id')) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            }

            if (Schema::hasColumn('transactions', 'transaction_category_id')) {
                $table->dropForeign(['transaction_category_id']);
                $table->dropColumn('transaction_category_id');
            }

            if (Schema::hasColumn('transactions', 'debit')) {
                $table->dropColumn('debit');
            }

            if (Schema::hasColumn('transactions', 'credit')) {
                $table->dropColumn('credit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('transactions', 'account_id')) {
                $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('transactions', 'transaction_category_id')) {
                $table->foreignId('transaction_category_id')->nullable()->after('type')->constrained('transaction_categories')->nullOnDelete();
            }

            if (! Schema::hasColumn('transactions', 'debit')) {
                $table->decimal('debit', 15, 3)->default(0)->after('notes');
            }

            if (! Schema::hasColumn('transactions', 'credit')) {
                $table->decimal('credit', 15, 3)->default(0)->after('debit');
            }
        });
    }
};
