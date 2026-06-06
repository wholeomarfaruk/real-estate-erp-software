<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---------- Expense categories (labour / other) ----------
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // Mason Labour, Transport, Security...
            $table->string('type');                  // labour | other
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ---------- Project expenses ----------
        Schema::create('project_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_no')->unique();                 // EXP-2026-0001
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->nullable();           // cash, bank_transfer, cheque
            $table->unsignedBigInteger('account_id')->nullable();   // accounts.id (ledger)
            $table->unsignedBigInteger('vendor_id')->nullable();    // vendors.id — who received payment
            $table->string('invoice_no')->nullable();               // bill / voucher reference
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->json('attachments')->nullable();                // [media_file_ids]
            $table->string('status')->default('draft');             // draft | approved
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['expense_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_expenses');
        Schema::dropIfExists('expense_categories');
    }
};
