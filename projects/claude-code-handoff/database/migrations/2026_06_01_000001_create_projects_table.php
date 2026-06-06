<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('image_id')->nullable();         // media_files.id (cover)
            $table->json('project_types')->nullable();                  // ["residential","commercial",...]
            $table->foreignId('site_engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('chief_engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('location')->nullable();
            $table->decimal('land_area', 12, 2)->nullable();
            $table->decimal('building_area', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('handover_date')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->string('status')->default('upcoming');              // upcoming, running, on_hold, completed, cancelled
            $table->unsignedTinyInteger('progress_pct')->default(0);    // construction progress 0-100
            $table->text('description')->nullable();
            $table->json('documents')->nullable();                      // [media_file_ids]
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
