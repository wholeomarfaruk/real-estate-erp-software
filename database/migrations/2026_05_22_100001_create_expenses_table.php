<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('expense_no', 30)->unique()->nullable();
            $table->string('title', 200);
            $table->date('date');
            $table->decimal('amount', 15, 3);
            $table->string('status', 20)->default('draft');

            // DR side: ledger account (type=LEDGER)
            $table->foreignId('expense_account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            // CR side: resolved at completion from BankAccount.account_id
            $table->unsignedBigInteger('payment_account_id')->nullable();
            $table->foreign('payment_account_id')
                ->references('id')->on('accounts')
                ->nullOnDelete();

            // Physical bank to pay from (drives BankingPaymentRequest)
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->foreign('bank_account_id')
                ->references('id')->on('bank_accounts')
                ->nullOnDelete();

            $table->foreignId('transaction_category_id')
                ->nullable()
                ->constrained('transaction_categories')
                ->nullOnDelete();

            // Set to TXN-EXPENSE.id on banking completion
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->foreign('transaction_id')
                ->references('id')->on('transactions')
                ->nullOnDelete();

            // Optional reference linkage
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index(['status', 'date']);
            $table->index('expense_account_id');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
