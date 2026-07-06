<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_password_resets', function (Blueprint $table) {
            $table->dropUnique(['token_hash']);
            $table->dropColumn('token_hash');
            $table->string('code_hash')->after('channel');
            $table->unsignedTinyInteger('attempts')->default(0)->after('code_hash');
        });
    }

    public function down(): void
    {
        Schema::table('client_password_resets', function (Blueprint $table) {
            $table->dropColumn(['code_hash', 'attempts']);
            $table->string('token_hash')->unique()->after('channel');
        });
    }
};
