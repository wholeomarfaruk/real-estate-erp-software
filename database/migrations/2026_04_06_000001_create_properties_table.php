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
            $table->text('address')->nullable();
            $table->string('status')->default('active');
            $table->string('image')->nullable();
            $table->json('documents')->nullable();
        
            //new added
                        // New columns — existing project_id / property_type / etc. stay intact
            $table->json('type')->nullable();
            $table->decimal('total_area', 12, 2)->nullable();
            $table->decimal('land_size', 10, 2)->nullable();
            $table->unsignedBigInteger('engineer_id')->nullable();
            $table->date('registered_at')->nullable();
            $table->text('remarks')->nullable();
            $table->softDeletes();



           $table->timestamps();

           //foreign key constraint for engineer_id referencing employees table
            $table->foreign('engineer_id')->references('id')->on('employees')->nullOnDelete();
           $table->index('type');
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
