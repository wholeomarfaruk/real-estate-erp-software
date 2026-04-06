<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('property_floor_id')->nullable()->constrained('property_floors')->nullOnDelete();
            $table->string('unit_number')->nullable();
            $table->string('unit_name')->nullable();
            $table->string('unit_type')->nullable();
            $table->string('purpose')->nullable();
            $table->decimal('size_sqft', 10, 2)->nullable();
            $table->decimal('sell_price', 14, 2)->nullable();
            $table->decimal('rent_amount', 14, 2)->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->integer('balcony')->nullable();
            $table->string('facing')->nullable();
            $table->string('availability_status')->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_units');
    }
};
