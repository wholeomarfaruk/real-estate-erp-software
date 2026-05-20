<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            // Drop old composite index and plain source_id column
            if (Schema::hasColumn('banking_payment_requests', 'source_id')) {
                try {
                    $table->dropIndex('banking_payment_requests_source_type_source_id_index');
                } catch (\Exception) {
                    // Index may already be absent
                }
                $table->dropColumn('source_id');
            }

            // Polymorphic morph — points to PurchaseFund (or any future source model)
            if (! Schema::hasColumn('banking_payment_requests', 'sourceable_type')) {
                $table->nullableMorphs('sourceable'); // adds sourceable_type + sourceable_id + index
            }

            // Sub-category (e.g. employee-advance / supplier-advance)
            if (! Schema::hasColumn('banking_payment_requests', 'transaction_category_id')) {
                $table->foreignId('transaction_category_id')
                    ->nullable()
                    ->after('source_type')
                    ->constrained('transaction_categories')
                    ->nullOnDelete();
            }

            // Populated when Banking marks the request completed (creates the ledger entry)
            if (! Schema::hasColumn('banking_payment_requests', 'transaction_id')) {
                $table->unsignedBigInteger('transaction_id')->nullable()->after('transaction_category_id');
                $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('banking_payment_requests', 'transaction_id')) {
                $table->dropForeign(['transaction_id']);
                $table->dropColumn('transaction_id');
            }
            if (Schema::hasColumn('banking_payment_requests', 'transaction_category_id')) {
                $table->dropForeign(['transaction_category_id']);
                $table->dropColumn('transaction_category_id');
            }
            if (Schema::hasColumn('banking_payment_requests', 'sourceable_type')) {
                $table->dropMorphs('sourceable');
            }
            if (! Schema::hasColumn('banking_payment_requests', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                $table->index(['source_type', 'source_id']);
            }
        });
    }
};
