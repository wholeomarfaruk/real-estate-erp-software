<?php

namespace App\Console\Commands;

use App\Models\BankingPaymentRequest;
use App\Services\Accounts\BankingTransactionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BankingBackfillTransactionsCommand extends Command
{
    protected $signature = 'banking:backfill-transactions
                            {--dry-run : Show what would be processed without making changes}
                            {--only-failed : Only process requests that previously failed}
    ';

    protected $description = 'Backfill transaction records for completed banking payment requests that lack transaction_id';

    public function handle(BankingTransactionService $service): int
    {
        $dryRun = $this->option('dry-run');
        $onlyFailed = $this->option('only-failed');

        $query = BankingPaymentRequest::where('status', 'completed')
            ->whereNull('transaction_id');

        if ($onlyFailed) {
            $query->whereNotNull('rejected_at');
        }

        $requests = $query->orderBy('id')->get();

        if ($requests->isEmpty()) {
            $this->info('No requests to process.');
            return self::SUCCESS;
        }

        $this->info("Found {$requests->count()} request(s) to process.");

        if ($dryRun) {
            $this->warn('[DRY RUN] No changes will be made.');
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($requests as $request) {
            try {
                if ($dryRun) {
                    $this->line("  ✓ Would process: {$request->request_no} (${$request->amount})");
                    $successCount++;
                } else {
                    $transaction = $service->completePaymentRequest($request, auth()->id() ?? 0);
                    $request->refresh();

                    $this->line("  ✓ Processed: {$request->request_no} (TXN: {$transaction->id})");
                    $successCount++;
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ Failed: {$request->request_no} — {$e->getMessage()}");
                $failureCount++;
            }
        }

        $this->newLine();
        $this->info("Summary: {$successCount} succeeded, {$failureCount} failed.");

        return $failureCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
