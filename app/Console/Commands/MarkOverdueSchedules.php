<?php

namespace App\Console\Commands;

use App\Models\PaymentSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkOverdueSchedules extends Command
{
    protected $signature   = 'property:mark-overdue';
    protected $description = 'Mark pending/partial payment schedules as overdue when past due date';

    public function handle(): int
    {
        $updated = DB::table('payment_schedules')
            ->where('due_date', '<', today()->toDateString())
            ->whereIn('status', ['pending', 'partial'])
            ->update(['status' => 'overdue', 'updated_at' => now()]);

        $this->info("Marked {$updated} schedule(s) as overdue.");

        return self::SUCCESS;
    }
}
