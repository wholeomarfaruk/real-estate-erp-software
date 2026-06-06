<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_estimates', function (Blueprint $table) {
            if (!Schema::hasColumn('project_estimates', 'estimate_no')) {
                $table->string('estimate_no')->unique()->nullable()->after('id');
            }
            if (!Schema::hasColumn('project_estimates', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('estimate_no');
            }
            if (!Schema::hasColumn('project_estimates', 'estimate_date')) {
                $table->date('estimate_date')->nullable()->after('title');
            }
            if (!Schema::hasColumn('project_estimates', 'status')) {
                $table->string('status')->default('draft')->after('estimate_date');
            }
            if (!Schema::hasColumn('project_estimates', 'total_estimated_amount')) {
                $table->decimal('total_estimated_amount', 15, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('project_estimates', 'attachments')) {
                $table->json('attachments')->nullable()->after('total_estimated_amount');
            }
            if (!Schema::hasColumn('project_estimates', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('attachments');
            }
            if (!Schema::hasColumn('project_estimates', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            }
            if (!Schema::hasColumn('project_estimates', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_estimates', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['estimate_no', 'version', 'estimate_date', 'status', 'total_estimated_amount', 'attachments', 'created_by', 'approved_by', 'approved_at']);
        });
    }
};
