<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        /* ============================================================
     DomPDF-safe · monochrome (laser & colour printer friendly)
     · DejaVu Sans / DejaVu Sans Mono
     · table layout only · ink + grays, no colour fills
     ============================================================ */
        * {
            font-family: "DejaVu Sans", sans-serif;
            box-sizing: border-box;
        }

        :root {
            --ink: #161616;
            --ink-2: #333333;
            --muted: #5f5f5f;
            --muted-2: #8a8a8a;
            --rule: #c9c9c9;
            --rule-2: #9c9c9c;
            --soft: #e4e4e4;
            --zebra: #f4f4f4;
            --paper: #ffffff;
            --bg: #e9e9ea;
        }

        body {
            font-size: 11px;
            color: var(--ink);
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            background: var(--paper);
        }

        .workspace {
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
            background: var(--paper);
        }

        .sheet {
            width: 272mm;
            min-height: 180mm;
            background: var(--paper);
            box-shadow: 0 1px 2px rgba(0, 0, 0, .04), 0 12px 40px -8px rgba(0, 0, 0, .18);
            padding: 10mm 13mm 8mm;
            position: relative;
            overflow: visible;
        }

        .sheet::before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: 3px;
            background: var(--ink);
        }

        /* Header */
        .head {
            width: 100%;
            border-collapse: collapse;
        }

        .head td {
            vertical-align: top;
            padding: 0;
        }

        .co-name {
            font-size: 15.5px;
            font-weight: bold;
            color: var(--ink);
            line-height: 1.2;
            white-space: nowrap;
        }

        .co-addr {
            font-size: 9.5px;
            color: var(--muted);
            line-height: 1.5;
            margin-top: 3px;
        }

        .doc-tag {
            display: inline-block;
            border: 1px solid var(--ink);
            color: var(--ink);
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1.6px;
            padding: 3px 11px;
            text-transform: uppercase;
        }

        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: var(--ink);
            margin-top: 8px;
            line-height: 1.25;
        }

        .doc-meta {
            font-size: 9.5px;
            color: var(--muted);
            margin-top: 5px;
            line-height: 1.7;
        }

        .doc-meta b {
            color: var(--ink);
            font-weight: bold;
        }

        .divider {
            height: 2px;
            background: var(--ink);
            margin: 14px 0 0;
        }

        .divider-soft {
            height: 1px;
            background: var(--rule);
            margin: 0 0 16px;
        }

        /* Info strip */
        .info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            border: 1px solid var(--rule);
        }

        .info td {
            padding: 8px 12px;
            vertical-align: top;
            border-right: 1px solid var(--rule);
        }

        .info td:last-child {
            border-right: 0;
        }

        .info .k {
            color: var(--muted-2);
            font-size: 8px;
            font-weight: bold;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .info .v {
            color: var(--ink);
            font-size: 11px;
            font-weight: bold;
            margin-top: 3px;
        }

        /* Data table */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead th {
            background: var(--zebra);
            color: var(--ink);
            font-size: 9px;
            font-weight: bold;
            letter-spacing: .5px;
            text-transform: uppercase;
            padding: 9px;
            text-align: left;
            border: 1px solid var(--ink);
        }

        .table tbody td {
            padding: 9px;
            font-size: 10px;
            border: 1px solid var(--rule);
            vertical-align: middle;
        }

        .table tbody tr.zebra td {
            background: var(--zebra);
        }

        .num {
            font-family: "DejaVu Sans Mono", monospace;
            text-align: right;
            white-space: nowrap;
            font-size: 10px;
        }

        .num-due {
            font-weight: bold;
            color: var(--ink);
        }

        .num-zero {
            color: var(--muted-2);
        }

        .count {
            font-family: "DejaVu Sans Mono", monospace;
            font-weight: bold;
            text-align: center;
            font-size: 10.5px;
        }

        .client-name {
            font-weight: bold;
            color: var(--ink);
            font-size: 10.5px;
        }

        .client-id {
            font-size: 8.5px;
            color: var(--muted-2);
            margin-top: 2px;
        }

        /* Totals band */
        .totals-h {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            margin: 20px 0 7px;
        }

        .totals {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--ink);
        }

        .totals td {
            vertical-align: top;
            padding: 10px 12px;
            border-right: 1px solid var(--rule);
            text-align: left;
        }

        .totals td:last-child {
            border-right: 0;
        }

        .totals .t-k {
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: .6px;
            text-transform: uppercase;
            color: var(--muted-2);
        }

        .totals .t-v {
            font-size: 12px;
            font-weight: bold;
            color: var(--ink);
            margin-top: 5px;
            font-family: "DejaVu Sans Mono", monospace;
        }

        .totals td.t-emph {
            background: var(--zebra);
            border: 2px solid var(--ink);
        }

        /* Notes */
        .note {
            margin-top: 14px;
            font-size: 9px;
            color: var(--muted);
            line-height: 1.7;
        }

        .note .note-h {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: .7px;
            text-transform: uppercase;
            color: var(--muted-2);
            margin-bottom: 4px;
        }

        /* Footer */
        .foot {
            width: 100%;
            margin-top: 18px;
            border-top: 1px solid var(--rule);
            padding-top: 7px;
            border-collapse: collapse;
        }

        .foot td {
            font-size: 8.5px;
            color: var(--muted-2);
            vertical-align: middle;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        @media print {

            html,
            body {
                background: #fff;
            }

            .workspace {
                padding: 0;
                gap: 0;
            }

            .sheet {
                box-shadow: none;
                page-break-after: always;
            }
        }
    </style>
</head>

<body>
    <div class="workspace">
        <div class="sheet">

            <!-- Header -->
            <table class="head">
                <tr>
                    <td style="width:64%;">
                        <div class="co-name">{{ $report['meta']['company_name'] }}</div>
                        <div class="co-addr">
                            {{ config('company.address') }}<br>{{ config('company.phone') }} ·
                            {{ config('company.email') }}</div>
                    </td>
                    <td style="width:36%; text-align:right;">
                        <span class="doc-tag">Statement</span>
                        <div class="doc-title">{{ $report['title'] ?? 'All Client Statement' }}</div>
                        <div class="doc-meta">
                            <b>Generated:</b> {{ $report['meta']['generated_at'] ?? now()->format('d M Y, H:i') }}<br>
                            <b>Generated by:</b> {{ $report['meta']['generated_by'] ?? 'System' }}
                        </div>
                    </td>
                </tr>
            </table>

            <div class="divider"></div>
            <div class="divider-soft"></div>

            <!-- Info strip -->
            <table class="info">
                <tr>
                    <td style="width:50%;">
                        <div class="k">Statement Period</div>
                        <div class="v">
                            {{ $report['meta']['from_date'] ?? '-' }} – {{ $report['meta']['to_date'] ?? '-' }}
                        </div>
                    </td>
                    <td style="width:25%;">
                        <div class="k">Currency</div>
                        <div class="v">Bangladeshi Taka (BDT)</div>
                    </td>
                    <td style="width:25%;">
                        <div class="k" style="text-align:right;">Total Clients</div>
                        <div class="v" style="text-align:right;">{{ $report['summary']['total_clients'] ?? 0 }}</div>
                    </td>
                </tr>
            </table>

            <!-- Data table -->
            <table class="table">
                <thead>
                    <tr>
                        @foreach ($report['columns'] as $column)
                            <th
                                style="width:
                @if ($column['key'] === 'client_name') 22%
                @elseif($column['key'] === 'scheduled_count') 9%
                @elseif($column['key'] === 'overdue_count') 9%
                @else 15% @endif
              ; text-align: {{ $column['align'] ?? 'left' }}">
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr @if ($loop->even) class="zebra" @endif>
                            @foreach ($report['columns'] as $column)
                                @if ($column['key'] === 'client_name')
                                    <td style="text-align:left;">
                                        <div class="client-name">{{ $row['client_name'] ?? '-' }}</div>
                                        <div class="client-id">ID: {{ $row['client_display_id'] ?? '-' }}</div>
                                    </td>
                                @elseif(in_array($column['key'], ['total_amount', 'total_paid', 'total_due', 'overdue_amount']))
                                    <td class="num @if ($row[$column['key']] > 0) num-due @else num-zero @endif">
                                        {{ number_format((float) $row[$column['key']], 2) }}
                                    </td>
                                @elseif(in_array($column['key'], ['scheduled_count', 'overdue_count']))
                                    <td class="count">{{ $row[$column['key']] ?? 0 }}</td>
                                @else
                                    <td style="text-align:{{ $column['align'] ?? 'left' }}">
                                        {{ $row[$column['key']] ?? '-' }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:20px;">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Totals band -->
            <div class="totals-h">Statement Summary</div>
            <table class="totals">
                <tr>
                    <td style="width:14%;">
                        <div class="t-k">Total Clients</div>
                        <div class="t-v">{{ $report['summary']['total_clients'] ?? 0 }}</div>
                    </td>
                    <td style="width:15%;">
                        <div class="t-k">Total Amount</div>
                        <div class="t-v">{{ number_format((float) ($report['summary']['total_amount'] ?? 0), 2) }}</div>
                    </td>
                    <td style="width:15%;">
                        <div class="t-k">Paid Amount</div>
                        <div class="t-v">{{ number_format((float) ($report['summary']['total_paid'] ?? 0), 2) }}</div>
                    </td>
                    <td style="width:15%;">
                        <div class="t-k">Overdue Amount</div>
                        <div class="t-v">{{ number_format((float) ($report['summary']['total_overdue_amount'] ?? 0), 2) }}</div>
                    </td>
                    <td style="width:13%;">
                        <div class="t-k">Sched / Overdue</div>
                        <div class="t-v">{{ $report['summary']['total_scheduled_count'] ?? 0 }} / {{ $report['summary']['total_overdue_count'] ?? 0 }}</div>
                    </td>
                    <td style="width:18%;" class="t-emph">
                        <div class="t-k">Total Outstanding</div>
                        <div class="t-v">{{ number_format((float) ($report['summary']['total_outstanding'] ?? 0), 2) }}</div>
                    </td>
                </tr>
            </table>

            @if (!empty($report['meta']['notes']))
                <!-- Notes section -->
                <div class="note">
                    <div class="note-h">Notes</div>
                    {{ $report['meta']['notes'] }}
                </div>
            @endif

            <!-- Footer -->
            <table class="foot">
                <tr>
                    <td style="text-align:left;">{{ config('company.name') }} ·
                        {{ $report['title'] ?? 'All Client Statement' }}</td>
                    <td style="text-align:right;">Generated
                        {{ $report['meta']['generated_at'] ?? now()->format('d M Y, H:i') }}</td>
                </tr>
            </table>

        </div>
    </div>
</body>

</html>
