<?php

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\EntryMethod;
use App\Enums\Accounts\TransactionStatus;
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
            $table->string('sub_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['name']);
        });
          Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // income or expense
            $table->string('type')->nullable();
            $table->string('slug')->unique();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false)->comment('if true, Cannot be deleted');

            $table->foreignId('parent_id')->nullable()->constrained('transaction_categories')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->timestamp('datetime');
            $table->string('type', 50)->default(TransactionType::INCOME->value);
            $table->unsignedBigInteger('transaction_category_id')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('debit', 15, 3)->default(0);
            $table->decimal('credit', 15, 3)->default(0);
            // Adjustments
            $table->timestamp('adjusted_at')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('adjusted_transaction_id')->nullable(); // reference to the adjusted transaction
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('reference_no')->nullable();
            //customer info
            $table->string('name')->nullable();
            $table->string('phone')->nullable();

            $table->string('method')->default(EntryMethod::CASH->value);
            $table->string('status')->default(TransactionStatus::PENDING->value);
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->index(['datetime', 'type']);
            $table->index(['reference_type', 'reference_id']);

            //foreign
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('transaction_category_id')->references('id')->on('transaction_categories')->nullOnDelete();
        });

      
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('transaction_categories');
        Schema::dropIfExists('accounts');
    }
};
