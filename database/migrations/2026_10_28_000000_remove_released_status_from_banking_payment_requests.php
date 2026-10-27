<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The "released" step was removed: approved requests are now completed directly.
    public function up(): void
    {
        DB::table('banking_payment_requests')
            ->where('status', 'released')
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
        // Irreversible: previously released rows cannot be told apart from approved ones.
    }
};
