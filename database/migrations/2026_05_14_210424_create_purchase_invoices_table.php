<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relations
            |--------------------------------------------------------------------------
            */

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('purchase_order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('stock_receive_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Invoice Information
            |--------------------------------------------------------------------------
            */

            $table->string('invoice_no')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('supplier_invoice_no')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Amounts
            |--------------------------------------------------------------------------
            */

            $table->decimal('subtotal', 18, 3)->default(0);
            $table->decimal('discount_amount', 18, 3)->default(0);
            $table->decimal('shipping_amount', 18, 3)->default(0);
            $table->decimal('total_amount', 18, 3)->default(0);
            $table->decimal('paid_amount', 18, 3)->default(0);
            $table->decimal('due_amount', 18, 3)->default(0);

            // Tracks advance cash used (from PurchaseFunds) applied against this invoice
            $table->decimal('advance_adjusted_amount', 18, 3)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            | draft → pending_approval → confirmed → partially_paid → paid → cancelled
            */

            $table->string('status')->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Accounting
            |--------------------------------------------------------------------------
            | inventory_account_id  : DR (Inventory/Asset) on confirmation
            | accounts_payable_account_id : CR (Accounts Payable) on confirmation
            | transaction_id        : Journal entry created on confirmation
            | purchase_payable_id   : Payable record created on confirmation
            */

            $table->foreignId('inventory_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();


            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Attachments / Notes
            |--------------------------------------------------------------------------
            */

            $table->json('attachments')->nullable();
            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Workflow Audit
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('invoice_date');
            $table->index('status');
            $table->index('supplier_id');
            $table->index(['purchase_order_id', 'status']);
            $table->index(['stock_receive_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
