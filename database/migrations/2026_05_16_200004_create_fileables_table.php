<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fileables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->morphs('fileable');   // fileable_id + fileable_type + index
            $table->string('category', 40)->default('other'); // facade|lobby|floor_plan|interior|document|other
            $table->string('caption')->nullable();
            $table->boolean('is_cover')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['fileable_type', 'fileable_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fileables');
    }
};
