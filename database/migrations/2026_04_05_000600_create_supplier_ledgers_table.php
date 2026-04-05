<?php

use App\Enums\Supplier\SupplierLedgerTransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_ledgers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('transaction_type', 50)->default(SupplierLedgerTransactionType::OPENING_BALANCE->value);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('status', 50)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'transaction_date', 'id'], 'supplier_ledgers_supplier_date_idx');
            $table->index(['supplier_id', 'transaction_type'], 'supplier_ledgers_supplier_type_idx');
            $table->index(['reference_type', 'reference_id'], 'supplier_ledgers_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ledgers');
    }
};
