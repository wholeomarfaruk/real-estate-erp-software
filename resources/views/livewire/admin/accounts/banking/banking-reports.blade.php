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
                'label'       => 'Balance Sheet',
                'description' => 'Assets, liabilities and equity position.',
                'route'       => 'admin.accounts.reports.balance-sheet',
                'icon'        => '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>',
                'color'       => 'text-gray-600 bg-gray-100 border-gray-200',
            ],
           
            [
                'label'       => 'Daily Statement',
                'description' => 'Transaction category based day-wise statement with preview and PDF download.',
                'route'       => 'admin.accounts.reports.daily-statement',
                'icon'        => '<path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/><path d="M8 14h3"/><path d="M8 18h8"/>',
                'color'       => 'text-cyan-700 bg-cyan-50 border-cyan-200',
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
