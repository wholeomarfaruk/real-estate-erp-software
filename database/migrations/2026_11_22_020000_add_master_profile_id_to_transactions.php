<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Party (master profile) on accounting entries — lets every profile show its
 * balance from the ledger. Nullable: many entries (e.g. some expenses) have no party.
 * Run `php artisan profiles:sync-transactions` after migrating to tag existing entries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('master_profile_id')->nullable()->after('reference_id')
                ->constrained('master_profiles')->nullOnDelete();
        });

        Schema::table('banking_payment_requests', function (Blueprint $table) {
            $table->foreignId('master_profile_id')->nullable()->after('sourceable_id')
                ->constrained('master_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('banking_payment_requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('master_profile_id'));
        Schema::table('transactions', fn (Blueprint $table) => $table->dropConstrainedForeignId('master_profile_id'));
    }
};
