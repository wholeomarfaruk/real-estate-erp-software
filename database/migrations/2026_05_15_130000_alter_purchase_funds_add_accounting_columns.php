<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds accounting columns to purchase_funds so each fund release now
 * creates a proper double-entry journal entry:
 *
 *   advance_type       : employee_advance | supplier_advance
 *   advance_account_id : DR account (Employee / Supplier Advance asset account)
 *   payment_account_id : CR account (Cash / Bank account)
 *   transaction_id     : journal entry posted at release time
 *   payment_id         : payment record linked to the journal
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_funds', function (Blueprint $table): void {

            if (! Schema::hasColumn('purchase_funds', 'advance_type')) {
                $table->string('advance_type', 30)->nullable()->after('release_type');
            }

            if (! Schema::hasColumn('purchase_funds', 'advance_account_id')) {
                $table->foreignId('advance_account_id')
                    ->nullable()
                    ->after('advance_type')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_funds', 'payment_account_id')) {
                $table->foreignId('payment_account_id')
                    ->nullable()
                    ->after('advance_account_id')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_funds', 'transaction_id')) {
                $table->foreignId('transaction_id')
                    ->nullable()
                    ->after('payment_account_id')
                    ->constrained('transactions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_funds', 'payment_id')) {
                $table->foreignId('payment_id')
                    ->nullable()
                    ->after('transaction_id')
                    ->constrained('payments')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_funds', function (Blueprint $table): void {
            foreach (['payment_id', 'transaction_id', 'payment_account_id', 'advance_account_id'] as $col) {
                if (Schema::hasColumn('purchase_funds', $col)) {
                    $table->dropForeign([$col]);
                }
            }

            $drop = array_filter(
                ['advance_type', 'advance_account_id', 'payment_account_id', 'transaction_id', 'payment_id'],
                fn ($c) => Schema::hasColumn('purchase_funds', $c)
            );

            if ($drop) {
                $table->dropColumn(array_values($drop));
            }
        });
    }
};
