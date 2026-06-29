<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_structures', function (Blueprint $table): void {
            $table->decimal('mobile_allowance', 14, 2)->default(0)->after('transport_allowance');
        });
    }

    public function down(): void
    {
        Schema::table('salary_structures', function (Blueprint $table): void {
            $table->dropColumn('mobile_allowance');
        });
    }
};
