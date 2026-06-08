<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_sales', function (Blueprint $table) {
            $table->json('extra_data')->nullable()->after('payment_status'); // for aditional data, like amenities, features, etc.

            //features data json example:
            // {
            //     "features": {
            //[name=>'parking',unit_name=>'parking-002',amount=>15000],
            //[name=>'elevator',unit_name=>'elevator-001',amount=>0],
            // [name=>'security guard',unit_name=>'security-guard-001',amount=>20000],
            //        },
            //   "terms_conditions": {
            //         [name=>'security guard fee','value'=>'monthly fee send to building secretary'],
            //         [name=>'maintenance fee','value'=>'300tk/monthly fee send to building secretary'],
            //     }
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_sales', function (Blueprint $table) {
            $table->dropColumn('extra_data');
        });
    }
};
