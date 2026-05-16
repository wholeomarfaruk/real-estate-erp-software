<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            // Traceability back to which stock receive item and PO item this line came from
            $table->foreignId('stock_receive_item_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('purchase_order_item_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal('qty', 18, 3);
            $table->decimal('unit_price', 18, 3);
            $table->decimal('discount_amount', 18, 3)->default(0);
            $table->decimal('total_amount', 18, 3);

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('product_id');
            $table->index('purchase_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
