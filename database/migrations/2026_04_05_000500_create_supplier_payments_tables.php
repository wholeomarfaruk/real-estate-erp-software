<?php

use App\Enums\Supplier\SupplierPaymentMethod;
use App\Enums\Supplier\SupplierPaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('payment_no', 100)->unique();
            $table->date('payment_date');
            $table->string('payment_method', 30)->default(SupplierPaymentMethod::CASH->value);
            $table->string('account_name')->nullable();
            $table->string('account_reference')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('transaction_no')->nullable();
            $table->string('cheque_no')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('allocated_amount', 18, 2)->default(0);
            $table->decimal('unallocated_amount', 18, 2)->default(0);
            $table->string('status', 30)->default(SupplierPaymentStatus::DRAFT->value);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index(['payment_date', 'status']);
            $table->index(['payment_method', 'status']);
        });

        Schema::create('supplier_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_payment_id')->constrained('supplier_payments')->cascadeOnDelete();
            $table->foreignId('supplier_bill_id')->constrained('supplier_bills')->restrictOnDelete();
            $table->decimal('allocated_amount', 18, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_bill_id'], 'spa_bill_idx');
            $table->index(['supplier_payment_id', 'supplier_bill_id'], 'spa_payment_bill_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_allocations');
        Schema::dropIfExists('supplier_payments');
    }
};
