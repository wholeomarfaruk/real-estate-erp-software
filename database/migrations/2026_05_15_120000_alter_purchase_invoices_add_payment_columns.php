<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the three payment workflow columns introduced by the revised invoice
 * approval flow.  All other columns (advance_adjusted_amount, accounting FKs,
 * workflow audit columns) were already present in the live table when this
 * migration was created and are therefore skipped.
 *
 * Missing columns added here:
 *   payment_account_id, payment_id, payment_method
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table): void {

            if (! Schema::hasColumn('purchase_invoices', 'payment_account_id')) {
                $table->foreignId('payment_account_id')
                    ->nullable()
                    ->after('accounts_payable_account_id')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_invoices', 'payment_id')) {
                $table->foreignId('payment_id')
                    ->nullable()
                    ->after('purchase_payable_id')
                    ->constrained('payments')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_invoices', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('payment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table): void {
            if (Schema::hasColumn('purchase_invoices', 'payment_id')) {
                $table->dropForeign(['payment_id']);
            }
            if (Schema::hasColumn('purchase_invoices', 'payment_account_id')) {
                $table->dropForeign(['payment_account_id']);
            }

            $cols = array_filter(
                ['payment_account_id', 'payment_id', 'payment_method'],
                fn ($c) => Schema::hasColumn('purchase_invoices', $c)
            );

            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
