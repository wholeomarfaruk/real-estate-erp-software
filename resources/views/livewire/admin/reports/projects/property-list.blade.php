<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <div class="max-w-[1600px] mx-auto px-6 py-8">

        @php
            $fmt = function ($value, $type) {
                if ($value === null || $value === '') return '—';
                return match ($type) {
                    'number' => rtrim(rtrim(number_format((float) $value, 2), '0'), '.'),
                    default  => $value,
                };
            };
        @endphp

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-bold text-slate-900">Property List</h1>
                <p class="text-slate-600 mt-2">All properties with their project, type, area and unit counts.</p>
            </div>
            <a href="{{ route('admin.reports.category', 'project') }}"
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
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Project</label>
                    <select wire:model.live="projectId" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Type</label>
                    <select wire:model.live="type" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="all">All Types</option>
                        <option value="residential">Residential</option>
                        <option value="commercial">Commercial</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Status</label>
                    <select wire:model.live="status" class="w-full h-10 px-3 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white transition">
                        <option value="all">All Statuses</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}">{{ ucwords(str_replace('_', ' ', $st)) }}</option>
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
                <p class="text-sm font-medium text-slate-600">Total Properties</p>
                <p class="text-3xl font-bold text-slate-900 mt-2">{{ $report['summary']['total_properties'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-600">Total Units</p>
                <p class="text-3xl font-bold text-slate-900 mt-2">{{ $report['summary']['total_units'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p class="text-sm font-medium text-slate-600">Total Area</p>
                <p class="text-2xl font-bold text-slate-900 mt-2">{{ number_format((float)$report['summary']['total_area'], 0) }}</p>
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
                                    <td class="px-3 py-2.5 text-{{ $column['align'] }} text-slate-700 @if($column['key'] === 'name') font-medium @endif">
                                        @if($column['key'] === 'status')
                                            @php $sv = strtolower($row['status'] ?? ''); @endphp
                                            <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold
                                                @if($sv === 'active') bg-green-100 text-green-700
                                                @elseif($sv === 'inactive') bg-slate-200 text-slate-600
                                                @else bg-amber-100 text-amber-700 @endif">
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
                                    <p class="text-slate-600 font-medium">No properties found</p>
                                    <p class="text-slate-500 text-sm">Try adjusting your filters</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($report['rows']) > 0)
                        <tfoot>
                            <tr class="bg-slate-50 border-t-2 border-slate-300 font-semibold text-slate-900">
                                {{-- cols 1-6 --}}
                                <td class="px-3 py-2.5 text-left" colspan="6">Total ({{ $report['summary']['total_properties'] }} properties)</td>
                                {{-- col 7: Total Area --}}
                                <td class="px-3 py-2.5 text-right">{{ number_format((float)$report['summary']['total_area'], 0) }}</td>
                                {{-- col 8: Land Size --}}
                                <td class="px-3 py-2.5"></td>
                                {{-- col 9: Floors --}}
                                <td class="px-3 py-2.5"></td>
                                {{-- col 10: Units --}}
                                <td class="px-3 py-2.5 text-center">{{ $report['summary']['total_units'] }}</td>
                                {{-- col 11: Status --}}
                                <td class="px-3 py-2.5"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
