<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            if (!Schema::hasColumn('banking_payment_requests', 'external_data')) {
                $table->json('external_data')->nullable()->after('notes');
            }
        });

        Schema::table('transactions', function (Blueprint $table): void {
            if (!Schema::hasColumn('transactions', 'external_data')) {
                $table->json('external_data')->nullable()->after('attachments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('banking_payment_requests', 'external_data')) {
                $table->dropColumn('external_data');
            }
        });

        Schema::table('transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('transactions', 'external_data')) {
                $table->dropColumn('external_data');
            }
        });
    }
};
