<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Migrate existing single string values into JSON array format first
        DB::table('projects')
            ->whereNotNull('project_type')
            ->where('project_type', '!=', '')
            ->get(['id', 'project_type'])
            ->each(function ($row) {
                // Only migrate if not already JSON
                $decoded = json_decode($row->project_type, true);
                if (!is_array($decoded)) {
                    DB::table('projects')->where('id', $row->id)->update([
                        'project_type' => json_encode([$row->project_type]),
                    ]);
                }
            });

        // Change column type from string to json
        Schema::table('projects', function (Blueprint $table) {
            $table->json('project_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Revert json back to string (take first element)
        DB::table('projects')
            ->whereNotNull('project_type')
            ->get(['id', 'project_type'])
            ->each(function ($row) {
                $decoded = json_decode($row->project_type, true);
                $value = is_array($decoded) ? ($decoded[0] ?? null) : $row->project_type;
                DB::table('projects')->where('id', $row->id)->update([
                    'project_type' => $value,
                ]);
            });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_type')->nullable()->change();
        });
    }
};
