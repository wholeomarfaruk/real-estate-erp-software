<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds fund-release classification columns to the payments table so
 * fund-release payments can be distinguished from regular supplier payments:
 *
 *   payment_type : fund_release | null (null = regular payment)
 *   release_type : employee_advance | supplier_advance | null
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type', 30)->nullable()->after('method');
            }

            if (! Schema::hasColumn('payments', 'release_type')) {
                $table->string('release_type', 30)->nullable()->after('payment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $drop = array_filter(
                ['payment_type', 'release_type'],
                fn ($c) => Schema::hasColumn('payments', $c)
            );

            if ($drop) {
                $table->dropColumn(array_values($drop));
            }
        });
    }
};
