<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all transactions with type='purchase_invoice' to type='purchase'
        DB::table('transactions')
            ->where('type', 'purchase_invoice')
            ->update(['type' => 'purchase']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert purchase back to purchase_invoice
        DB::table('transactions')
            ->where('type', 'purchase')
            ->update(['type' => 'purchase_invoice']);
    }
};
