<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_reference_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('reference_key');
            $table->timestamps();

            $table->unique(['account_id', 'reference_key']);
            $table->index('reference_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_reference_links');
    }
};
