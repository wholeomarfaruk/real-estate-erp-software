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

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('property_floor_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code', 30);                                  // A-101, S-G01, P-G01
            $table->enum('type', ['flat', 'shop', 'parking']);
            $table->enum('status', ['available', 'booked', 'sold', 'rented'])
                  ->default('available');

            $table->decimal('area', 12, 2)->nullable();                  // sft
            $table->decimal('price', 18, 3)->default(0);
            $table->decimal('service_charge', 18, 3)->default(0);        // monthly
            $table->decimal('rent_amount', 18, 3)->default(0);           // for rented units

            $table->string('facing')->nullable();                        // North, South, East, West
            $table->text('notes')->nullable();

            $table->unsignedInteger('sort_order')->default(0);           // drag order within floor

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['property_id', 'code']);
            $table->index('status');
            $table->index('type');
            $table->index(['property_floor_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_units');
    }
};
