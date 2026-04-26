<?php

use App\Enums\Inventory\StockReceiveStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receives', function (Blueprint $table): void {
            $table->id();
            $table->string('receive_no', 100)->unique();
            $table->string('store_receiver_no')->nullable()->unique();
            $table->date('receive_date');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_voucher')->nullable();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->text('remarks')->nullable();
            $table->json('images')->nullable();
            $table->string('status', 20)->default(StockReceiveStatus::DRAFT->value);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['receive_date', 'status']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('stock_receive_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_receive_id')->constrained('stock_receives')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 14, 2);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receive_items');
        Schema::dropIfExists('stock_receives');
    }
};
