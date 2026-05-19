<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advance_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advance_transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();
            $table->foreignId('adjust_transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();
            $table->decimal('amount', 15, 3);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('advance_transaction_id');
            $table->index('adjust_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_adjustments');
    }
};
