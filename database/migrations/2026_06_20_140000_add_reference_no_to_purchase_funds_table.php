<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voucher / reference number captured when a purchase-order fund advance is
 * requested. It survives to completion so the posted advance transaction's
 * `reference_no` can carry it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('purchase_funds', 'reference_no')) {
            return;
        }

        Schema::table('purchase_funds', function (Blueprint $table): void {
            $table->string('reference_no', 100)->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('purchase_funds', 'reference_no')) {
            return;
        }

        Schema::table('purchase_funds', function (Blueprint $table): void {
            $table->dropColumn('reference_no');
        });
    }
};
