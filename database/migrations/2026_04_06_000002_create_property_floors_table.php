<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            
            //new added
            $table->string('code', 10)->nullable();
            $table->string('label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('floor_area', 12, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'sort_order', 'code']);
            
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_floors');
    }
};
