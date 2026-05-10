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
        Schema::create('stock_request_purchase_order_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_request_id')->constrained('stock_requests')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('linked_quantity', 10, 3);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['stock_request_id', 'purchase_order_id', 'product_id'], 'sr_po_product_unique');
            $table->index(['stock_request_id', 'product_id'], 'sr_po_product_idx');
            $table->index(['purchase_order_id', 'product_id'], 'po_sr_product_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_request_purchase_order_links');
    }
};
