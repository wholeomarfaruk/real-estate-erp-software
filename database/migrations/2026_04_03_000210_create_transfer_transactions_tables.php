<?php

use App\Enums\Inventory\TransferStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('transfer_no', 100)->unique();
            $table->foreignId('sender_store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('receiver_store_id')->constrained('stores')->restrictOnDelete();
            $table->date('transfer_date');
            $table->string('status', 20)->default(TransferStatus::DRAFT->value);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['transfer_date', 'status']);
            $table->index(['sender_store_id', 'receiver_store_id']);
        });

        Schema::create('transfer_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transfer_transaction_id')->constrained('transfer_transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->decimal('received_quantity', 10, 3)->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 14, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamp('checked_by_sender_at')->nullable();
            $table->timestamp('checked_by_receiver_at')->nullable();
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
        Schema::dropIfExists('transfer_transactions');
    }
};
