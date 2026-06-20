<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_sale_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_sale_id');
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('property_unit_id');

            // Per-unit pricing — invoice summary is the sum of these rows.
            $table->decimal('sale_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);   // sale - discount + tax
            $table->decimal('service_charge', 15, 2)->default(0);
            $table->decimal('utility_charge', 15, 2)->default(0);
            $table->decimal('down_payment_percentage', 5, 2)->nullable(); // suggested only

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('property_sale_id')->references('id')->on('property_sales')->cascadeOnDelete();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->foreign('property_unit_id')->references('id')->on('property_units');
            $table->index(['property_sale_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_sale_units');
    }
};
