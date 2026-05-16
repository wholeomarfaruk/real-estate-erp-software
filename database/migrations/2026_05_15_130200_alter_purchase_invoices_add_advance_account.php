<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds advance_account_id to purchase_invoices so the approval journal entry
 * can credit the correct Advance account when an advance was pre-released.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('purchase_invoices', 'advance_account_id')) {
                $table->foreignId('advance_account_id')
                    ->nullable()
                    ->after('payment_account_id')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table): void {
            if (Schema::hasColumn('purchase_invoices', 'advance_account_id')) {
                $table->dropForeign(['advance_account_id']);
                $table->dropColumn('advance_account_id');
            }
        });
    }
};
