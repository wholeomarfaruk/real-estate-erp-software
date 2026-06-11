<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
        }
        body {
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .report-meta {
            font-size: 10px;
            color: #666;
            margin-bottom: 2px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table thead tr {
            background-color: #f0f0f0;
            border-bottom: 2px solid #333;
        }
        .table th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-right: 1px solid #ddd;
            font-size: 10px;
        }
        .table th:last-child {
            border-right: none;
        }
        .table td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
            border-right: 1px solid #ddd;
        }
        .table td:last-child {
            border-right: none;
        }
        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #333;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #ddd;
        }
        .summary-label {
            font-weight: bold;
            width: 40%;
        }
        .summary-value {
            text-align: right;
            width: 55%;
            font-weight: bold;
        }
        .status-current {
            color: #27ae60;
        }
        .status-overdue {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $report['meta']['company_name'] }}</div>
        <div class="report-title">{{ $report['title'] }}</div>
        <div class="report-meta">Generated: {{ $report['meta']['generated_at'] }}</div>
        @if($report['meta']['from_date'] !== '-' && $report['meta']['to_date'] !== '-')
            <div class="report-meta">Period: {{ $report['meta']['from_date'] }} to {{ $report['meta']['to_date'] }}</div>
        @endif
    </div>

    <table class="table">
        <thead>
            <tr>
                @foreach($report['columns'] as $column)
                    <th style="text-align: {{ $column['align'] }}">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($report['rows'] as $row)
                <tr>
                    @foreach($report['columns'] as $column)
                        <td style="text-align: {{ $column['align'] }}
                            @if($column['key'] === 'status')
                                @if($row['status'] === 'Overdue') class="status-overdue"
                                @elseif($row['status'] === 'Current') class="status-current"
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
            @endforeach
        </tbody>
    </table>

    <div class="summary-section">
        <div class="summary-row">
            <span class="summary-label">Total Clients:</span>
            <span class="summary-value">{{ $report['summary']['total_clients'] }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Outstanding:</span>
            <span class="summary-value">{{ number_format((float)$report['summary']['total_outstanding'], 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Due This Month:</span>
            <span class="summary-value">{{ number_format((float)$report['summary']['total_due_this_month'], 2) }}</span>
        </div>
    </div>
</body>
</html>
