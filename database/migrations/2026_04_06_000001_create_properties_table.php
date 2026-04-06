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
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('property_type')->nullable();
            $table->string('purpose')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->integer('total_floors')->nullable();
            $table->string('status')->default('active');
            $table->string('image')->nullable();
            $table->json('documents')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
