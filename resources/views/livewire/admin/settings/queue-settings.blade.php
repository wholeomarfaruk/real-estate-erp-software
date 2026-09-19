<div class="p-6 space-y-6" wire:poll.30s="refreshStats">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Queue Worker Settings</h1>
            <p class="text-gray-600 text-sm mt-1">SMS, email and marketing messages are sent through a background queue — this page shows whether it's running and how to set it up.</p>
        </div>
        <button type="button" wire:click="refreshStats"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm font-medium">
            Refresh
        </button>
    </div>

    <!-- Status banner -->
    @if ($queueConnection === 'sync')
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <p class="text-sm font-semibold text-blue-800">Queue connection is "sync"</p>
            <p class="text-xs text-blue-700 mt-1">Messages send immediately in the same request — no worker needed. This is simple but slows down the page while sending.</p>
        </div>
    @elseif ($workerLikelyRunning)
        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
            <p class="text-sm font-semibold text-green-800">✓ Queue looks healthy</p>
            <p class="text-xs text-green-700 mt-1">Pending jobs are being picked up in a reasonable time.</p>
        </div>
    @else
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-semibold text-red-800">⚠ Queue worker does not appear to be running</p>
            <p class="text-xs text-red-700 mt-1">
                There {{ $pendingJobs === 1 ? 'is' : 'are' }} {{ $pendingJobs }} job(s) waiting, and the oldest one has been sitting for
                <strong>{{ $oldestPendingAge }}</strong> without being processed. Messages will stay "Queued" forever until a worker runs.
                See the setup instructions below.
            </p>
        </div>
    @endif

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Queue Driver</p>
            <p class="text-xl font-bold text-gray-900 mt-1">{{ $queueConnection }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Jobs</p>
            <p class="text-xl font-bold text-gray-900 mt-1">{{ $pendingJobs }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Failed Jobs</p>
            <p class="text-xl font-bold {{ $failedJobs > 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ $failedJobs }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Oldest Pending</p>
            <p class="text-xl font-bold text-gray-900 mt-1">{{ $oldestPendingAge ?? '—' }}</p>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="bg-white rounded-lg shadow p-5 space-y-3">
        <h2 class="text-sm font-semibold text-gray-900">Quick Actions</h2>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="runQueueOnce" wire:loading.attr="disabled" wire:target="runQueueOnce"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50">
                <span wire:loading.remove wire:target="runQueueOnce">Run Queue Once Now</span>
                <span wire:loading wire:target="runQueueOnce">Running…</span>
            </button>

            @if ($failedJobs > 0)
                <button type="button" wire:click="retryFailed"
                    class="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 transition text-sm font-medium">
                    Retry Failed Jobs
                </button>
                <button type="button" wire:click="flushFailed"
                    onclick="return confirm('Clear the failed jobs log? This cannot be undone.')"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm font-medium">
                    Clear Failed Log
                </button>
            @endif
        </div>
        <p class="text-xs text-gray-500">"Run Queue Once Now" processes everything currently waiting and then stops — it's a manual nudge, not a replacement for the cron/worker setup below. Use it if messages are stuck right now.</p>

        @if ($lastRunOutput)
            <pre class="mt-2 text-xs bg-gray-900 text-gray-100 rounded-lg p-3 overflow-x-auto whitespace-pre-wrap">{{ $lastRunOutput }}</pre>
        @endif
    </div>

    <!-- Setup instructions -->
    <div class="bg-white rounded-lg shadow p-5 space-y-5">
        <h2 class="text-sm font-semibold text-gray-900">How to Set Up the Queue Worker (do this once on the server)</h2>
        <p class="text-sm text-gray-600">
            "{{ $queueConnection }}" jobs (SMS sending, campaign messages, notifications) sit in the <code class="bg-gray-100 px-1 rounded">jobs</code> table
            until something processes them. Pick the option that matches your hosting:
        </p>

        <div class="border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-800">Option A — Shared / cPanel hosting: Cron Job (recommended for this setup)</h3>
            <p class="text-xs text-gray-600 mt-1 mb-2">
                Add a cron job that runs every minute and processes whatever is waiting, then exits. This is the safest option on shared hosting since there's no long-running process to babysit.
            </p>
            <p class="text-xs text-gray-500 mb-1">cPanel → Cron Jobs → "Once per minute" → command:</p>
            <div class="flex items-center gap-2">
                <code class="flex-1 bg-gray-900 text-gray-100 text-xs rounded-lg px-3 py-2 overflow-x-auto">cd {{ $appPath }} &amp;&amp; {{ $phpBinary }} artisan queue:work --stop-when-empty --max-time=50 &gt;&gt; /dev/null 2&gt;&amp;1</code>
                <button type="button"
                    x-data
                    @click="navigator.clipboard.writeText('cd {{ $appPath }} && {{ $phpBinary }} artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1')"
                    class="px-3 py-2 bg-gray-100 rounded-lg text-xs hover:bg-gray-200 shrink-0">Copy</button>
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-800">Option B — VPS with Supervisor (recommended if you have root/SSH)</h3>
            <p class="text-xs text-gray-600 mt-1 mb-2">
                Keeps a worker permanently running and auto-restarts it if it crashes. Create <code class="bg-gray-100 px-1 rounded">/etc/supervisor/conf.d/queue-worker.conf</code>:
            </p>
            <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-3 overflow-x-auto">[program:erp-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command={{ $phpBinary }} {{ $appPath }}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory={{ $appPath }}
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile={{ $appPath }}/storage/logs/queue-worker.log
stopwaitsecs=3600</pre>
            <p class="text-xs text-gray-500 mt-2">Then: <code class="bg-gray-100 px-1 rounded">supervisorctl reread &amp;&amp; supervisorctl update &amp;&amp; supervisorctl start erp-queue-worker:*</code></p>
        </div>

        <div class="border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-800">Option C — Simplest: switch to "sync" (no worker needed)</h3>
            <p class="text-xs text-gray-600 mt-1">
                Set <code class="bg-gray-100 px-1 rounded">QUEUE_CONNECTION=sync</code> in <code class="bg-gray-100 px-1 rounded">.env</code> and run
                <code class="bg-gray-100 px-1 rounded">php artisan config:clear</code>. Messages then send immediately instead of being queued —
                simplest to operate, but sending a campaign to many recipients will make the page wait until every SMS finishes.
                Not recommended for bulk campaigns.
            </p>
        </div>

        <p class="text-xs text-gray-400">After setting up A or B, come back and hit "Refresh" above — pending jobs should drop to 0 within a minute and the banner should turn green.</p>
    </div>
</div>
