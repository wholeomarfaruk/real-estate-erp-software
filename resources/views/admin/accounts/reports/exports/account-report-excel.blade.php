<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
</head>
<body>
    <table>
        <tr>
            <td colspan="{{ count($report['columns']) }}"><strong>{{ $report['meta']['company_name'] }}</strong></td>
        </tr>
        <tr>
            <td colspan="{{ count($report['columns']) }}"><strong>{{ $report['title'] }}</strong></td>
        </tr>
        <tr>
            <td colspan="{{ count($report['columns']) }}">{{ $report['meta']['period_label'] }}</td>
        </tr>
        <tr><td colspan="{{ count($report['columns']) }}"></td></tr>
        <tr>
            @foreach ($report['columns'] as $column)
                <th>{{ $column['label'] }}</th>
            @endforeach
        </tr>
        @forelse ($report['rows'] as $row)
            <tr>
                @foreach ($report['columns'] as $column)
                    <td>{{ $row[$column['key']] ?? '' }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($report['columns']) }}">No report data found.</td>
            </tr>
        @endforelse

        @if (! empty($report['footer']))
            <tr>
                @foreach ($report['columns'] as $column)
                    <td><strong>{{ $report['footer'][$column['key']] ?? '' }}</strong></td>
                @endforeach
            </tr>
        @endif
    </table>
</body>
</html>
