<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'chief_engineer_id')) {
                $table->foreignId('chief_engineer_id')->nullable()->constrained('users')->nullOnDelete()->after('code');
            }
            if (!Schema::hasColumn('projects', 'site_engineer_id')) {
                $table->foreignId('site_engineer_id')->nullable()->constrained('users')->nullOnDelete()->after('chief_engineer_id');
            }
            if (!Schema::hasColumn('projects', 'land_area')) {
                $table->decimal('land_area', 12, 2)->nullable()->after('location');
            }
            if (!Schema::hasColumn('projects', 'building_area')) {
                $table->decimal('building_area', 12, 2)->nullable()->after('land_area');
            }
            if (!Schema::hasColumn('projects', 'handover_date')) {
                $table->date('handover_date')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('projects', 'progress_pct')) {
                $table->unsignedTinyInteger('progress_pct')->default(0)->after('status');
            }
            if (!Schema::hasColumn('projects', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('progress_pct');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['chief_engineer_id']);
            $table->dropForeign(['site_engineer_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['chief_engineer_id', 'site_engineer_id', 'land_area', 'building_area', 'handover_date', 'progress_pct', 'created_by']);
        });
    }
};
