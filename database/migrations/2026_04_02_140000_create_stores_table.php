<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->unique();
            $table->string('type')->default('office');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true); // Active by default
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');

            $table->index('type');
            $table->index('status');
            $table->index(['type', 'status']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
