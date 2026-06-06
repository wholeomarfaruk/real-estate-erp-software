<div x-data x-init="$store.pageName = { name: 'Balance Sheet', slug: 'accounts-reports' }">
    <div class="space-y-5">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500">{{ $data['meta']['company_name'] }}</p>
                <h1 class="mt-1 text-xl font-bold text-gray-900">Balance Sheet</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $data['meta']['period_label'] }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $excelUrl }}"
                   class="inline-flex h-9 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    Export Excel
                </a>
                @if ($supportsPdfExport)
                    <a href="{{ $pdfUrl }}"
                       class="inline-flex h-9 items-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        Export PDF
                    </a>
                @endif
            </div>
        </div>

        {{-- Filters --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="text-xs font-medium uppercase tracking-wide text-gray-500">As of Date</label>
                    <input type="date" wire:model.live="to_date"
                           class=\"mt-1 h-10 w-44 rounded-lg border border-gray-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flatpickr-only-date\">
                </div>
                <button wire:click="resetFilters"
                        class="h-10 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                    Reset
                </button>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Total Assets</p>
                <p class="mt-2 text-2xl font-bold text-indigo-700">{{ number_format($data['total_assets'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-rose-100 bg-rose-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-500">Total Liabilities</p>
                <p class="mt-2 text-2xl font-bold text-rose-700">{{ number_format($data['total_liabilities'], 2) }}</p>
            </div>
            <div class="rounded-2xl border p-5 {{ $data['is_balanced'] ? 'border-emerald-100 bg-emerald-50' : 'border-amber-100 bg-amber-50' }}">
                <p class="text-xs font-semibold uppercase tracking-wide {{ $data['is_balanced'] ? 'text-emerald-500' : 'text-amber-500' }}">
                    Net Position
                </p>
                <p class="mt-2 text-2xl font-bold {{ $data['is_balanced'] ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ number_format($data['total_assets'] - $data['total_liabilities'], 2) }}
                </p>
                <p class="mt-1 text-xs {{ $data['is_balanced'] ? 'text-emerald-600' : 'text-amber-600' }}">
                    {{ $data['is_balanced'] ? 'Balanced' : 'Assets − Liabilities' }}
                </p>
            </div>
        </div>

        {{-- Two-Panel Layout --}}
        <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

            {{-- ── Assets Panel ── --}}
            <div class="rounded-2xl border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-800">Assets</h2>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        {{ number_format($data['total_assets'], 2) }}
                    </span>
                </div>

                @if (count($data['asset_groups']) === 0)
                    <div class="px-5 py-10 text-center text-sm text-gray-400">No asset accounts found.</div>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach ($data['asset_groups'] as $group)
                            {{-- Group header --}}
                            <div class="bg-gray-50 px-5 py-2.5">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ $group['label'] }}
                                </span>
                            </div>

                            {{-- Group items --}}
                            @foreach ($group['items'] as $item)
                                <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50/60">
                                    <span class="text-sm text-gray-700">{{ $item['name'] }}</span>
                                    <span class="text-sm font-medium tabular-nums text-gray-900">
                                        {{ number_format($item['balance'], 2) }}
                                    </span>
                                </div>
                            @endforeach

                            {{-- Group subtotal --}}
                            <div class="flex items-center justify-between bg-indigo-50/50 px-5 py-2.5">
                                <span class="text-xs font-semibold text-indigo-600">Subtotal — {{ $group['label'] }}</span>
                                <span class="text-sm font-semibold tabular-nums text-indigo-700">
                                    {{ number_format($group['subtotal'], 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Total Assets row --}}
                    <div class="flex items-center justify-between border-t border-indigo-200 bg-indigo-100 px-5 py-3.5">
                        <span class="text-sm font-bold text-indigo-800">Total Assets</span>
                        <span class="text-base font-bold tabular-nums text-indigo-800">
                            {{ number_format($data['total_assets'], 2) }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- ── Liabilities & Equity Panel ── --}}
            <div class="rounded-2xl border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-800">Liabilities & Equity</h2>
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                        {{ number_format($data['total_liabilities'], 2) }}
                    </span>
                </div>

                @if (count($data['liability_items']) === 0)
                    <div class="px-5 py-6 text-sm text-gray-400">No liability accounts.</div>
                @else
                    <div class="bg-gray-50 px-5 py-2.5">
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Liabilities</span>
                    </div>

                    <div class="divide-y divide-gray-50">
                        @foreach ($data['liability_items'] as $item)
                            <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50/60">
                                <span class="text-sm text-gray-700">{{ $item['name'] }}</span>
                                <span class="text-sm font-medium tabular-nums text-gray-900">
                                    {{ number_format($item['balance'], 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Equity / Retained Earnings row --}}
                @php
                    $netEquity = $data['total_assets'] - $data['total_liabilities'];
                @endphp
                <div class="border-t border-gray-100 bg-gray-50 px-5 py-2.5">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Equity</span>
                </div>
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm text-gray-700">Retained Earnings / Net Position</span>
                    <span class="text-sm font-medium tabular-nums {{ $netEquity >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ number_format($netEquity, 2) }}
                    </span>
                </div>

                {{-- Total Liabilities + Equity --}}
                <div class="flex items-center justify-between border-t border-rose-200 bg-rose-100 px-5 py-3.5">
                    <span class="text-sm font-bold text-rose-800">Total Liabilities + Equity</span>
                    <span class="text-base font-bold tabular-nums text-rose-800">
                        {{ number_format($data['total_liabilities'] + $netEquity, 2) }}
                    </span>
                </div>
            </div>

        </div>

        {{-- Balance equation note --}}
        <div class="rounded-xl border px-5 py-3 text-sm
            {{ $data['is_balanced'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
            <span class="font-semibold">Assets</span>
            = <span class="font-semibold">Liabilities + Equity</span>
            &nbsp;|&nbsp;
            {{ number_format($data['total_assets'], 2) }}
            =
            {{ number_format($data['total_liabilities'] + ($data['total_assets'] - $data['total_liabilities']), 2) }}
            @if ($data['is_balanced'])
                &nbsp;✓ Balanced
            @else
                &nbsp;— Difference: {{ number_format(abs($data['total_assets'] - $data['total_liabilities']), 2) }}
            @endif
        </div>

    </div>
</div>
