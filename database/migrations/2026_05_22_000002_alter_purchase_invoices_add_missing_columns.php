<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('purchase_invoices', 'accounts_payable_account_id')) {
                $table->unsignedBigInteger('accounts_payable_account_id')->nullable()->after('inventory_account_id');
                $table->foreign('accounts_payable_account_id')
                    ->references('id')->on('accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_invoices', 'advance_account_id')) {
                $table->unsignedBigInteger('advance_account_id')->nullable()->after('accounts_payable_account_id');
                $table->foreign('advance_account_id')
                    ->references('id')->on('accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_invoices', 'payment_account_id')) {
                $table->unsignedBigInteger('payment_account_id')->nullable()->after('advance_account_id');
                $table->foreign('payment_account_id')
                    ->references('id')->on('accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_invoices', 'payment_method')) {
                $table->string('payment_method', 30)->nullable()->after('payment_account_id');
            }

            if (! Schema::hasColumn('purchase_invoices', 'purchase_payable_id')) {
                $table->unsignedBigInteger('purchase_payable_id')->nullable()->after('payment_method');
            }

            if (! Schema::hasColumn('purchase_invoices', 'payment_id')) {
                $table->unsignedBigInteger('payment_id')->nullable()->after('purchase_payable_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table): void {
            $fks = ['accounts_payable_account_id', 'advance_account_id', 'payment_account_id'];
            foreach ($fks as $col) {
                if (Schema::hasColumn('purchase_invoices', $col)) {
                    $table->dropForeign([$col]);
                    $table->dropColumn($col);
                }
            }

            foreach (['payment_method', 'purchase_payable_id', 'payment_id'] as $col) {
                if (Schema::hasColumn('purchase_invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
