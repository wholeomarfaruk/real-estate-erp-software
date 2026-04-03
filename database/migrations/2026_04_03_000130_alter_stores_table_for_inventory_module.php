<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            if (! Schema::hasColumn('stores', 'manager_user_id')) {
                $table->foreignId('manager_user_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::create('store_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['store_id', 'user_id']);
            $table->index(['user_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_user');

        Schema::table('stores', function (Blueprint $table): void {
            if (Schema::hasColumn('stores', 'manager_user_id')) {
                $table->dropConstrainedForeignId('manager_user_id');
            }
        });
    }
};
