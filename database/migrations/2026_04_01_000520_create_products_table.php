<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->text('description')->nullable();
            $table->string('unit')->nullable();
            $table->unsignedBigInteger('image_id')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('product_categories')->cascadeOnDelete();
            $table->foreign('brand_id')->references('id')->on('product_brands')->nullOnDelete();
            $table->foreign('image_id')->references('id')->on('files')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
