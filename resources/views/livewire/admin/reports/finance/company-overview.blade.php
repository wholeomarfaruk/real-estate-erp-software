<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <div class="max-w-[1600px] mx-auto px-6 py-8">

        {{-- Value formatter for a cell based on the column type --}}
        @php
            $fmt = function ($value, $type) {
                if ($value === null || $value === '' ) return '—';
                return match ($type) {
                    'money'  => number_format((float) $value, 0),
                    'number' => rtrim(rtrim(number_format((float) $value, 2), '0'), '.'),
                    default  => $value,
                };
            };
        @endphp

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-bold text-slate-900">Company Overview Report</h1>
                <p class="text-slate-600 mt-2">A single, wide overview of every property sale — flat, financials, recovery & outstanding.</p>
            </div>
            <a href="{{ route('admin.reports.category', 'finance') }}"
               class="flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back
            </a>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wider">Filters</h2>
                <button wire:click="resetFilters()" class="text-xs font-medium text-blue-600 hover:text-blue-700 hover:underline transition">
                    Clear All
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Project</label>
                    <select wire:model.live="projectId" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Client</label>
                    <select wire:model.live="customerId" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="">All Clients</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Unit Type (flat / shop / parking — from DB) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Unit Type</label>
                    <select wire:model.live="unitType" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="">All Types</option>
                        @foreach($unitTypes as $type)
                            <option value="{{ $type->slug }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Purpose (sale / rent) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Purpose</label>
                    <select wire:model.live="purpose" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="all">All</option>
                        <option value="sale">Sale</option>
                        <option value="rent">Rent</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">From</label>
                    <input type="date" wire:model.live="fromDate" class="flatpickr-only-date w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">To</label>
                    <input type="date" wire:model.live="toDate" class="flatpickr-only-date w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                </div>

                <div class="flex items-end gap-2">
                    <button wire:click="applyPreset('month')"
                            class="px-3 h-10 text-xs font-medium rounded-lg transition @if($preset === 'month') bg-blue-500 text-white shadow-sm @else bg-slate-100 text-slate-700 hover:bg-slate-200 @endif">Month</button>
                    <button wire:click="applyPreset('year')"
                            class="px-3 h-10 text-xs font-medium rounded-lg transition @if($preset === 'year') bg-blue-500 text-white shadow-sm @else bg-slate-100 text-slate-700 hover:bg-slate-200 @endif">Year</button>
                    <button wire:click="applyPreset('all')"
                            class="px-3 h-10 text-xs font-medium rounded-lg transition @if($preset === 'all') bg-blue-500 text-white shadow-sm @else bg-slate-100 text-slate-700 hover:bg-slate-200 @endif">All</button>
                </div>
            </div>

            <div class="mt-5">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Notes (Optional)</label>
                <textarea wire:model.live="notes" placeholder="Add notes for this report..." rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition resize-none"></textarea>
            </div>
        </div>

        {{-- Summary KPIs --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-600">Total Clients</p>
                <p class="text-3xl font-bold text-slate-900 mt-2">{{ $report['summary']['total_clients'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-600">Total Flat Value</p>
                <p class="text-2xl font-bold text-slate-900 mt-2">{{ number_format((float)$report['summary']['total_flat_value'], 0) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-600">Total Recovery</p>
                <p class="text-2xl font-bold text-green-600 mt-2">{{ number_format((float)$report['summary']['total_recovery'], 0) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-600">Present Outstanding</p>
                <p class="text-2xl font-bold text-red-600 mt-2">{{ number_format((float)$report['summary']['total_outstanding'], 0) }}</p>
            </div>
        </div>

        {{-- Export Buttons --}}
        <div class="flex gap-3 mb-6">
            <a href="{{ $printUrl }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 font-medium text-sm transition">Print</a>
            <a href="{{ $pdfUrl }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 font-medium text-sm transition">PDF</a>
            <a href="{{ $excelUrl }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 font-medium text-sm transition">Excel</a>
        </div>

        {{-- Single wide, horizontally scrollable table --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-max w-full text-xs whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            @foreach($report['columns'] as $column)
                                <th class="px-2 py-2 text-{{ $column['align'] }} font-semibold text-slate-900">
                                    {{ $column['label'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $row)
                            <tr class="border-b border-slate-100 hover:bg-blue-50/50 transition">
                                @foreach($report['columns'] as $column)
                                    <td class="px-2 py-2 text-{{ $column['align'] }} text-slate-700
                                        @if(in_array($column['key'], ['flat_value','total_flat_value','total_recovery','present_outstanding'])) font-medium @endif">
                                        @if($column['key'] === 'payment_status')
                                            @php $pv = strtolower($row['payment_status'] ?? ''); @endphp
                                            <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold
                                                @if($pv === 'paid') bg-green-100 text-green-700
                                                @elseif($pv === 'partial') bg-blue-100 text-blue-700
                                                @elseif($pv === 'cancelled') bg-slate-200 text-slate-600
                                                @else bg-amber-100 text-amber-700 @endif">
                                                {{ $row['payment_status'] ?? '—' }}
                                            </span>
                                        @elseif($column['key'] === 'status')
                                            @php $sv = strtolower($row['status'] ?? ''); @endphp
                                            <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold
                                                @if($sv === 'completed') bg-green-100 text-green-700
                                                @elseif($sv === 'active') bg-indigo-100 text-indigo-700
                                                @elseif($sv === 'cancelled') bg-rose-100 text-rose-700
                                                @else bg-slate-200 text-slate-600 @endif">
                                                {{ $row['status'] ?? '—' }}
                                            </span>
                                        @else
                                            {{ $fmt($row[$column['key']] ?? null, $column['type']) }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['columns']) }}" class="px-6 py-12 text-center">
                                    <p class="text-slate-600 font-medium">No data available</p>
                                    <p class="text-slate-500 text-sm">Try adjusting your filters</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($report['rows']) > 0)
                        <tfoot>
                            <tr class="bg-slate-50 border-t-2 border-slate-300 font-semibold text-slate-900">
                                {{-- cols 1-9 --}}
                                <td class="px-2 py-2 text-left" colspan="9">Total ({{ $report['summary']['total_clients'] }} clients)</td>
                                {{-- col 10: Total Flat Value --}}
                                <td class="px-2 py-2 text-right">{{ number_format((float)$report['summary']['total_flat_value'], 0) }}</td>
                                {{-- cols 11-14 --}}
                                <td class="px-2 py-2" colspan="4"></td>
                                {{-- col 15: Total Recovery --}}
                                <td class="px-2 py-2 text-right">{{ number_format((float)$report['summary']['total_recovery'], 0) }}</td>
                                {{-- col 16: Present Outstanding --}}
                                <td class="px-2 py-2 text-right">{{ number_format((float)$report['summary']['total_outstanding'], 0) }}</td>
                                {{-- cols 17-19: Payment Status, Status, Reference --}}
                                <td class="px-2 py-2" colspan="3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
