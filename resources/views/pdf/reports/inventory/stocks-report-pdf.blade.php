<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        * { font-family: "DejaVu Sans", sans-serif; box-sizing: border-box; }

        :root { --ink: #161616; --muted: #5f5f5f; --muted-2: #8a8a8a; --rule: #c9c9c9; --zebra: #f4f4f4; --paper: #ffffff; }

        @page { size: A4 landscape; margin: 7mm; }

        body { font-size: 8px; color: var(--ink); margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; background: var(--paper); }

        .head { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .head td { vertical-align: top; padding: 0; }
        .co-name { font-size: 14px; font-weight: bold; }
        .co-addr { font-size: 8px; color: var(--muted); margin-top: 2px; }
        .doc-tag { display: inline-block; border: 1px solid var(--ink); font-size: 7.5px; font-weight: bold; letter-spacing: 1.4px; padding: 2px 9px; text-transform: uppercase; }
        .doc-title { font-size: 12px; font-weight: bold; margin-top: 5px; }
        .doc-meta { font-size: 8px; color: var(--muted); margin-top: 3px; line-height: 1.5; }
        .doc-meta b { color: var(--ink); }
        .divider { height: 2px; background: var(--ink); margin: 6px 0; }

        .table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .table thead th { background: var(--zebra); color: var(--ink); font-size: 7px; font-weight: bold; text-transform: uppercase; padding: 4px 3px; border: 1px solid var(--ink); word-wrap: break-word; }
        .table tbody td { padding: 4px 3px; font-size: 7.5px; border: 1px solid var(--rule); vertical-align: middle; word-wrap: break-word; }
        .table tbody tr.zebra td { background: var(--zebra); }
        .t-left { text-align: left; } .t-right { text-align: right; } .t-center { text-align: center; }
        .num { font-family: "DejaVu Sans Mono", monospace; }
        tfoot td { padding: 5px 3px; font-size: 8px; font-weight: bold; border: 1px solid var(--ink); background: var(--zebra); }

        .note { margin-top: 8px; font-size: 8px; color: var(--muted); }
        .note-h { font-weight: bold; text-transform: uppercase; letter-spacing: .6px; color: var(--muted-2); margin-bottom: 3px; }
        .foot { width: 100%; border-top: 1px solid var(--rule); padding-top: 5px; margin-top: 8px; border-collapse: collapse; }
        .foot td { font-size: 7.5px; color: var(--muted-2); }
    </style>
</head>

<body>
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

    <table class="head">
        <tr>
            <td style="width:60%;">
                <div class="co-name">{{ $report['meta']['company_name'] }}</div>
                <div class="co-addr">{{ config('company.address') }} · {{ config('company.phone') }} · {{ config('company.email') }}</div>
            </td>
            <td style="width:40%; text-align:right;">
                <span class="doc-tag">Report</span>
                <div class="doc-title">{{ $report['title'] }}</div>
                <div class="doc-meta"><b>Generated:</b> {{ $report['meta']['generated_at'] }} by {{ $report['meta']['generated_by'] }}</div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

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
                <tr @if ($loop->even) class="zebra" @endif>
                    @foreach ($report['columns'] as $column)
                        <td class="t-{{ $column['align'] }} @if(in_array($column['type'], ['money','number'])) num @endif">
                            {{ $fmt($row[$column['key']] ?? null, $column['type']) }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($report['columns']) }}" style="text-align:center; padding:14px;">No records found.</td></tr>
            @endforelse
        </tbody>
        @if (count($report['rows']) > 0)
            <tfoot>
                <tr>
                    <td class="t-left" colspan="5">Total ({{ $report['summary']['total_rows'] }} products)</td>
                    <td class="t-right num">{{ rtrim(rtrim(number_format((float) $report['summary']['total_quantity'], 2), '0'), '.') }}</td>
                    <td></td>
                    <td class="t-right num">{{ number_format((float) $report['summary']['total_value'], 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if (!empty($report['meta']['notes']))
        <div class="note"><div class="note-h">Notes</div>{{ $report['meta']['notes'] }}</div>
    @endif

    <table class="foot">
        <tr>
            <td style="text-align:left;">{{ config('company.name') }} · {{ $report['title'] }}</td>
            <td style="text-align:right;">Generated {{ $report['meta']['generated_at'] }}</td>
        </tr>
    </table>
</body>

</html>
