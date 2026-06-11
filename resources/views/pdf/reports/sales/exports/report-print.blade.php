<x-layouts.admin.admin>
<div class="max-w-full mx-auto px-6 py-7">

    {{-- Header --}}
    <div class="mb-8 pb-6 border-b border-rule">
        <h1 class="text-3xl font-bold text-ink-1 mb-2">{{ $report['title'] }}</h1>
        <div class="text-sm text-ink-3 space-y-1">
            <p><strong>Company:</strong> {{ $report['meta']['company_name'] }}</p>
            <p><strong>Generated:</strong> {{ $report['meta']['generated_at'] }}</p>
            @if($report['meta']['from_date'] !== '-' && $report['meta']['to_date'] !== '-')
                <p><strong>Period:</strong> {{ $report['meta']['from_date'] }} to {{ $report['meta']['to_date'] }}</p>
            @endif
        </div>
    </div>

    {{-- Summary KPIs --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-paper border border-rule rounded-lg p-4">
            <div class="text-ink-3 text-sm mb-1">Total Clients</div>
            <div class="text-2xl font-bold text-ink-1">{{ $report['summary']['total_clients'] }}</div>
        </div>
        <div class="bg-paper border border-rule rounded-lg p-4">
            <div class="text-ink-3 text-sm mb-1">Total Paid</div>
            <div class="text-2xl font-bold text-ink-1">{{ number_format((float)($report['summary']['total_paid'] ?? 0), 2) }}</div>
        </div>
        <div class="bg-paper border border-rule rounded-lg p-4">
            <div class="text-ink-3 text-sm mb-1">Total Outstanding</div>
            <div class="text-2xl font-bold text-ink-1">{{ number_format((float)$report['summary']['total_outstanding'], 2) }}</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="bg-ink-5 text-ink-1">
                    @foreach($report['columns'] as $column)
                        <th class="px-4 py-3 text-{{ $column['align'] }} font-semibold border-b-2 border-rule">
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $row)
                    <tr class="border-b border-rule hover:bg-paper transition">
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
                            No data available
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Print button --}}
    <div class="mt-6 flex gap-3 print:hidden">
        <button onclick="window.print()" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path></svg>
            Print
        </button>
        <a href="javascript:history.back()" class="btn btn-secondary">
            Back
        </a>
    </div>

</div>
</x-layouts.admin.admin>
