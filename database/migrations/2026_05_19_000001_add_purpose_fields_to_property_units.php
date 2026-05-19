<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_units', function (Blueprint $table) {
            $table->decimal('down_payment_percentage', 5, 2)->nullable()->after('purpose');
            $table->decimal('deposit_amount', 14, 2)->nullable()->after('down_payment_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('property_units', function (Blueprint $table) {
            $table->dropColumn(['down_payment_percentage', 'deposit_amount']);
        });
    }
};
