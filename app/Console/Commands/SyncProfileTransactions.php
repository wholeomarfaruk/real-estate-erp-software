<?php

namespace App\Console\Commands;

use App\Services\Profiles\ProfileTransactionResolver;
use Illuminate\Console\Command;

class SyncProfileTransactions extends Command
{
    protected $signature = 'profiles:sync-transactions';

    protected $description = 'Tag accounting entries with their master profile (party) using their payroll / invoice / installment references';

    public function handle(ProfileTransactionResolver $resolver): int
    {
        $count = $resolver->sync();
        $this->info("Tagged {$count} transactions with a master profile.");

        return self::SUCCESS;
    }
}
