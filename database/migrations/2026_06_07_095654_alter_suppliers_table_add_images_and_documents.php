<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Add image fields (as unsigned big integers for attachment IDs)
            if (!Schema::hasColumn('suppliers', 'image_id')) {
                $table->unsignedBigInteger('image_id')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('suppliers', 'cover_image_id')) {
                $table->unsignedBigInteger('cover_image_id')->nullable()->after('image_id');
            }

            // Add documents JSON field
            if (!Schema::hasColumn('suppliers', 'documents')) {
                $table->json('documents')->nullable()->after('cover_image_id');
            }

            // Remove unnecessary financial fields
            $dropColumns = [];

            foreach (['opening_balance', 'opening_balance_type', 'payment_terms_days', 'credit_limit', 'company_name'] as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Drop new columns
            $dropColumns = [];

            foreach (['image_id', 'cover_image_id', 'documents'] as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }

            // Restore old columns
            $table->string('company_name')->nullable()->after('name');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->string('opening_balance_type', 20)->default('payable');
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->decimal('credit_limit', 14, 2)->default(0);
        });
    }
};
