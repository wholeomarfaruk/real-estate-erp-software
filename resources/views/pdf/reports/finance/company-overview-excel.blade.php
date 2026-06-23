<table border="1">
    <thead>
        <tr>
            <th colspan="{{ count($report['columns']) }}" style="font-size:16px; font-weight:bold;">
                {{ $report['meta']['company_name'] }} — {{ $report['title'] }}
            </th>
        </tr>
        <tr>
            <td colspan="{{ count($report['columns']) }}">
                Period: {{ $report['meta']['from_date'] }} – {{ $report['meta']['to_date'] }}
                &nbsp;|&nbsp; Generated: {{ $report['meta']['generated_at'] }} by {{ $report['meta']['generated_by'] }}
            </td>
        </tr>
        <tr></tr>
        <tr>
            @foreach ($report['columns'] as $column)
                <th style="background:#eeeeee; font-weight:bold;">{{ $column['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php
            $fmt = function ($value, $type) {
                if ($value === null || $value === '') return '';
                return match ($type) {
                    'money'  => number_format((float) $value, 0, '.', ''),
                    'number' => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.'),
                    default  => $value,
                };
            };
        @endphp
        @forelse($report['rows'] as $row)
            <tr>
                @foreach ($report['columns'] as $column)
                    <td>{{ $fmt($row[$column['key']] ?? null, $column['type']) }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($report['columns']) }}">No records found.</td></tr>
        @endforelse

        @if (count($report['rows']) > 0)
            <tr></tr>
            <tr>
                <td colspan="9" style="font-weight:bold;">Total ({{ $report['summary']['total_clients'] }} clients)</td>
                <td style="font-weight:bold;">{{ number_format((float) $report['summary']['total_flat_value'], 0, '.', '') }}</td>
                <td colspan="4"></td>
                <td style="font-weight:bold;">{{ number_format((float) $report['summary']['total_recovery'], 0, '.', '') }}</td>
                <td style="font-weight:bold;">{{ number_format((float) $report['summary']['total_outstanding'], 0, '.', '') }}</td>
                <td colspan="3"></td>
            </tr>
        @endif
    </tbody>
</table>
