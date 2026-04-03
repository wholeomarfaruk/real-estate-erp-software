<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
          $table->string('name');
    $table->string('contact_person')->nullable();
    $table->string('phone', 50)->nullable();
    $table->string('secondary_phone', 50)->nullable();
    $table->string('email')->nullable();
    $table->string('address')->nullable();
    $table->boolean('status')->default(true);
    $table->timestamps();

    $table->index(['name', 'status']);
    $table->index(['phone', 'secondary_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
