<div x-data x-init="$store.pageName = { name: 'Banking Reports' }">

    <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Reports</h1>
            <p class="text-sm text-gray-500">Banking and accounts financial reports.</p>
        </div>
    </div>

    @include('livewire.admin.accounts.banking.partials.nav-tabs', ['active' => 'reports'])

    @php
        $reportLinks = [
            [
                'label'       => 'Cash Book',
                'description' => 'Daily cash inflows and outflows.',
                'route'       => 'admin.accounts.reports.cash-book',
                'icon'        => '<rect x="2" y="6" width="20" height="12" rx="1"/><circle cx="12" cy="12" r="2.5"/>',
                'color'       => 'text-amber-600 bg-amber-50 border-amber-200',
            ],
            [
                'label'       => 'Bank Book',
                'description' => 'Bank account transaction register.',
                'route'       => 'admin.accounts.reports.bank-book',
                'icon'        => '<path d="M3 21h18M5 21V10m4 11V10m6 11V10m4 11V10M2 10l10-7 10 7"/>',
                'color'       => 'text-blue-600 bg-blue-50 border-blue-200',
            ],
            [
                'label'       => 'Account Ledger',
                'description' => 'Per-account transaction ledger.',
                'route'       => 'admin.accounts.reports.account-ledger',
                'icon'        => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/>',
                'color'       => 'text-indigo-600 bg-indigo-50 border-indigo-200',
            ],
            [
                'label'       => 'Daily Summary',
                'description' => 'Day-wise income, expense, and net summary.',
                'route'       => 'admin.accounts.reports.daily-summary',
                'icon'        => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
                'color'       => 'text-emerald-600 bg-emerald-50 border-emerald-200',
            ],
            [
                'label'       => 'Trial Balance',
                'description' => 'Debit/credit summary across all accounts.',
                'route'       => 'admin.accounts.reports.trial-balance',
                'icon'        => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
                'color'       => 'text-violet-600 bg-violet-50 border-violet-200',
            ],
            [
                'label'       => 'Balance Sheet',
                'description' => 'Assets, liabilities and equity position.',
                'route'       => 'admin.accounts.reports.balance-sheet',
                'icon'        => '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>',
                'color'       => 'text-gray-600 bg-gray-100 border-gray-200',
            ],
            [
                'label'       => 'Profit & Loss',
                'description' => 'Revenue, expenses and net profit/loss.',
                'route'       => 'admin.accounts.reports.profit-loss',
                'icon'        => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
                'color'       => 'text-rose-600 bg-rose-50 border-rose-200',
            ],
            [
                'label'       => 'Statement Sheet',
                'description' => 'Full statement for any account or period.',
                'route'       => 'admin.accounts.reports.statement',
                'icon'        => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
                'color'       => 'text-teal-600 bg-teal-50 border-teal-200',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($reportLinks as $rep)
            @if(Route::has($rep['route']))
                <a href="{{ route($rep['route']) }}"
                    class="group flex items-start gap-4 rounded-xl border border-gray-200 bg-white p-5 transition hover:shadow-md hover:-translate-y-px">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border {{ $rep['color'] }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            {!! $rep['icon'] !!}
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600 transition">{{ $rep['label'] }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $rep['description'] }}</p>
                    </div>
                    <svg class="h-4 w-4 shrink-0 text-gray-300 group-hover:text-gray-500 transition mt-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            @endif
        @endforeach
    </div>

</div>
