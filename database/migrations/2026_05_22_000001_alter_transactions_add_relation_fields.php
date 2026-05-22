<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('transactions', 'related_transaction_id')) {
                $table->unsignedBigInteger('related_transaction_id')->nullable()->after('adjusted_transaction_id');
                $table->foreign('related_transaction_id')
                    ->references('id')
                    ->on('transactions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('transactions', 'relation_type')) {
                $table->string('relation_type', 30)->nullable()->after('related_transaction_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('transactions', 'relation_type')) {
                $table->dropColumn('relation_type');
            }
            if (Schema::hasColumn('transactions', 'related_transaction_id')) {
                $table->dropForeign(['related_transaction_id']);
                $table->dropColumn('related_transaction_id');
            }
        });
    }
};
