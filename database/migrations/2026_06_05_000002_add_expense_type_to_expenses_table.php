<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreignId('expense_type_id')
                ->nullable()
                ->after('expense_account_id')
                ->constrained('expense_types')
                ->nullOnDelete();

            $table->index('expense_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_type_id');
        });
    }
};
