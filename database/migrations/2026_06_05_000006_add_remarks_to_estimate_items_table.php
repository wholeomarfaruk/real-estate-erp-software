<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            if (!Schema::hasColumn('estimate_items', 'remarks')) {
                $table->text('remarks')->nullable()->after('is_optional');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
