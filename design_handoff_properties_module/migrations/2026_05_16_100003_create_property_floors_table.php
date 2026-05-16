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

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code', 10);                // G, 1, 2, 3, T
            $table->string('label');                   // Ground, Floor 1, Terrace
            $table->unsignedInteger('sort_order')->default(0);   // for drag-reorder

            $table->decimal('floor_area', 12, 2)->nullable();    // sft
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique(['property_id', 'code']);
            $table->index(['property_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_floors');
    }
};
