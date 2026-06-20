<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add `is_locked` to chart-of-accounts entries. Locked accounts (the top-level
 * group parents seeded by ChartOfAccountsSeeder) can never be deleted. Mirrors the
 * existing transaction_categories.is_locked convention.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('accounts', 'is_locked')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table): void {
            $table->boolean('is_locked')
                ->default(false)
                ->after('is_active')
                ->comment('if true, Cannot be deleted');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('accounts', 'is_locked')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn('is_locked');
        });
    }
};
