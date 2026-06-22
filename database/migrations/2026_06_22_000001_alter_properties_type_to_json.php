<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Drop the existing index on type column
            $table->dropIndex(['type']);
        });

        // Convert existing string values to JSON arrays before changing column type
        DB::statement("UPDATE properties SET type = JSON_ARRAY(type) WHERE type IS NOT NULL AND type != ''");

        Schema::table('properties', function (Blueprint $table) {
            // Change column type from string to json
            $table->json('type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Revert to string, extracting first element or null
            DB::statement("UPDATE properties SET type = JSON_UNQUOTE(JSON_EXTRACT(type, '$[0]')) WHERE type IS NOT NULL");

            $table->string('type')->nullable()->change();
            $table->index('type');
        });
    }
};
