<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add double-entry fields to banking_payment_requests.
     * These store the debit/credit account details and amounts before transaction creation.
     * When payment is completed (released → completed), the actual Transaction is created.
     */
    public function up(): void
    {
        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            // Debit side (account and amount)
            if (! Schema::hasColumn('banking_payment_requests', 'debit_account_id')) {
                $table->unsignedBigInteger('debit_account_id')->nullable()
                    ->after('account_id')
                    ->comment('Debit account for double-entry');
                $table->foreign('debit_account_id')
                    ->references('id')->on('accounts')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('banking_payment_requests', 'debit_amount')) {
                $table->decimal('debit_amount', 15, 3)->nullable()
                    ->after('debit_account_id')
                    ->comment('Debit amount');
            }

            // Credit side (account and amount)
            if (! Schema::hasColumn('banking_payment_requests', 'credit_account_id')) {
                $table->unsignedBigInteger('credit_account_id')->nullable()
                    ->after('debit_amount')
                    ->comment('Credit account for double-entry');
                $table->foreign('credit_account_id')
                    ->references('id')->on('accounts')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('banking_payment_requests', 'credit_amount')) {
                $table->decimal('credit_amount', 15, 3)->nullable()
                    ->after('credit_account_id')
                    ->comment('Credit amount');
            }

            // Transaction reference fields
            if (! Schema::hasColumn('banking_payment_requests', 'reference_no')) {
                $table->string('reference_no', 50)->nullable()
                    ->after('credit_amount')
                    ->comment('Reference number (PO, Invoice, etc)');
            }

            if (! Schema::hasColumn('banking_payment_requests', 'name')) {
                $table->string('name', 100)->nullable()
                    ->after('reference_no')
                    ->comment('Payee/payer name');
            }

            if (! Schema::hasColumn('banking_payment_requests', 'phone')) {
                $table->string('phone', 20)->nullable()
                    ->after('name')
                    ->comment('Payee/payer phone');
            }

            if (! Schema::hasColumn('banking_payment_requests', 'method')) {
                $table->string('method', 20)->nullable()
                    ->after('phone')
                    ->comment('Payment method (bank, cash, mfs, check)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            $columns = [
                'debit_account_id', 'debit_amount',
                'credit_account_id', 'credit_amount',
                'reference_no', 'name', 'phone', 'method'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('banking_payment_requests', $column)) {
                    if (str_contains($column, '_account_id')) {
                        $table->dropForeign(["$column"]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
