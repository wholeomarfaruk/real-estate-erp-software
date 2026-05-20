<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banking_payment_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_no', 30)->unique();
            $table->string('source_type', 30); // PaymentRequestSourceType enum value
            $table->unsignedBigInteger('source_id')->nullable(); // polymorphic source reference
            $table->decimal('amount', 15, 3);
            $table->text('description')->nullable();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->string('status', 20)->default('pending'); // pending/approved/released/completed/rejected
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'bank_account_id']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_payment_requests');
    }
};
