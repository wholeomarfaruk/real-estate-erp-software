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
        Schema::create('account_entry_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique()->index();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('icon', 500)->nullable();
            $table->string('color', 50)->nullable();
            $table->boolean('is_locked')->default(false)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_entry_categories');
    }
};
