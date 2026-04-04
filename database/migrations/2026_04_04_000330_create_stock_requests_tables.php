<?php

use App\Enums\Inventory\StockRequestPriority;
use App\Enums\Inventory\StockRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_no', 100)->unique();
            $table->date('request_date');
            $table->foreignId('requester_store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('source_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('fulfilled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fulfilled_at')->nullable();
            $table->string('status', 30)->default(StockRequestStatus::DRAFT->value);
            $table->string('priority', 20)->default(StockRequestPriority::NORMAL->value);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['request_date', 'status'], 'sr_reqdate_status_idx');
            $table->index(['requester_store_id', 'status'], 'sr_reqstore_status_idx');
            $table->index(['source_store_id', 'status'], 'sr_srcstore_status_idx');
            $table->index(['project_id', 'status'], 'sr_project_status_idx');
            $table->index(['priority', 'status'], 'sr_priority_status_idx');
        });

        Schema::create('stock_request_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_request_id')->constrained('stock_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->decimal('approved_quantity', 10, 3)->nullable();
            $table->decimal('fulfilled_quantity', 10, 3)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('product_id', 'sri_product_idx');
        });

        Schema::create('stock_request_transfer_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_request_id')->constrained('stock_requests')->cascadeOnDelete();
            $table->foreignId('transfer_transaction_id')->constrained('transfer_transactions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['stock_request_id', 'transfer_transaction_id'],
                'srtl_req_transfer_unq'
            );

            $table->unique('transfer_transaction_id', 'srtl_transfer_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_request_transfer_links');
        Schema::dropIfExists('stock_request_items');
        Schema::dropIfExists('stock_requests');
    }
};
