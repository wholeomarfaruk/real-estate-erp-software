<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_units', function (Blueprint $table) {
            // New canonical columns alongside existing legacy columns
            $table->string('code', 30)->nullable()->after('property_floor_id');
            $table->string('type')->nullable()->comment('flat|shop|parking')->after('code');
            $table->string('status')->default('available')->comment('available|booked|sold|rented')->after('type');
            $table->decimal('area', 12, 2)->nullable()->after('status');
            $table->decimal('price', 18, 3)->default(0)->after('area');
            $table->decimal('service_charge', 18, 3)->default(0)->after('price');
            $table->unsignedInteger('sort_order')->default(0)->after('service_charge');
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
            $table->index(['property_floor_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('property_units', function (Blueprint $table) {
            $table->dropIndex(['property_floor_id', 'sort_order']);
            $table->dropIndex(['property_units_status_index']);
            $table->dropIndex(['property_units_type_index']);
            $table->dropColumn(['code', 'type', 'status', 'area', 'price', 'service_charge', 'sort_order', 'deleted_at']);
        });
    }
};
