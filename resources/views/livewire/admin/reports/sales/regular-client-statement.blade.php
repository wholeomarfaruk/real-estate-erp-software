<div>
    {{-- Header --}}
    <div class="max-w-7xl mx-auto px-6 py-7">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-ink-1">Regular Client Statement</h1>
                <p class="text-ink-3 mt-1">All clients with outstanding balances</p>
            </div>
            <a href="{{ route('admin.reports.category', 'sales') }}" class="text-ink-2 hover:text-ink-1 text-sm">
                ← Back to Sales Reports
            </a>
        </div>

        {{-- Filter Bar --}}
        <div class="bg-paper border border-rule rounded-lg p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                {{-- Project Filter --}}
                <div>
                    <label class="block text-sm font-medium text-ink-1 mb-2">Project</label>
                    <select wire:model.live="projectId" class="input w-full">
                        <option value="">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer Filter --}}
                <div>
                    <label class="block text-sm font-medium text-ink-1 mb-2">Client</label>
                    <select wire:model.live="customerId" class="input w-full">
                        <option value="">All Clients</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Property Filter --}}
                <div>
                    <label class="block text-sm font-medium text-ink-1 mb-2">Unit / Property</label>
                    <select wire:model.live="propertyId" class="input w-full">
                        <option value="">All Units</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}">{{ $property->name }} ({{ $property->property->name ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Sale Type --}}
                <div>
                    <label class="block text-sm font-medium text-ink-1 mb-2">Sale Type</label>
                    <select wire:model.live="saleType" class="input w-full">
                        <option value="all">All Types</option>
                        <option value="sale">Sale</option>
                        <option value="rent">Rent</option>
                    </select>
                </div>
            </div>

            {{-- Date Range and Presets --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-ink-1 mb-2">From Date</label>
                    <input type="date" wire:model.live="fromDate" class="input w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-1 mb-2">To Date</label>
                    <input type="date" wire:model.live="toDate" class="input w-full">
                </div>

                {{-- Preset Buttons --}}
                <div class="lg:col-span-2 flex gap-2 items-end">
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
            </div>

            {{-- Reset Button --}}
            <div class="flex justify-end gap-2">
                <button wire:click="resetFilters()" class="btn btn-secondary">
                    Reset Filters
                </button>
            </div>
        </div>

        {{-- Summary KPIs --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-paper border border-rule rounded-lg p-4">
                <div class="text-ink-3 text-sm mb-1">Total Clients</div>
                <div class="text-3xl font-bold text-ink-1">{{ $report['summary']['total_clients'] }}</div>
            </div>
            <div class="bg-paper border border-rule rounded-lg p-4">
                <div class="text-ink-3 text-sm mb-1">Total Outstanding</div>
                <div class="text-3xl font-bold text-ink-1">{{ number_format((float)$report['summary']['total_outstanding'], 2) }}</div>
            </div>
            <div class="bg-paper border border-rule rounded-lg p-4">
                <div class="text-ink-3 text-sm mb-1">Total Due This Month</div>
                <div class="text-3xl font-bold text-ink-1">{{ number_format((float)$report['summary']['total_due_this_month'], 2) }}</div>
            </div>
        </div>

        {{-- Data Table --}}
        <div class="bg-paper border border-rule rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-ink-5 text-ink-1 border-b border-rule">
                            @foreach($report['columns'] as $column)
                                <th class="px-4 py-4 text-{{ $column['align'] }} font-semibold">
                                    {{ $column['label'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $row)
                            <tr class="border-b border-rule hover:bg-ink-5/30 transition">
                                @foreach($report['columns'] as $column)
                                    <td class="px-4 py-3 text-{{ $column['align'] }}
                                        @if($column['key'] === 'status')
                                            @if($row['status'] === 'Overdue') text-red-600 font-semibold
                                            @elseif($row['status'] === 'Current') text-green-600 font-semibold
                                            @endif
                                        @endif
                                    ">
                                        @if(in_array($column['key'], ['contract_value', 'total_paid', 'outstanding_balance', 'due_amount']))
                                            {{ number_format((float)$row[$column['key']], 2) }}
                                        @else
                                            {{ $row[$column['key']] ?? '-' }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['columns']) }}" class="px-4 py-8 text-center text-ink-3">
                                    No data available for the selected filters
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Export Buttons --}}
        <div class="flex gap-3 mt-6">
            <a href="{{ $printUrl }}" target="_blank" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                </svg>
                Print
            </a>
            <a href="{{ $pdfUrl }}" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Export PDF
            </a>
            <a href="{{ $excelUrl }}" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"></path>
                    <line x1="9" y1="7" x2="9" y2="17"></line>
                    <line x1="15" y1="7" x2="15" y2="17"></line>
                </svg>
                Export Excel
            </a>
        </div>
    </div>
</div>
