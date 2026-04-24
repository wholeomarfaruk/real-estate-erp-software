<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order {{ $purchaseOrder->po_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
        }
        .page {
            max-width: 920px;
            margin: 16px auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 18px;
        }
        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 12px;
        }
        .btn {
            border: 1px solid #d1d5db;
            background: #fff;
            border-radius: 6px;
            padding: 6px 10px;
            color: #374151;
            text-decoration: none;
            font-size: 12px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }
        .muted {
            color: #6b7280;
        }
        .grid {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin: 0 -10px 12px -10px;
        }
        .card {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
        }
        .card h3 {
            margin: 0 0 8px 0;
            font-size: 12px;
        }
        .line {
            margin-bottom: 4px;
            line-height: 1.45;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            text-align: left;
            vertical-align: top;
            font-size: 10px;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        th {
            background: #f9fafb;
            font-size: 10px;
        }
        .text-right { text-align: right; }
        .summary {
            margin-top: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
        }
        .approval {
            margin-top: 10px;
            border: 1px solid #d1fae5;
            background: #ecfdf5;
            border-radius: 8px;
            padding: 10px;
        }
        .sig-wrap {
            margin-top: 18px;
            display: table;
            width: 100%;
        }
        .sig-col {
            display: table-cell;
            width: 33.33%;
            padding-right: 10px;
            vertical-align: top;
        }
        .sig-line {
            margin-top: 36px;
            border-top: 1px solid #9ca3af;
            width: 180px;
            padding-top: 6px;
            color: #4b5563;
        }
        @media print {
            body { background: #fff; }
            .page {
                margin: 0;
                border: 0;
                border-radius: 0;
                max-width: 100%;
                padding: 0;
            }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="page">
        @if (! $isPdf)
            <div class="toolbar">
                <button type="button" class="btn" onclick="window.print()">Print</button>
                <a href="{{ route('admin.inventory.purchase-orders.pdf', $purchaseOrder) }}" class="btn">Download PDF</a>
            </div>
        @endif

        <div class="header">
            <div>
                <h1 class="title">{{ $companyName ?: 'Company' }}</h1>
                <div class="muted">{{ $companyAddress ?: 'Address not configured' }}</div>
                <div class="muted">
                    {{ $companyPhone ?: 'N/A' }}
                    @if ($companyEmail)
                        | {{ $companyEmail }}
                    @endif
                </div>
            </div>
            <div style="text-align: right;">
                <h2 style="margin: 0 0 6px 0; font-size: 14px;">PURCHASE ORDER</h2>
                <div class="line"><strong>PO No:</strong> {{ $purchaseOrder->po_no }}</div>
                <div class="line"><strong>Date:</strong> {{ optional($purchaseOrder->order_date)->format('d M, Y') ?: 'N/A' }}</div>
                <div class="line"><strong>Status:</strong> {{ $purchaseOrder->status?->label() ?? 'N/A' }}</div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>PO Details</h3>
                <div class="line"><strong>Requester:</strong> {{ $purchaseOrder->requester?->name ?? 'N/A' }}</div>
                <div class="line"><strong>Purchase Mode:</strong> {{ $purchaseOrder->purchase_mode?->label() ?? 'N/A' }}</div>
                <div class="line"><strong>Store:</strong> {{ $purchaseOrder->store?->name ?? 'N/A' }}</div>
                <div class="line"><strong>Store Code:</strong> {{ $purchaseOrder->store?->code ?? 'N/A' }}</div>
                <div class="line"><strong>Store Address:</strong> {{ $purchaseOrder->store?->address ?? 'N/A' }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Product</th>
                    <th style="width: 80px;">Qty</th>
                    <th style="width: 100px;">Unit</th>
                    <th>Supplier Details</th>
                    <th class="text-right" style="width: 120px;">Unit Price</th>
                    <th class="text-right" style="width: 120px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchaseOrder->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            {{ $item->product?->name ?? 'N/A' }}<br>
                            <span class="muted">
                                ID: {{ $item->product?->id ?? 'N/A' }}
                                | Code: {{ $item->product?->sku ?? 'N/A' }}
                            </span>
                        </td>
                        <td>{{ number_format((float) $item->quantity, 3) }}</td>
                        <td>{{ $item->unit ?: 'N/A' }}</td>
                        <td>
                            {{ $item->supplier?->name ?? $purchaseOrder->supplier?->name ?? 'N/A' }}<br>
                            <span class="muted">
                                ID: {{ $item->supplier?->id ?? $purchaseOrder->supplier?->id ?? 'N/A' }}
                                | Code: {{ $item->supplier?->code ?? $purchaseOrder->supplier?->code ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="text-right">{{ number_format((float) $item->estimated_unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format((float) $item->estimated_total_price, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center;">No items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary">
            <div><strong>Fund Request:</strong> {{ number_format((float) $purchaseOrder->fund_request_amount, 2) }}</div>
            <div><strong>Approved Amount:</strong> {{ number_format((float) $purchaseOrder->approved_amount, 2) }}</div>
            <div><strong>Actual Purchase Amount:</strong> {{ number_format((float) $purchaseOrder->actual_purchase_amount, 2) }}</div>
            <div><strong>Remarks:</strong> {{ $purchaseOrder->remarks ?: 'N/A' }}</div>
        </div>

        @if (in_array($purchaseOrder->status?->value, ['approved', 'partially_received', 'received', 'completed'], true))
            <div class="approval">
                <strong>Digitally Approved</strong>
                <div class="line">Engineer: {{ $purchaseOrder->engineerApprover?->name ?? 'N/A' }} ({{ optional($purchaseOrder->engineer_approved_at)->format('d M, Y h:i A') ?: 'N/A' }})</div>
                <div class="line">Chairman: {{ $purchaseOrder->chairmanApprover?->name ?? 'N/A' }} ({{ optional($purchaseOrder->chairman_approved_at)->format('d M, Y h:i A') ?: 'N/A' }})</div>
                <div class="line">Accounts: {{ $purchaseOrder->accountsApprover?->name ?? 'N/A' }} ({{ optional($purchaseOrder->accounts_approved_at)->format('d M, Y h:i A') ?: 'N/A' }})</div>
            </div>
        @endif

        <div class="sig-wrap">
            <div class="sig-col">
                <div class="sig-line">
                    <strong>Prepared By</strong><br>
                    {{ $purchaseOrder->requester?->name ?? 'N/A' }}<br>
                    <span class="muted">{{ optional($purchaseOrder->created_at)->format('d M, Y h:i A') ?: 'N/A' }}</span>
                </div>
            </div>
            <div class="sig-col">
                <div class="sig-line">
                    <strong>Checked By (Engineer)</strong><br>
                    {{ $purchaseOrder->engineerApprover?->name ?? 'Pending' }}<br>
                    <span class="muted">{{ optional($purchaseOrder->engineer_approved_at)->format('d M, Y h:i A') ?: '-' }}</span>
                </div>
            </div>
            <div class="sig-col">
                <div class="sig-line">
                    <strong>Authorized By (Chairman)</strong><br>
                    {{ $purchaseOrder->chairmanApprover?->name ?? 'Pending' }}<br>
                    <span class="muted">{{ optional($purchaseOrder->chairman_approved_at)->format('d M, Y h:i A') ?: '-' }}</span>
                </div>
                <div class="sig-line" style="margin-top: 18px;">
                    <strong>Checked By (Accounts)</strong><br>
                    {{ $purchaseOrder->accountsApprover?->name ?? 'Pending' }}<br>
                    <span class="muted">{{ optional($purchaseOrder->accounts_approved_at)->format('d M, Y h:i A') ?: '-' }}</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
