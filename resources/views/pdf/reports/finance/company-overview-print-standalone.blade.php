<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report['title'] }}</title>
    <style>
        * { font-family: Arial, Helvetica, sans-serif; box-sizing: border-box; }

        :root { --ink: #161616; --muted: #5f5f5f; --muted-2: #8a8a8a; --rule: #c9c9c9; --zebra: #f4f4f4; }

        @page { size: A4 landscape; margin: 8mm; }

        body { font-size: 9px; color: var(--ink); margin: 0; padding: 14px; background: #e9e9ea; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .sheet { background: #fff; padding: 14px; max-width: 100%; margin: 0 auto; box-shadow: 0 6px 24px -8px rgba(0,0,0,.2); }

        .toolbar { max-width: 100%; margin: 0 auto 12px; text-align: right; }
        .toolbar button { font-size: 12px; padding: 7px 16px; border: 1px solid var(--ink); background: var(--ink); color: #fff; border-radius: 5px; cursor: pointer; }

        .head { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .co-name { font-size: 16px; font-weight: bold; }
        .co-addr { font-size: 9px; color: var(--muted); margin-top: 2px; }
        .doc-title { font-size: 13px; font-weight: bold; }
        .doc-meta { font-size: 9px; color: var(--muted); margin-top: 3px; }
        .doc-meta b { color: var(--ink); }
        .divider { height: 2px; background: var(--ink); margin: 8px 0; }

        .scroll { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table thead th { background: var(--zebra); font-size: 8px; font-weight: bold; text-transform: uppercase; padding: 5px 4px; border: 1px solid var(--ink); white-space: nowrap; }
        .table tbody td { padding: 5px 4px; font-size: 8.5px; border: 1px solid var(--rule); white-space: nowrap; }
        .table tbody tr:nth-child(even) td { background: var(--zebra); }
        .t-left { text-align: left; } .t-right { text-align: right; } .t-center { text-align: center; }
        tfoot td { padding: 6px 4px; font-size: 9px; font-weight: bold; border: 1px solid var(--ink); background: var(--zebra); }
        .badge-uc { background: #fde68a; padding: 1px 6px; border-radius: 8px; font-weight: bold; }
        .badge-paid { background: #bbf7d0; padding: 1px 6px; border-radius: 8px; font-weight: bold; }
        .foot { width: 100%; border-top: 1px solid var(--rule); padding-top: 6px; margin-top: 10px; font-size: 8.5px; color: var(--muted-2); }

        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; padding: 0; }
            .toolbar { display: none; }
            .scroll { overflow: visible; }
        }
    </style>
</head>

<body>
    @php
        $fmt = function ($value, $type) {
            if ($value === null || $value === '') return '—';
            return match ($type) {
                'money'  => number_format((float) $value, 0),
                'number' => rtrim(rtrim(number_format((float) $value, 2), '0'), '.'),
                default  => $value,
            };
        };
    @endphp

    <div class="toolbar"><button onclick="window.print()">Print</button></div>

    <div class="sheet">
        <table class="head">
            <tr>
                <td style="width:60%; vertical-align:top;">
                    <div class="co-name">{{ $report['meta']['company_name'] }}</div>
                    <div class="co-addr">{{ config('company.address') }} · {{ config('company.phone') }} · {{ config('company.email') }}</div>
                </td>
                <td style="width:40%; text-align:right; vertical-align:top;">
                    <div class="doc-title">{{ $report['title'] }}</div>
                    <div class="doc-meta">
                        <b>Period:</b> {{ $report['meta']['from_date'] }} – {{ $report['meta']['to_date'] }}<br>
                        <b>Generated:</b> {{ $report['meta']['generated_at'] }} by {{ $report['meta']['generated_by'] }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="scroll">
            <table class="table">
                <thead>
                    <tr>
                        @foreach ($report['columns'] as $column)
                            <th class="t-{{ $column['align'] }}">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr>
                            @foreach ($report['columns'] as $column)
                                <td class="t-{{ $column['align'] }}">
                                    @if($column['key'] === 'payment_status' && strtolower($row['payment_status'] ?? '') === 'paid')
                                        <span class="badge-paid">{{ $row['payment_status'] }}</span>
                                    @else
                                        {{ $fmt($row[$column['key']] ?? null, $column['type']) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($report['columns']) }}" style="text-align:center; padding:16px;">No records found.</td></tr>
                    @endforelse
                </tbody>
                @if (count($report['rows']) > 0)
                    <tfoot>
                        <tr>
                            <td class="t-left" colspan="9">Total ({{ $report['summary']['total_clients'] }} clients)</td>
                            <td class="t-right">{{ number_format((float) $report['summary']['total_flat_value'], 0) }}</td>
                            <td colspan="4"></td>
                            <td class="t-right">{{ number_format((float) $report['summary']['total_recovery'], 0) }}</td>
                            <td class="t-right">{{ number_format((float) $report['summary']['total_outstanding'], 0) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if (!empty($report['meta']['notes']))
            <p style="margin-top:10px; font-size:9px; color:var(--muted);"><b>Notes:</b> {{ $report['meta']['notes'] }}</p>
        @endif

        <div class="foot">{{ config('company.name') }} · {{ $report['title'] }} · Generated {{ $report['meta']['generated_at'] }}</div>
    </div>

    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>
</body>

</html>
