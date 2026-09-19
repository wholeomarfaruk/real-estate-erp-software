<?php

namespace App\Livewire\Admin\Settings;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class QueueSettings extends Component
{
    public string $queueConnection = '';
    public int $pendingJobs = 0;
    public int $failedJobs = 0;
    public ?string $oldestPendingAge = null;
    public bool $workerLikelyRunning = false;

    public ?string $lastRunOutput = null;

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $this->queueConnection = config('queue.default');

        if ($this->queueConnection === 'sync') {
            $this->pendingJobs = 0;
            $this->failedJobs = DB::table('failed_jobs')->count();
            $this->oldestPendingAge = null;
            $this->workerLikelyRunning = true; // sync has no queue to stall
            return;
        }

        $this->pendingJobs = DB::table('jobs')->count();
        $this->failedJobs = DB::table('failed_jobs')->count();

        $oldest = DB::table('jobs')->orderBy('created_at')->value('created_at');

        if ($oldest) {
            $ageSeconds = now()->timestamp - (int) $oldest;
            $this->oldestPendingAge = now()->subSeconds($ageSeconds)->diffForHumans();
            // If the oldest queued job has been sitting for more than 2 minutes,
            // no worker is very likely picking jobs up.
            $this->workerLikelyRunning = $ageSeconds < 120;
        } else {
            $this->oldestPendingAge = null;
            $this->workerLikelyRunning = true; // nothing pending = can't tell, assume fine
        }
    }

    /**
     * Manually drain the queue once (best-effort). Useful on hosts where a
     * persistent worker/cron isn't set up yet — does not replace one.
     */
    public function runQueueOnce(): void
    {
        try {
            \Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--tries' => 3,
                '--max-time' => 50,
            ]);

            $this->lastRunOutput = trim(\Artisan::output()) ?: 'Queue drained — no output.';

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Queue worker ran once and stopped.']);
        } catch (\Throwable $e) {
            $this->lastRunOutput = $e->getMessage();
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Failed to run queue: ' . $e->getMessage()]);
        }

        $this->refreshStats();
    }

    public function retryFailed(): void
    {
        try {
            \Artisan::call('queue:retry', ['id' => ['all']]);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'All failed jobs re-queued.']);
        } catch (\Throwable $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Retry failed: ' . $e->getMessage()]);
        }

        $this->refreshStats();
    }

    public function flushFailed(): void
    {
        DB::table('failed_jobs')->truncate();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Failed job log cleared.']);
        $this->refreshStats();
    }

    public function render()
    {
        return view('livewire.admin.settings.queue-settings', [
            'appPath' => base_path(),
            'phpBinary' => PHP_BINARY,
        ])->layout('layouts.admin.admin');
    }
}
