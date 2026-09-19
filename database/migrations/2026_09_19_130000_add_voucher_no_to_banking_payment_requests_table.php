<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('banking_payment_requests', 'voucher_no')) {
                $table->string('voucher_no')->nullable()->after('reference_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('banking_payment_requests', 'voucher_no')) {
                $table->dropColumn('voucher_no');
            }
        });
    }
};
