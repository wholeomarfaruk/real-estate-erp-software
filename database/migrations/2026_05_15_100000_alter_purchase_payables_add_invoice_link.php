<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_payables', function (Blueprint $table): void {
            // MySQL requires dropping the FK before dropping the unique index it depends on.
            $table->dropForeign(['purchase_order_id']);
            $table->dropUnique('purchase_payables_purchase_order_id_unique');

            // Re-add FK with a plain (non-unique) index so multiple payables per PO are allowed.
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->restrictOnDelete();

            // Link each payable to the specific invoice that created it.
            $table->foreignId('purchase_invoice_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('purchase_invoices')
                ->nullOnDelete();

            // Optional notes for the accounts team.
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payables', function (Blueprint $table): void {
            $table->dropForeign(['purchase_invoice_id']);
            $table->dropColumn(['purchase_invoice_id', 'notes']);

            // Restore original unique FK.
            $table->dropForeign(['purchase_order_id']);
            $table->dropIndex('purchase_payables_purchase_order_id_index');
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->restrictOnDelete();
            $table->unique('purchase_order_id');
        });
    }
};
