<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_funds', function (Blueprint $table): void {
            // Workflow status: pending (waiting banking approval) | completed | rejected
            if (! Schema::hasColumn('purchase_funds', 'status')) {
                $table->string('status', 20)->default('pending')->after('release_date');
            }

            // Sub-category mirrors BankingPaymentRequest.transaction_category_id
            if (! Schema::hasColumn('purchase_funds', 'transaction_category_id')) {
                $table->foreignId('transaction_category_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('transaction_categories')
                    ->nullOnDelete();
            }

            // Source bank account (used by completeRelease to derive Account.id for the ledger)
            if (! Schema::hasColumn('purchase_funds', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')->nullable()->after('transaction_category_id');
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            }

            // Payment method chosen at request time (cash / bank / cheque / mobile_banking)
            if (! Schema::hasColumn('purchase_funds', 'method')) {
                $table->string('method', 30)->nullable()->after('bank_account_id');
            }
        });

        // Existing records that already have a ledger entry are complete
        DB::statement("UPDATE purchase_funds SET status = 'completed' WHERE transaction_id IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('purchase_funds', function (Blueprint $table): void {
            foreach (['method', 'bank_account_id', 'transaction_category_id', 'status'] as $col) {
                if (Schema::hasColumn('purchase_funds', $col)) {
                    if ($col === 'bank_account_id') {
                        $table->dropForeign(['bank_account_id']);
                    }
                    if ($col === 'transaction_category_id') {
                        $table->dropForeign(['transaction_category_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
