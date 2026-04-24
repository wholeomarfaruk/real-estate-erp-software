<?php

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\CollectionType;
use App\Enums\Accounts\EntryMethod;
use App\Enums\Accounts\PurchasePayableStatus;
use App\Enums\Accounts\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('code', 50)->nullable()->unique();
            $table->string('name', 150);
            $table->string('type', 30)->default(AccountType::ASSET->value);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['name']);
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('type', 50)->default(TransactionType::JOURNAL->value);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['date', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('transaction_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('debit', 15, 3)->default(0);
            $table->decimal('credit', 15, 3)->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['account_id']);
            $table->index(['transaction_id', 'account_id']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->string('payment_no', 50)->nullable()->unique();
            $table->date('date');
            $table->string('method', 30)->default(EntryMethod::CASH->value);
            $table->foreignId('payment_account_id')->constrained('accounts')->restrictOnDelete();
            // Required for clean double-entry: debit purpose account against credit cash/bank account.
            $table->foreignId('purpose_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('payee_name', 150)->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['date', 'method']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['payment_account_id']);
            $table->index(['purpose_account_id']);
        });

        Schema::create('collections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->string('collection_no', 50)->nullable()->unique();
            $table->date('date');
            $table->string('method', 30)->default(EntryMethod::CASH->value);
            $table->foreignId('collection_account_id')->constrained('accounts')->restrictOnDelete();
            // Required for clean double-entry: credit target account against debit cash/bank collection account.
            $table->foreignId('target_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('payer_name', 150)->nullable();
            $table->string('collection_type', 50)->default(CollectionType::OTHER->value);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['date', 'method']);
            $table->index(['collection_type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['collection_account_id']);
            $table->index(['target_account_id']);
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->string('expense_no', 50)->nullable()->unique();
            $table->date('date');
            $table->string('title', 150);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('payment_account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['expense_account_id']);
            $table->index(['payment_account_id']);
        });

        Schema::create('purchase_payables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->decimal('payable_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('due_amount', 14, 2);
            $table->string('status', 30)->default(PurchasePayableStatus::UNPAID->value);
            $table->timestamps();

            $table->unique(['purchase_order_id']);
            $table->index(['supplier_id', 'status']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payables');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('transaction_lines');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('accounts');
    }
};
