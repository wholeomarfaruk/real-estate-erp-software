<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_sales', function (Blueprint $table) {
            $table->string('schedule_name')->nullable()->default(null)->change();
            $table->decimal('down_payment_percentage', 5, 2)->nullable();
            
        });
    }

    public function down(): void
    {
        Schema::table('property_sales', function (Blueprint $table) {
            $table->string('schedule_name')->nullable(false)->change();
            $table->dropColumn('down_payment_percentage');
        });
    }
};
