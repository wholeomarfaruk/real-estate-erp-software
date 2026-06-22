<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all accounting events with transaction_type='purchase_invoice' to 'purchase'
        DB::table('accounting_events')
            ->where('transaction_type', 'purchase_invoice')
            ->update(['transaction_type' => 'purchase']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert purchase back to purchase_invoice
        DB::table('accounting_events')
            ->where('transaction_type', 'purchase')
            ->update(['transaction_type' => 'purchase_invoice']);
    }
};
