<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_consumptions', function (Blueprint $table) {
            $table->foreignId('transaction_id')
                ->nullable()
                ->after('posted_at')
                ->constrained('transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_consumptions', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['transaction_id']);
            $table->dropColumn('transaction_id');
        });
    }
};
