<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Seed the three defaults so existing records still resolve
        DB::table('unit_types')->insert([
            ['name' => 'Flat',    'slug' => 'flat',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Shop',    'slug' => 'shop',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Parking', 'slug' => 'parking', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_types');
    }
};
