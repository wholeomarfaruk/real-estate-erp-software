<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_funds', function (Blueprint $table): void {
            // Drop old payment-method column — method is now stored in transactions.method
            if (Schema::hasColumn('purchase_funds', 'release_type')) {
                $table->dropColumn('release_type');
            }

            // Link to the new single-row Transaction entry
            if (! Schema::hasColumn('purchase_funds', 'transaction_id')) {
                $table->unsignedBigInteger('transaction_id')->nullable()->after('purchase_order_id');
                $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_funds', function (Blueprint $table): void {
            if (Schema::hasColumn('purchase_funds', 'transaction_id')) {
                $table->dropForeign(['transaction_id']);
                $table->dropColumn('transaction_id');
            }

            if (! Schema::hasColumn('purchase_funds', 'release_type')) {
                $table->string('release_type', 20)->nullable()->after('purchase_order_id');
            }
        });
    }
};
