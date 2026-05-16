<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_sales', function (Blueprint $table) {
            // Direct property reference (unit already carries property_id, but explicit is useful for queries)
            $table->unsignedBigInteger('property_id')->nullable()->after('sale_number');

            // Package / predefined pricing plan
            $table->unsignedBigInteger('package_id')->nullable()->after('property_id');

            // Down payment
            $table->decimal('down_payment_amount', 15, 2)->default(0)->after('net_amount');

            // Installment plan
            $table->unsignedSmallInteger('installment_month_no')->nullable()->after('payment_terms');
            $table->decimal('installment_amount', 15, 2)->default(0)->after('installment_month_no');
            $table->enum('installment_status', ['active', 'deactive', 'complete'])->nullable()->after('installment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('property_sales', function (Blueprint $table) {
            $table->dropColumn([
                'property_id',
                'package_id',
                'down_payment_amount',
                'installment_month_no',
                'installment_amount',
                'installment_status',
            ]);
        });
    }
};
