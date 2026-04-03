<?php

use App\Enums\Inventory\StockConsumptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_consumptions', function (Blueprint $table): void {
            $table->id();
            $table->string('consumption_no', 100)->unique();
            $table->date('consumption_date');
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default(StockConsumptionStatus::DRAFT->value);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['consumption_date', 'status']);
            $table->index(['store_id', 'project_id']);
        });

        Schema::create('stock_consumption_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_consumption_id')->constrained('stock_consumptions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 14, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_consumption_items');
        Schema::dropIfExists('stock_consumptions');
    }
};
