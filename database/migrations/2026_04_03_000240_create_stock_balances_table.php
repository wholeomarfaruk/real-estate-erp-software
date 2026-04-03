<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 10, 3)->default(0);
            $table->decimal('avg_unit_price', 12, 2)->default(0);
            $table->decimal('total_value', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'product_id']);
            $table->index(['product_id', 'quantity']);
            $table->index(['store_id', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
