<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Legacy columns (quantity, rate, type) predate the BOQ columns
        // (estimated_qty, estimated_rate, cost_type). Give them defaults so
        // new BOQ-style rows can be inserted without supplying legacy values.
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->default(0)->nullable()->change();
            $table->decimal('rate', 15, 2)->default(0)->nullable()->change();
            if (Schema::hasColumn('estimate_items', 'type')) {
                $table->string('type')->nullable()->change();
            }
            if (Schema::hasColumn('estimate_items', 'name')) {
                $table->string('name')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // No safe rollback for default changes; leave as-is.
    }
};
