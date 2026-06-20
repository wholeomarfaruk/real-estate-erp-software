<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Move the fund-release "source account" onto the chart-of-accounts `accounts`
 * table directly (a cash/bank/mfs/wallet money account) instead of going through
 * bank_accounts. The PurchaseFund stores the chosen COA account; the
 * BankingPaymentRequest gains a nullable COA account_id and its bank_account_id
 * becomes nullable so a request can be backed by a COA account alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_funds', function (Blueprint $table): void {
            if (! Schema::hasColumn('purchase_funds', 'payment_account_id')) {
                $table->foreignId('payment_account_id')
                    ->nullable()
                    ->after('bank_account_id')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }
        });

        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('banking_payment_requests', 'account_id')) {
                $table->foreignId('account_id')
                    ->nullable()
                    ->after('bank_account_id')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }
        });

        // Make bank_account_id nullable (a COA-backed request may have no BankAccount).
        if (Schema::hasColumn('banking_payment_requests', 'bank_account_id')) {
            // Drop FK first if present, relax nullability, re-add FK.
            try {
                Schema::table('banking_payment_requests', function (Blueprint $table): void {
                    $table->dropForeign(['bank_account_id']);
                });
            } catch (\Throwable) {
                // no FK to drop
            }

            Schema::table('banking_payment_requests', function (Blueprint $table): void {
                $table->unsignedBigInteger('bank_account_id')->nullable()->change();
                $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('purchase_funds', function (Blueprint $table): void {
            if (Schema::hasColumn('purchase_funds', 'payment_account_id')) {
                $table->dropForeign(['payment_account_id']);
                $table->dropColumn('payment_account_id');
            }
        });

        Schema::table('banking_payment_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('banking_payment_requests', 'account_id')) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            }
        });
    }
};
