<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_entry_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique()->index();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('category_key', 50)->index();
            $table->string('icon', 500)->nullable();
            $table->enum('workflow', ['banking_request', 'direct_ledger', 'posting_engine'])->default('banking_request')->index();
            $table->string('transaction_type', 50)->nullable();
            $table->string('accounting_event_key', 100)->nullable();
            $table->string('debit_feature_type', 50)->nullable();
            $table->string('debit_account_group', 50)->nullable();
            $table->string('debit_account_type', 50)->nullable();
            $table->string('credit_feature_type', 50)->nullable();
            $table->string('credit_account_group', 50)->nullable();
            $table->string('credit_account_type', 50)->nullable();
            $table->boolean('is_locked')->default(false)->index();
            $table->string('form_component', 255)->nullable();
            $table->string('permission', 100)->default('accounts.entry.create');
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_entry_types');
    }
};
