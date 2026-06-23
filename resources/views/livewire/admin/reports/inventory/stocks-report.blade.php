<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <div class="max-w-[1600px] mx-auto px-6 py-8">

        @php
            $fmt = function ($value, $type) {
                if ($value === null || $value === '') return '—';
                return match ($type) {
                    'money'  => number_format((float) $value, 2),
                    'number' => rtrim(rtrim(number_format((float) $value, 2), '0'), '.'),
                    default  => $value,
                };
            };
        @endphp

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-bold text-slate-900">Stocks Report</h1>
                <p class="text-slate-600 mt-2">Current stock balances by product and store with quantity and value.</p>
            </div>
            <a href="{{ route('admin.reports.category', 'inventory') }}"
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Product</label>
                    <select wire:model.live="productId" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Notes (Optional)</label>
                    <input type="text" wire:model.live="notes" placeholder="Add a note..." class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                </div>
            </div>
        </div>

        {{-- Summary KPIs --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-600">Stock Lines</p>
                <p class="text-3xl font-bold text-slate-900 mt-2">{{ $report['summary']['total_rows'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-600">Total Quantity</p>
                <p class="text-3xl font-bold text-slate-900 mt-2">{{ rtrim(rtrim(number_format((float)$report['summary']['total_quantity'], 2), '0'), '.') }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-600">Total Value</p>
                <p class="text-2xl font-bold text-slate-900 mt-2">{{ number_format((float)$report['summary']['total_value'], 2) }}</p>
            </div>
        </div>

        {{-- Export Buttons --}}
        <div class="flex gap-3 mb-6">
            <a href="{{ $printUrl }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 font-medium text-sm transition">Print</a>
            <a href="{{ $pdfUrl }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 font-medium text-sm transition">PDF</a>
            <a href="{{ $excelUrl }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 font-medium text-sm transition">Excel</a>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-max w-full text-xs whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            @foreach($report['columns'] as $column)
                                <th class="px-3 py-2.5 text-{{ $column['align'] }} font-semibold text-slate-900">{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $row)
                            <tr class="border-b border-slate-100 hover:bg-blue-50/50 transition">
                                @foreach($report['columns'] as $column)
                                    <td class="px-3 py-2.5 text-{{ $column['align'] }} text-slate-700 @if($column['key'] === 'product') font-medium @endif">
                                        {{ $fmt($row[$column['key']] ?? null, $column['type']) }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['columns']) }}" class="px-6 py-12 text-center">
                                    <p class="text-slate-600 font-medium">No stock found</p>
                                    <p class="text-slate-500 text-sm">Try adjusting your filters</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($report['rows']) > 0)
                        <tfoot>
                            <tr class="bg-slate-50 border-t-2 border-slate-300 font-semibold text-slate-900">
                                {{-- cols 1-5 --}}
                                <td class="px-3 py-2.5 text-left" colspan="5">Total ({{ $report['summary']['total_rows'] }} products)</td>
                                {{-- col 6: Total Stock --}}
                                <td class="px-3 py-2.5 text-right">{{ rtrim(rtrim(number_format((float)$report['summary']['total_quantity'], 2), '0'), '.') }}</td>
                                {{-- col 7: Avg Unit Price --}}
                                <td class="px-3 py-2.5"></td>
                                {{-- col 8: Total Value --}}
                                <td class="px-3 py-2.5 text-right">{{ number_format((float)$report['summary']['total_value'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
