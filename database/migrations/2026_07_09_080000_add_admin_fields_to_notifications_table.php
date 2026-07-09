<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('status')->default('sent')->after('action_url');
            $table->string('audience_type')->nullable()->after('status');
            $table->json('audience_value')->nullable()->after('audience_type');
            $table->timestamp('scheduled_at')->nullable()->after('audience_value');
            $table->timestamp('sent_at')->nullable()->after('scheduled_at');
            $table->foreignId('created_by')->nullable()->after('sent_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['status', 'audience_type', 'audience_value', 'scheduled_at', 'sent_at']);
        });
    }
};
