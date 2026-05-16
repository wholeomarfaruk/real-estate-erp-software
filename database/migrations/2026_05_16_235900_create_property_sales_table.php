<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number', 20)->unique();        // SALE-0000001
            $table->unsignedBigInteger('property_unit_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('sale_date')->nullable();
            $table->date('contract_date')->nullable();
            $table->decimal('sale_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);   // sale - discount + tax
            $table->unsignedSmallInteger('payment_terms')->nullable();   // days
            $table->enum('payment_status', ['pending','partial','paid','cancelled'])->default('pending');
            $table->enum('status', ['active','completed','cancelled','on_hold'])->default('active');
            $table->string('sales_representative')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_sales');
    }
};
