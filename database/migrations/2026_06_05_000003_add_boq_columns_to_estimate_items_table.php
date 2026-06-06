<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            if (!Schema::hasColumn('estimate_items', 'material_id')) {
                $table->unsignedBigInteger('material_id')->nullable()->after('project_estimate_id');
            }
            if (!Schema::hasColumn('estimate_items', 'transaction_category_id')) {
                $table->foreignId('transaction_category_id')->nullable()->constrained('transaction_categories')->nullOnDelete()->after('material_id');
            }
            if (!Schema::hasColumn('estimate_items', 'estimated_qty')) {
                $table->decimal('estimated_qty', 12, 2)->default(0)->after('unit');
            }
            if (!Schema::hasColumn('estimate_items', 'estimated_rate')) {
                $table->decimal('estimated_rate', 15, 2)->default(0)->after('estimated_qty');
            }
            if (!Schema::hasColumn('estimate_items', 'estimated_amount')) {
                $table->decimal('estimated_amount', 15, 2)->default(0)->after('estimated_rate');
            }
            if (!Schema::hasColumn('estimate_items', 'cost_type')) {
                $table->string('cost_type')->nullable()->after('estimated_amount');
            }
            if (!Schema::hasColumn('estimate_items', 'work_phase')) {
                $table->string('work_phase')->nullable()->after('cost_type');
            }
            if (!Schema::hasColumn('estimate_items', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('work_phase');
            }
            if (!Schema::hasColumn('estimate_items', 'is_optional')) {
                $table->boolean('is_optional')->default(false)->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            $table->dropForeign(['transaction_category_id']);
            $table->dropColumn(['material_id', 'transaction_category_id', 'estimated_qty', 'estimated_rate', 'estimated_amount', 'cost_type', 'work_phase', 'sort_order', 'is_optional']);
        });
    }
};
