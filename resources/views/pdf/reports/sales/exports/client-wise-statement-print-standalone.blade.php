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
            width: 277mm;
            min-height: 190mm;
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

        /* Customer strip */
        .cust-h {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            margin: 0 0 7px;
        }

        .cust {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            border: 1px solid var(--rule);
        }

        .cust td {
            padding: 8px 12px;
            vertical-align: top;
            border-right: 1px solid var(--rule);
            border-bottom: 1px solid var(--rule);
        }

        .cust td:last-child {
            border-right: 0;
        }

        .cust tr:last-child td {
            border-bottom: 0;
        }

        .cust .k {
            color: var(--muted-2);
            font-size: 8px;
            font-weight: bold;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .cust .v {
            color: var(--ink);
            font-size: 10.5px;
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

        .chip {
            font-size: 10px;
            color: var(--ink);
            font-weight: bold;
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
            font-size: 12.5px;
            font-weight: bold;
            color: var(--ink);
            margin-top: 5px;
            font-family: "DejaVu Sans Mono", monospace;
        }

        .totals .t-v.small {
            font-size: 10px;
        }

        .totals td.t-emph {
            background: var(--zebra);
            border: 2px solid var(--ink);
        }

        .totals td.t-emph .t-k {
            color: var(--muted);
        }

        .totals td.t-emph .t-v {
            color: var(--ink);
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
            position: absolute;
            width: calc(100% - 26mm);
            left: 13mm;
            right: 13mm;
            bottom: 8mm;
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
                        <table style="border-collapse:collapse;">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <div class="co-name">{{ $report['meta']['company_name'] }}</div>
                                    <div class="co-addr">
                                        {{ config('company.address') }}<br>{{ config('company.phone') }} ·
                                        {{ config('company.email') }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width:36%; text-align:right;">
                        <span class="doc-tag">Statement</span>
                        <div class="doc-title">{{ $report['title'] ?? 'Client Wise Statement' }}</div>
                        <div class="doc-meta">
                            <b>Generated:</b> {{ $report['meta']['generated_at'] ?? now()->format('d M Y, H:i') }}<br>
                            <b>Generated by:</b> {{ $report['meta']['generated_by'] ?? 'System' }}
                        </div>
                    </td>
                </tr>
            </table>

            <div class="divider"></div>
            <div class="divider-soft"></div>

            @php($customer = $report['customer'] ?? null)

            <!-- Customer strip -->
            @if ($customer)
                <div class="cust-h">Client Details</div>
                <table class="cust">
                    <tr>
                        <td style="width:34%;">
                            <div class="k">Client</div>
                            <div class="v">{{ $customer['name'] ?? '-' }}</div>
                        </td>
                        <td style="width:22%;">
                            <div class="k">Client ID</div>
                            <div class="v">{{ $customer['code'] ?? '-' }}</div>
                        </td>
                        <td style="width:22%;">
                            <div class="k">Type</div>
                            <div class="v" style="text-transform:capitalize;">{{ $customer['type'] ?? '-' }}</div>
                        </td>
                        <td style="width:22%;">
                            <div class="k">Phone</div>
                            <div class="v">{{ $customer['phone'] ?? '-' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="k">Email</div>
                            <div class="v">{{ $customer['email'] ?? '-' }}</div>
                        </td>
                        <td colspan="3">
                            <div class="k">Address</div>
                            <div class="v">{{ $customer['address'] ?? '-' }}</div>
                        </td>
                    </tr>
                </table>
            @endif

            <!-- Info strip -->
            <table class="info">
                <tr>
                    <td style="width:40%;">
                        <div class="k">Statement Period</div>
                        <div class="v">
                            {{ $report['meta']['from_date'] ?? '-' }} – {{ $report['meta']['to_date'] ?? '-' }}
                        </div>
                    </td>
                  
                    <td style="width:20%;">
                        <div class="k" style="text-align:right;">Properties</div>
                        <div class="v" style="text-align:right;">{{ $report['summary']['total_transactions'] ?? 0 }}</div>
                    </td>
                    <td style="width:20%;">
                        <div class="k" style="text-align:right;">Outstanding</div>
                        <div class="v" style="text-align:right;">{{ number_format((float) ($report['summary']['total_outstanding'] ?? 0), 2) }}</div>
                    </td>
                </tr>
            </table>

            <!-- Data table -->
            <table class="table">
                <thead>
                    <tr>
                        @foreach ($report['columns'] as $column)
                            <th style="text-align: {{ $column['align'] ?? 'left' }}">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr @if ($loop->even) class="zebra" @endif>
                            @foreach ($report['columns'] as $column)
                                @if (in_array($column['key'], ['amount', 'total_paid', 'total_due']))
                                    <td class="num @if (($row[$column['key']] ?? 0) > 0) num-due @else num-zero @endif">
                                        {{ number_format((float) ($row[$column['key']] ?? 0), 2) }}
                                    </td>
                                @else
                                    <td style="text-align:{{ $column['align'] ?? 'left' }}">
                                        {{ $row[$column['key']] ?? '-' }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($report['columns']) }}" style="text-align:center; padding:20px;">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Totals band -->
            <div class="totals-h">Statement Summary</div>
            <table class="totals">
                <tr>
                    <td style="width:13%;">
                        <div class="t-k">Properties</div>
                        <div class="t-v">{{ $report['summary']['total_transactions'] ?? 0 }}</div>
                    </td>
                    <td style="width:15%;">
                        <div class="t-k">Sale Amount</div>
                        <div class="t-v small">{{ number_format((float) ($report['summary']['total_sale_amount'] ?? 0), 2) }}</div>
                    </td>
                    <td style="width:15%;">
                        <div class="t-k">Rent Amount</div>
                        <div class="t-v small">{{ number_format((float) ($report['summary']['total_rent_amount'] ?? 0), 2) }}</div>
                    </td>
                    <td style="width:15%;">
                        <div class="t-k">Total Paid</div>
                        <div class="t-v small">{{ number_format((float) ($report['summary']['total_paid'] ?? 0), 2) }}</div>
                    </td>
                    <td style="width:12%;">
                        <div class="t-k">Scheduled</div>
                        <div class="t-v">{{ $report['summary']['total_scheduled'] ?? 0 }}</div>
                    </td>
                    <td style="width:10%;">
                        <div class="t-k">Overdue</div>
                        <div class="t-v">{{ $report['summary']['total_overdue'] ?? 0 }}</div>
                    </td>
                    <td style="width:20%;" class="t-emph">
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
                        {{ $report['title'] ?? 'Client Wise Statement' }}</td>
                    <td style="text-align:right;">Generated
                        {{ $report['meta']['generated_at'] ?? now()->format('d M Y, H:i') }}</td>
                </tr>
            </table>

        </div>
    </div>
</body>

</html>
