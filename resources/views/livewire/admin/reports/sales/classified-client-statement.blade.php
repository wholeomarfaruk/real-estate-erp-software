<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <div class="max-w-7xl mx-auto px-6 py-8">
        {{-- Header Section --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-bold text-slate-900">Classified Clients</h1>
                <p class="text-slate-600 mt-2">High-risk clients with more than 3 overdue installments</p>
            </div>
            <a href="{{ route('admin.reports.category', 'sales') }}"
               class="flex items-center gap-2 px-4 py-2 text-slate-600 hover:text-slate-900 hover:bg-white rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back
            </a>
        </div>

        {{-- Filters Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-sm font-semibold text-slate-900 uppercase tracking-wider">Filters</h2>
                <button wire:click="resetFilters()" class="text-xs font-medium text-blue-600 hover:text-blue-700 hover:underline transition">
                    Clear All
                </button>
            </div>

            {{-- Filters Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                {{-- Project Filter --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Project</label>
                    <select wire:model.live="projectId" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Client Filter --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Client</label>
                    <select wire:model.live="customerId" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="">All Clients</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Sale Type --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Type</label>
                    <select wire:model.live="saleType" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="all">All Types</option>
                        <option value="sale">Sale</option>
                        <option value="rent">Rent</option>
                    </select>
                </div>

                {{-- From Date --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">From</label>
                    <input type="date" wire:model.live="fromDate" class="flatpickr-only-date w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                </div>

                {{-- To Date --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">To</label>
                    <input type="date" wire:model.live="toDate" class="flatpickr-only-date w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                </div>
            </div>

            {{-- Notes Field --}}
            <div class="mt-5">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Notes (Optional)</label>
                <textarea wire:model.live="notes" placeholder="Add notes for this report..." rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition resize-none"></textarea>
            </div>

            {{-- Preset Buttons --}}
            <div class="flex gap-2 mt-5 pt-5 border-t border-slate-200">
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide py-2">Quick Range:</span>
                <button wire:click="applyPreset('today')"
                        class="px-3 py-2 text-xs font-medium rounded-lg transition @if($preset === 'today') bg-blue-500 text-white shadow-sm @else bg-slate-100 text-slate-700 hover:bg-slate-200 @endif">
                    Today
                </button>
                <button wire:click="applyPreset('month')"
                        class="px-3 py-2 text-xs font-medium rounded-lg transition @if($preset === 'month') bg-blue-500 text-white shadow-sm @else bg-slate-100 text-slate-700 hover:bg-slate-200 @endif">
                    This Month
                </button>
                <button wire:click="applyPreset('year')"
                        class="px-3 py-2 text-xs font-medium rounded-lg transition @if($preset === 'year') bg-blue-500 text-white shadow-sm @else bg-slate-100 text-slate-700 hover:bg-slate-200 @endif">
                    This Year
                </button>
            </div>
        </div>

        {{-- Summary KPIs --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {{-- Total Clients Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-600">Classified Clients</p>
                        <p class="text-3xl font-bold text-slate-900 mt-2">{{ $report['summary']['total_clients'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0a6 6 0 11-12 0 6 6 0 0112 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Outstanding Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-600">Total Outstanding</p>
                        <p class="text-3xl font-bold text-red-600 mt-2">{{ number_format((float)$report['summary']['total_outstanding'], 0) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Paid Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-600">Total Paid</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format((float)$report['summary']['total_paid'], 0) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7 12a5 5 0 1110 0A5 5 0 017 12z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Export Buttons --}}
        <div class="flex gap-3 mb-8">
            <a href="{{ $printUrl }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 font-medium text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4H7a2 2 0 01-2-2v-4a2 2 0 012-2h10a2 2 0 012 2v4a2 2 0 01-2 2zm0 0h4a2 2 0 002-2v-6a2 2 0 00-2-2h-4.5V9"></path>
                </svg>
                Print
            </a>
            <a href="{{ $pdfUrl }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 font-medium text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                PDF
            </a>
            <a href="{{ $excelUrl }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 font-medium text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Excel
            </a>
        </div>


        {{-- Data Table --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            @foreach($report['columns'] as $column)
                                <th class="px-6 py-4 text-{{ $column['align'] }} text-sm font-semibold text-slate-900">
                                    {{ $column['label'] }}
                                </th>
                            @endforeach
                            <th class="px-6 py-4 text-center text-sm font-semibold text-slate-900">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $row)
                            <tr class="border-b border-slate-200 hover:bg-blue-50 transition">
                                @foreach($report['columns'] as $column)
                                    <td class="px-6 py-4 text-{{ $column['align'] }} text-sm text-slate-700">
                                        @if(in_array($column['key'], ['total_paid', 'total_due']))
                                            <span class="font-medium">{{ number_format((float)$row[$column['key']], 0) }}</span>
                                        @elseif($column['key'] === 'overdue_count')
                                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">{{ $row['overdue_count'] ?? 0 }}</span>
                                        @else
                                            {{ $row[$column['key']] ?? '-' }}
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.reports.sales.client-wise-statement', ['customer_id' => $row['customer_id']]) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['columns']) + 1 }}" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        <p class="text-slate-600 font-medium">No data available</p>
                                        <p class="text-slate-500 text-sm">Try adjusting your filters</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
