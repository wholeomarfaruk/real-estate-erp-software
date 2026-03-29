<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_estimate_id')->constrained('project_estimates')->onDelete('cascade');
            $table->enum('type', ['materials', 'labor', 'equipment', 'transport', 'subcontract', 'overhead']);
            $table->string('name');
            $table->decimal('quantity', 10, 2);
            $table->string('unit');
            $table->decimal('rate', 12, 2);
            $table->decimal('total_cost', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_items');
    }
};
