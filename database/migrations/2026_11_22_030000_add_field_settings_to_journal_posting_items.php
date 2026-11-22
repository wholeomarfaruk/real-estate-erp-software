<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-item entry-form field rules: { field_key: "hidden" | "optional" | "required" }.
 * Missing keys fall back to "optional".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_posting_items', function (Blueprint $table) {
            $table->json('field_settings')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('journal_posting_items', function (Blueprint $table) {
            $table->dropColumn('field_settings');
        });
    }
};
