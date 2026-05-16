<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                  // P-101
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('type')->nullable();                // Residential, Commercial, Mixed
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->decimal('total_area', 12, 2)->nullable();  // sft
            $table->decimal('land_size', 10, 2)->nullable();   // katha / decimal

            $table->foreignId('engineer_id')
                ->nullable()
                ->constrained('engineers')
                ->nullOnDelete();

            $table->date('registered_at')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
