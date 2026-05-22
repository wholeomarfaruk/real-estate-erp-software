<div x-data x-init="$store.pageName = { name: 'Daily Statement', slug: 'accounts-reports' }" class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Daily Statement</h1>
            <p class="text-sm text-gray-500">Income, expense, advance, and transfer transactions grouped into the daily statement sheet.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li>Accounts</li>
                <li>/</li>
                <li><a href="{{ route('admin.accounts.banking.reports') }}" class="hover:text-gray-700">Banking Reports</a></li>
                <li>/</li>
                <li class="text-gray-700">Daily Statement</li>
            </ol>
        </nav>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="lg:col-span-3">
                <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Report Date</label>
                <input type="date" wire:model.live="reportDate" class="mt-1 h-11 w-full rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
            </div>

            <div class="lg:col-span-4">
                <label class="text-xs font-medium uppercase tracking-wide text-gray-500">Bank Account</label>
                <select wire:model.live="bankAccountId" class="mt-1 h-11 w-full rounded-xl border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <option value="">All Bank Accounts</option>
                    @foreach ($bankAccounts as $bankAccount)
                        <option value="{{ $bankAccount->id }}">{{ $bankAccount->bankAccount?->bank_name ?: $bankAccount->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-5 flex flex-wrap items-end justify-start gap-2">
                <button type="button" wire:click="resetFilters" class="inline-flex h-11 items-center rounded-xl border border-gray-300 px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Reset
                </button>

                <a href="{{ $previewUrl }}" target="_blank" class="inline-flex h-11 items-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Open Report
                </a>

                @can('accounts.reports.statement.export')
                    @if ($supportsPdfExport)
                        <a href="{{ $downloadUrl }}" class="inline-flex h-11 items-center rounded-xl bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">
                            Download PDF
                        </a>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Live Preview</h2>
                <p class="mt-1 text-sm text-gray-500">Transactions are grouped by `transaction_category.type` and matched against `income`, `expense`, `advance`, and `transfer`.</p>
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200">
            <iframe src="{{ $embedUrl }}" class="h-[980px] w-full bg-white"></iframe>
        </div>
    </div>
</div>
