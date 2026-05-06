<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        .header {
            margin-bottom: 16px;
        }

        .header h1,
        .header p {
            margin: 0;
        }

        .header h1 {
            font-size: 18px;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
            font-size: 10px;
        }

        .text-right {
            text-align: right;
        }

        tfoot td {
            background: #f9fafb;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <p>{{ $report['meta']['company_name'] }}</p>
        <h1>{{ $report['title'] }}</h1>
        <p>{{ $report['meta']['period_label'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($report['columns'] as $column)
                    <th class="{{ ($column['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">
                        {{ $column['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($report['columns'] as $column)
                        <td class="{{ ($column['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">
                            {{ $row[$column['key']] ?? '-' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['columns']) }}">No report data found.</td>
                </tr>
            @endforelse
        </tbody>
        @if (! empty($report['footer']))
            <tfoot>
                <tr>
                    @foreach ($report['columns'] as $column)
                        <td class="{{ ($column['align'] ?? 'left') === 'right' ? 'text-right' : '' }}">
                            {{ $report['footer'][$column['key']] ?? '' }}
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
