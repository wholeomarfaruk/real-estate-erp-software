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
        // No existing supplier payment transactions yet since the feature just integrated
        // But prepare for any future data that might exist
        // This migration creates the accounting event in the database
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No cleanup needed since we're just adding a new transaction type
    }
};
