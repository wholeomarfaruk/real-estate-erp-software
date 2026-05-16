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
            $table->unsignedBigInteger('property_id')->constrained()->cascadeOnDelete();
            $table->string('sale_type')->nullable(); // sale, rent from PropertySaleType enum
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
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'cancelled'])->default('pending');
            $table->enum('status', ['active', 'completed', 'cancelled', 'on_hold'])->default('active');
            $table->string('sales_representative')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->decimal('down_payment_amount', 15, 2)->default(0);

            //SCHEDULE
            $table->boolean('is_scheduled')->default(false);
            $table->unsignedSmallInteger('schedule_count')->nullable(); // for auto generation of payment schedule like "Monthly Rent - Month 1", "Security Deposit", "Rent - Renewal Year 1", etc.
            $table->decimal('schedule_amount', 15, 2)->default(0);
            $table->string('schedule_name'); // for auto generation of payment schedule text like "Monthly Rent - Month 1", "Security Deposit", "Rent - Renewal Year 1", etc.
            $table->string('schedule_type')->nullable(); // payment_schedule_type daily, weekly, monthly
            $table->string('schedule_day')->nullable(); // payment_schedule_day 1-28
            $table->date('schedule_start_date')->nullable(); // payment_schedule_start_date
            $table->enum('schedule_status', ['active', 'deactive', 'complete'])->nullable();

            // RENT
            $table->date('rent_start_date')->nullable();
            $table->date('rent_end_date')->nullable();
            $table->decimal('security_deposit_amount', 15, 2)->default(0);
            $table->boolean('is_renewal')->default(false);
            $table->date('renewal_date')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_sales');
    }
};
