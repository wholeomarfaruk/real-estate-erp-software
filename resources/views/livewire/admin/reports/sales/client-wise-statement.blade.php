<div>
    <div class="max-w-7xl mx-auto px-6 py-7">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-ink-1">Client Wise Statement</h1>
                <p class="text-ink-3 mt-1 text-sm">Sales &amp; rent transactions with payment status for a single client.</p>
            </div>
            <a href="{{ route('admin.reports.sales.regular-client-statement') }}"
               class="inline-flex items-center gap-1.5 text-ink-2 hover:text-ink-1 text-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Clients
            </a>
        </div>

        {{-- Filter Bar --}}
        <div class="bg-paper border border-rule rounded-xl p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
                {{-- Customer Selection (reusable custom select — searchable) --}}
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-ink-3 mb-2">Customer</label>
                    <x-forms.select
                        wire:model.live="customerId"
                        :options="$customers->map(fn ($c) => ['value' => $c['id'], 'label' => $c['label']])->all()"
                        placeholder="Search and select a customer..."
                        search-placeholder="Search customers..." />
                </div>

                {{-- Transaction Type Filter --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-ink-3 mb-2">Type</label>
                    <select wire:model.live="transactionType" class="input w-full">
                        @foreach($transactionTypes as $type)
                            <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- From Date --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-ink-3 mb-2">From</label>
                    <input type="date" wire:model.live="fromDate" class="flatpickr-only-date input w-full">
                </div>

                {{-- To Date --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-ink-3 mb-2">To</label>
                    <input type="date" wire:model.live="toDate" class="flatpickr-only-date input w-full">
                </div>
            </div>

            {{-- Presets + Reset --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex gap-2">
                    <button wire:click="applyPreset('today')"
                            class="btn text-sm px-3 py-1.5 @if($preset === 'today') btn-primary @else btn-secondary @endif">
                        Today
                    </button>
                    <button wire:click="applyPreset('month')"
                            class="btn text-sm px-3 py-1.5 @if($preset === 'month') btn-primary @else btn-secondary @endif">
                        This Month
                    </button>
                    <button wire:click="applyPreset('year')"
                            class="btn text-sm px-3 py-1.5 @if($preset === 'year') btn-primary @else btn-secondary @endif">
                        This Year
                    </button>
                </div>
                <button wire:click="resetFilters()" class="btn btn-secondary text-sm px-3 py-1.5">
                    Reset Filters
                </button>
            </div>
        </div>

        @if($report && $report['customer'])
            @php($customer = $report['customer'])

            {{-- Customer Profile + KPIs --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
                {{-- Profile card --}}
                <div class="lg:col-span-1 bg-paper border border-rule rounded-xl p-5">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 w-14 h-14 rounded-full bg-accent/10 text-accent flex items-center justify-center text-lg font-bold">
                            {{ $customer['initials'] }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-ink-1 truncate">{{ $customer['name'] }}</h2>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                @if($customer['code'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-ink-5 text-ink-2">
                                        {{ $customer['code'] }}
                                    </span>
                                @endif
                                @if($customer['type'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-accent/10 text-accent capitalize">
                                        {{ $customer['type'] }}
                                    </span>
                                @endif
                                @if($customer['status'])
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize',
                                        'bg-green-100 text-green-700' => $customer['status'] === 'active',
                                        'bg-ink-5 text-ink-2' => $customer['status'] !== 'active',
                                    ])>
                                        {{ $customer['status'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <dl class="mt-5 space-y-3 text-sm">
                        @if($customer['company_name'])
                            <div class="flex items-start gap-2.5">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 mt-0.5 text-ink-3 shrink-0">
                                    <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"></path>
                                </svg>
                                <span class="text-ink-1">{{ $customer['company_name'] }}</span>
                            </div>
                        @endif
                        @if($customer['phone'])
                            <div class="flex items-start gap-2.5">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 mt-0.5 text-ink-3 shrink-0">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.81.36 1.6.7 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.74-1.27a2 2 0 0 1 2.11-.45c.74.34 1.53.57 2.34.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <a href="tel:{{ $customer['phone'] }}" class="text-ink-1 hover:text-accent">{{ $customer['phone'] }}</a>
                            </div>
                        @endif
                        @if($customer['email'])
                            <div class="flex items-start gap-2.5">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 mt-0.5 text-ink-3 shrink-0">
                                    <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <a href="mailto:{{ $customer['email'] }}" class="text-ink-1 hover:text-accent truncate">{{ $customer['email'] }}</a>
                            </div>
                        @endif
                        @if($customer['address'])
                            <div class="flex items-start gap-2.5">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 mt-0.5 text-ink-3 shrink-0">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span class="text-ink-1">{{ $customer['address'] }}</span>
                            </div>
                        @endif
                    </dl>

                    <a href="{{ route('admin.crm.customers.show', $customer['id']) }}"
                       class="mt-5 inline-flex items-center gap-1.5 text-sm font-medium text-accent hover:underline">
                        View full profile
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>

                {{-- KPIs --}}
                <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div class="bg-paper border border-rule rounded-xl p-4">
                        <div class="text-ink-3 text-xs uppercase tracking-wide mb-1">Properties</div>
                        <div class="text-2xl font-bold text-ink-1">{{ $report['summary']['total_transactions'] }}</div>
                    </div>
                    <div class="bg-paper border border-rule rounded-xl p-4">
                        <div class="text-ink-3 text-xs uppercase tracking-wide mb-1">Sale Amount</div>
                        <div class="text-2xl font-bold text-ink-1">{{ number_format((float)$report['summary']['total_sale_amount'], 0) }}</div>
                    </div>
                    <div class="bg-paper border border-rule rounded-xl p-4">
                        <div class="text-ink-3 text-xs uppercase tracking-wide mb-1">Rent Amount</div>
                        <div class="text-2xl font-bold text-ink-1">{{ number_format((float)$report['summary']['total_rent_amount'], 0) }}</div>
                    </div>
                    <div class="bg-green-50 border border-green-100 rounded-xl p-4">
                        <div class="text-green-700/70 text-xs uppercase tracking-wide mb-1">Total Paid</div>
                        <div class="text-2xl font-bold text-green-700">{{ number_format((float)$report['summary']['total_paid'], 0) }}</div>
                    </div>
                    <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                        <div class="text-red-700/70 text-xs uppercase tracking-wide mb-1">Outstanding</div>
                        <div class="text-2xl font-bold text-red-700">{{ number_format((float)$report['summary']['total_outstanding'], 0) }}</div>
                    </div>
                    <div class="bg-paper border border-rule rounded-xl p-4">
                        <div class="text-ink-3 text-xs uppercase tracking-wide mb-1">Scheduled Payments</div>
                        <div class="text-2xl font-bold text-ink-1">{{ $report['summary']['total_scheduled'] }}</div>
                    </div>
                    <div @class([
                        'rounded-xl p-4 border',
                        'bg-red-50 border-red-100' => (int) $report['summary']['total_overdue'] > 0,
                        'bg-paper border-rule' => (int) $report['summary']['total_overdue'] === 0,
                    ])>
                        <div @class([
                            'text-xs uppercase tracking-wide mb-1',
                            'text-red-700/70' => (int) $report['summary']['total_overdue'] > 0,
                            'text-ink-3' => (int) $report['summary']['total_overdue'] === 0,
                        ])>Overdue</div>
                        <div @class([
                            'text-2xl font-bold',
                            'text-red-700' => (int) $report['summary']['total_overdue'] > 0,
                            'text-ink-1' => (int) $report['summary']['total_overdue'] === 0,
                        ])>{{ $report['summary']['total_overdue'] }}</div>
                    </div>
                    <div class="bg-paper border border-rule rounded-xl p-4 flex flex-col justify-center">
                        <div class="text-ink-3 text-xs uppercase tracking-wide mb-2">Export</div>
                        <div class="flex gap-2">
                            <a href="{{ $printUrl }}" target="_blank" title="Print"
                               class="btn btn-secondary p-2" aria-label="Print">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                </svg>
                            </a>
                            <a href="{{ $pdfUrl }}" title="PDF" class="btn btn-secondary p-2" aria-label="PDF">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                            </a>
                            <a href="{{ $excelUrl }}" title="Excel" class="btn btn-secondary p-2" aria-label="Excel">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                                    <path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"></path>
                                    <line x1="9" y1="7" x2="9" y2="17"></line>
                                    <line x1="15" y1="7" x2="15" y2="17"></line>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transaction Table --}}
            <div class="bg-paper border border-rule rounded-xl overflow-hidden">
                <div class="px-5 py-3.5 border-b border-rule flex items-center justify-between">
                    <h3 class="font-semibold text-ink-1">Properties</h3>
                    <span class="text-xs text-ink-3">{{ $report['summary']['total_transactions'] }} record(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-ink-5/60 text-ink-2 border-b border-rule">
                                @foreach($report['columns'] as $column)
                                    <th class="px-4 py-3 text-{{ $column['align'] }} font-semibold text-xs uppercase tracking-wide">
                                        {{ $column['label'] }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['rows'] as $row)
                                <tr class="border-b border-rule last:border-0 hover:bg-ink-5/20 transition">
                                    @foreach($report['columns'] as $column)
                                        <td class="px-4 py-3 text-{{ $column['align'] }}">
                                            @if($column['key'] === 'status')
                                                @php($statusLower = strtolower($row['status']))
                                                <span @class([
                                                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold',
                                                    'bg-green-100 text-green-700' => in_array($statusLower, ['paid', 'completed']),
                                                    'bg-blue-100 text-blue-700' => $statusLower === 'partial',
                                                    'bg-orange-100 text-orange-700' => $statusLower === 'pending',
                                                    'bg-red-100 text-red-700' => $statusLower === 'cancelled',
                                                    'bg-yellow-100 text-yellow-700' => ! in_array($statusLower, ['paid', 'completed', 'partial', 'pending', 'cancelled']),
                                                ])>
                                                    {{ $row['status'] }}
                                                </span>
                                            @elseif($column['key'] === 'overdue_count')
                                                @if((int)$row['overdue_count'] > 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                                        {{ $row['overdue_count'] }}
                                                    </span>
                                                @else
                                                    <span class="text-ink-3">0</span>
                                                @endif
                                            @elseif($column['key'] === 'scheduled_count')
                                                <span class="text-ink-2 font-medium">{{ $row['scheduled_count'] }}</span>
                                            @elseif(in_array($column['key'], ['amount', 'total_paid', 'total_due']))
                                                <span class="@if($column['key'] === 'total_due' && (float)$row['total_due'] > 0) text-red-600 font-medium @endif">
                                                    {{ number_format((float)$row[$column['key']], 0) }}
                                                </span>
                                            @else
                                                {{ $row[$column['key']] ?? '-' }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($report['columns']) }}" class="px-4 py-10 text-center text-ink-3">
                                        No transactions available for the selected filters
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            {{-- Empty state — no customer selected --}}
            <div class="bg-paper border border-dashed border-rule rounded-xl p-16 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-ink-5 flex items-center justify-center mb-4">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-7 h-7 text-ink-3">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-ink-1 mb-1">Select a customer</h3>
                <p class="text-ink-3 text-sm">Choose a customer above to view their information and transaction statement.</p>
            </div>
        @endif
    </div>
</div>
