<?php

use App\Enums\Inventory\PurchaseMode;
use App\Enums\Inventory\PurchaseOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('po_no', 100)->unique();
            $table->date('order_date');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->string('purchase_mode', 20)->default(PurchaseMode::CASH->value);
            $table->decimal('fund_request_amount', 14, 2)->default(0);
            $table->decimal('approved_amount', 14, 2)->default(0);
            $table->decimal('actual_purchase_amount', 14, 2)->default(0);
            $table->decimal('returned_amount', 14, 2)->default(0);
            $table->decimal('due_amount', 14, 2)->default(0);
            $table->string('status', 30)->default(PurchaseOrderStatus::DRAFT->value);
            $table->foreignId('engineer_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('engineer_approved_at')->nullable();
            $table->foreignId('chairman_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('chairman_approved_at')->nullable();
            $table->foreignId('accounts_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accounts_approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['order_date', 'status']);
            $table->index(['store_id', 'status']);

            $table->index(['purchase_mode', 'status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->string('unit', 255)->nullable();
            $table->decimal('estimated_unit_price', 12, 2)->default(0);
            $table->decimal('estimated_total_price', 14, 2)->default(0);
            $table->decimal('approved_quantity', 10, 3)->nullable();
            $table->decimal('approved_unit_price', 12, 2)->nullable();
            $table->decimal('approved_total_price', 14, 2)->nullable();

            $table->text('remarks')->nullable();
            $table->index(['supplier_id']);
            $table->timestamps();

            $table->index(['product_id']);
        });

        Schema::create('purchase_order_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('approval_stage', 30);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 20);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_id', 'approval_stage']);
        });

        Schema::create('purchase_funds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('release_type', 20);
            $table->decimal('amount', 14, 2);
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('release_date');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_id', 'release_date']);
        });

        Schema::create('purchase_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->unique()->constrained('purchase_orders')->cascadeOnDelete();
            $table->decimal('total_fund_released', 14, 2)->default(0);
            $table->decimal('actual_purchase_amount', 14, 2)->default(0);
            $table->decimal('returned_cash_amount', 14, 2)->default(0);
            $table->decimal('due_amount', 14, 2)->default(0);
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_settlements');
        Schema::dropIfExists('purchase_funds');
        Schema::dropIfExists('purchase_order_approvals');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
