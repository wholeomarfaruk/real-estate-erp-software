<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_stations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->nullable()->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index('name');
            $table->index('status');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->foreignId('work_station_id')
                ->nullable()
                ->after('designation_id')
                ->constrained('work_stations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropForeignIdFor(\App\Models\WorkStation::class);
            $table->dropColumn('work_station_id');
        });

        Schema::dropIfExists('work_stations');
    }
};
