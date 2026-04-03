<?php

use App\Enums\Inventory\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'sku')) {
                $table->string('sku', 100)->nullable()->after('name');
                $table->unique('sku');
            }

            if (! Schema::hasColumn('products', 'product_unit_id')) {
                $table->foreignId('product_unit_id')
                    ->nullable()
                    ->after('unit')
                    ->constrained('product_units')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('products', 'minimum_stock_level')) {
                $table->decimal('minimum_stock_level', 10, 3)->default(0)->after('description');
            }

            if (! Schema::hasColumn('products', 'status')) {
                $table->string('status', 20)->default(ProductStatus::ACTIVE->value)->after('minimum_stock_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('products', 'minimum_stock_level')) {
                $table->dropColumn('minimum_stock_level');
            }

            if (Schema::hasColumn('products', 'product_unit_id')) {
                $table->dropConstrainedForeignId('product_unit_id');
            }

            if (Schema::hasColumn('products', 'sku')) {
                $table->dropUnique('products_sku_unique');
                $table->dropColumn('sku');
            }
        });
    }
};
