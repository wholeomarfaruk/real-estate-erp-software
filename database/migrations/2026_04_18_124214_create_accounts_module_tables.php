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
            $table->string('sub_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['name']);
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->timestamp('datetime');
            $table->string('type', 50)->default(TransactionType::JOURNAL->value);
            $table->string('main_category')->nullable();
            $table->string('sub_category')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('debit', 15, 3)->default(0);
            $table->decimal('credit', 15, 3)->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name')->nullable();
            $table->string('method')->default(EntryMethod::CASH->value);
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->index(['datetime', 'type']);
            $table->index(['reference_type', 'reference_id']);

            //foreign
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });

      
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('accounts');
    }
};
