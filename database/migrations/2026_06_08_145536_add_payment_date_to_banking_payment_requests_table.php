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
        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('banking_payment_requests', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('banking_payment_requests', 'payment_date')) {
                $table->dropColumn('payment_date');
            }
        });
    }
};
