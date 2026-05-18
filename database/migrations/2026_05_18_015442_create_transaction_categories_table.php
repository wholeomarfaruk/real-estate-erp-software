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

        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // income or expense
            $table->string('slug')->unique();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false)->comment('if true, Cannot be deleted');

            $table->foreignId('parent_id')->nullable()->constrained('transaction_categories')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_categories');
    }
};
