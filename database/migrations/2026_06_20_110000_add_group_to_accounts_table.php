<?php

use App\Enums\Accounts\AccountGroupType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the top-level accounting `group` classification to chart-of-accounts entries
 * (Asset / Liability / Equity / Income / Expense). Nullable for now so existing
 * accounts are not forced to a value before they are classified.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('accounts', 'group')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table): void {
            $table->enum('group', AccountGroupType::values())
                ->nullable()
                ->after('type')
                ->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('accounts', 'group')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn('group');
        });
    }
};
