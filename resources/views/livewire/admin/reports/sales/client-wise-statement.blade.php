<div>
    <div class="max-w-7xl mx-auto px-6 py-7">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-ink-1">{{ $report['summary']['customer_name'] }}</h1>
                <p class="text-ink-3 mt-1">Transaction details and payment status</p>
            </div>
            <a href="{{ route('admin.reports.sales.regular-client-statement') }}" class="text-ink-2 hover:text-ink-1 text-sm">
                ← Back to Clients
            </a>
        </div>

        {{-- Filter Bar --}}
        <div class="bg-paper border border-rule rounded-lg p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                {{-- Transaction Type Filter --}}
                <div>
                    <label class="block text-sm font-medium text-ink-1 mb-2">Transaction Type</label>
                    <select wire:model.live="transactionType" class="input w-full">
                        @foreach($transactionTypes as $type)
                            <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- From Date --}}
                <div>
                    <label class="block text-sm font-medium text-ink-1 mb-2">From Date</label>
                    <input type="date" wire:model.live="fromDate" class="flatpickr-only-date input w-full">
                </div>

                {{-- To Date --}}
                <div>
                    <label class="block text-sm font-medium text-ink-1 mb-2">To Date</label>
                    <input type="date" wire:model.live="toDate" class="flatpickr-only-date input w-full">
                </div>
            </div>

            {{-- Preset Buttons --}}
            <div class="flex gap-2 mb-4">
                <button wire:click="applyPreset('today')"
                        class="btn text-sm px-3 py-2 @if($preset === 'today') btn-primary @else btn-secondary @endif">
                    Today
                </button>
                <button wire:click="applyPreset('month')"
                        class="btn text-sm px-3 py-2 @if($preset === 'month') btn-primary @else btn-secondary @endif">
                    This Month
                </button>
                <button wire:click="applyPreset('year')"
                        class="btn text-sm px-3 py-2 @if($preset === 'year') btn-primary @else btn-secondary @endif">
                    This Year
                </button>
            </div>

            {{-- Reset Button --}}
            <div class="flex justify-end gap-2">
                <button wire:click="resetFilters()" class="btn btn-secondary">
                    Reset
                </button>
            </div>
        </div>

        {{-- Summary KPIs --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-paper border border-rule rounded-lg p-4">
                <div class="text-ink-3 text-sm mb-1">Transactions</div>
                <div class="text-2xl font-bold text-ink-1">{{ $report['summary']['total_transactions'] }}</div>
            </div>
            <div class="bg-paper border border-rule rounded-lg p-4">
                <div class="text-ink-3 text-sm mb-1">Sale Amount</div>
                <div class="text-2xl font-bold text-ink-1">{{ number_format((float)$report['summary']['total_sale_amount'], 0) }}</div>
            </div>
            <div class="bg-paper border border-rule rounded-lg p-4">
                <div class="text-ink-3 text-sm mb-1">Rent Amount</div>
                <div class="text-2xl font-bold text-ink-1">{{ number_format((float)$report['summary']['total_rent_amount'], 0) }}</div>
            </div>
            <div class="bg-paper border border-rule rounded-lg p-4">
                <div class="text-ink-3 text-sm mb-1">Total Paid</div>
                <div class="text-2xl font-bold text-green-600">{{ number_format((float)$report['summary']['total_paid'], 0) }}</div>
            </div>
            <div class="bg-paper border border-rule rounded-lg p-4">
                <div class="text-ink-3 text-sm mb-1">Outstanding</div>
                <div class="text-2xl font-bold text-red-600">{{ number_format((float)$report['summary']['total_outstanding'], 0) }}</div>
            </div>
        </div>

        {{-- Export Buttons --}}
        <div class="flex gap-3 mb-6">
            <a href="{{ $printUrl }}" target="_blank" class="btn btn-secondary text-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                </svg>
                Print
            </a>
            <a href="{{ $pdfUrl }}" class="btn btn-secondary text-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                PDF
            </a>
            <a href="{{ $excelUrl }}" class="btn btn-secondary text-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"></path>
                    <line x1="9" y1="7" x2="9" y2="17"></line>
                    <line x1="15" y1="7" x2="15" y2="17"></line>
                </svg>
                Excel
            </a>
        </div>

        {{-- Transaction Table --}}
        <div class="bg-paper border border-rule rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-ink-5 text-ink-1 border-b border-rule">
                            @foreach($report['columns'] as $column)
                                <th class="px-4 py-3 text-{{ $column['align'] }} font-semibold">
                                    {{ $column['label'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $row)
                            <tr class="border-b border-rule hover:bg-ink-5/20 transition">
                                @foreach($report['columns'] as $column)
                                    <td class="px-4 py-3 text-{{ $column['align'] }}
                                        @if($column['key'] === 'status')
                                            @if($row['status'] === 'Completed') text-green-600 font-semibold
                                            @elseif($row['status'] === 'Pending') text-orange-600 font-semibold
                                            @else text-yellow-600 font-semibold
                                            @endif
                                        @endif
                                    ">
                                        @if(in_array($column['key'], ['amount', 'total_paid', 'total_due']))
                                            {{ number_format((float)$row[$column['key']], 0) }}
                                        @else
                                            {{ $row[$column['key']] ?? '-' }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['columns']) }}" class="px-4 py-8 text-center text-ink-3">
                                    No transactions available for the selected filters
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
