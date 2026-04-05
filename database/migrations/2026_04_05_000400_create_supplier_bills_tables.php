<?php

use App\Enums\Supplier\SupplierBillReferenceType;
use App\Enums\Supplier\SupplierBillStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_bills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('bill_no', 100)->unique();
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->string('reference_type', 50)->nullable()->default(SupplierBillReferenceType::MANUAL->value);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('stock_receive_id')->nullable()->constrained('stock_receives')->nullOnDelete();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('other_charge', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('due_amount', 18, 2)->default(0);
            $table->string('status', 30)->default(SupplierBillStatus::DRAFT->value);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index(['bill_date', 'status']);
            $table->index(['due_date', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('supplier_bill_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_bill_id')->constrained('supplier_bills')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('qty', 18, 3)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->decimal('rate', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['supplier_bill_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_bill_items');
        Schema::dropIfExists('supplier_bills');
    }
};
