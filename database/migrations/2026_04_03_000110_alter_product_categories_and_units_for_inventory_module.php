<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_categories', 'status')) {
                $table->boolean('status')->default(true)->after('description');
                $table->index('status');
            }
        });

        Schema::table('product_units', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_units', 'code')) {
                $table->string('code', 50)->nullable()->after('name');
                $table->unique('code');
            }

            if (! Schema::hasColumn('product_units', 'status')) {
                $table->boolean('status')->default(true)->after('code');
                $table->index('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table): void {
            if (Schema::hasColumn('product_units', 'status')) {
                $table->dropIndex('product_units_status_index');
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('product_units', 'code')) {
                $table->dropUnique('product_units_code_unique');
                $table->dropColumn('code');
            }
        });

        Schema::table('product_categories', function (Blueprint $table): void {
            if (Schema::hasColumn('product_categories', 'status')) {
                $table->dropIndex('product_categories_status_index');
                $table->dropColumn('status');
            }
        });
    }
};
