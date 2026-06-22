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
        Schema::create('feature_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key', 50);
            $table->foreignId('parent_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('child_account_id')->constrained('accounts')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['feature_key', 'parent_account_id', 'child_account_id'], 'feature_account_mappings_unique');
            $table->index(['feature_key', 'is_enabled'], 'feature_account_mappings_feature_enabled_idx');
            $table->index('parent_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_account_mappings');
    }
};
