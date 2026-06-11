<table border="1">
    <thead>
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <th>{{ $report['meta']['company_name'] }}</th>
        </tr>
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <th>{{ $report['title'] }}</th>
        </tr>
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <th>Generated: {{ $report['meta']['generated_at'] }}</th>
        </tr>
        @if($report['meta']['from_date'] !== '-' && $report['meta']['to_date'] !== '-')
            <tr style="background-color: #f0f0f0; font-weight: bold;">
                <th>Period: {{ $report['meta']['from_date'] }} to {{ $report['meta']['to_date'] }}</th>
            </tr>
        @endif
        <tr></tr>
        <tr style="background-color: #e0e0e0; font-weight: bold;">
            @foreach($report['columns'] as $column)
                <th style="text-align: {{ $column['align'] }};"> {{ $column['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($report['rows'] as $row)
            <tr>
                @foreach($report['columns'] as $column)
                    <td style="text-align: {{ $column['align'] }};">
                        @if(in_array($column['key'], ['contract_value', 'total_paid', 'outstanding_balance', 'due_amount']))
                            {{ number_format((float)$row[$column['key']], 2) }}
                        @else
                            {{ $row[$column['key']] ?? '-' }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        <tr></tr>
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <td colspan="{{ count($report['columns']) - 3 }}" style="text-align: right;">Total Clients:</td>
            <td style="text-align: right;">{{ $report['summary']['total_clients'] }}</td>
        </tr>
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <td colspan="{{ count($report['columns']) - 3 }}" style="text-align: right;">Total Outstanding:</td>
            <td style="text-align: right;">{{ number_format((float)$report['summary']['total_outstanding'], 2) }}</td>
        </tr>
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <td colspan="{{ count($report['columns']) - 3 }}" style="text-align: right;">Total Due This Month:</td>
            <td style="text-align: right;">{{ number_format((float)$report['summary']['total_due_this_month'], 2) }}</td>
        </tr>
    </tbody>
</table>
