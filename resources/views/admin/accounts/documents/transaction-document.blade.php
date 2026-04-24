<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documentTitle }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            color: #111827;
            margin: 0;
            padding: 0;
            background: #f9fafb;
        }
        .page {
            max-width: 900px;
            margin: 18px auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
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
            align-items: flex-start;
            margin-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }
        .meta {
            font-size: 12px;
            color: #4b5563;
            line-height: 1.7;
            text-align: right;
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
            font-size: 13px;
            color: #374151;
        }
        .row {
            margin-bottom: 4px;
        }
        .row span {
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            font-size: 12px;
            color: #374151;
        }
        .text-right { text-align: right; }
        .summary {
            margin-top: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
        }
        .notes {
            margin-top: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            white-space: pre-wrap;
        }
        .foot {
            margin-top: 20px;
            display: table;
            width: 100%;
        }
        .foot > div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .signature {
            margin-top: 40px;
            border-top: 1px solid #9ca3af;
            width: 220px;
            padding-top: 6px;
            font-size: 12px;
            color: #4b5563;
        }
        @media print {
            body { background: #fff; }
            .page {
                margin: 0;
                border: none;
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
                <a href="{{ url()->previous() }}" class="btn">Back</a>
            </div>
        @endif

        <div class="header">
            <div>
                <p class="title">{{ $documentTitle }}</p>
                <p style="margin: 6px 0 0 0; color: #6b7280;">Generated on {{ now()->format('d M, Y h:i A') }}</p>
            </div>
            <div class="meta">
                <div><strong>Transaction:</strong> #{{ $transaction?->id ?? 'N/A' }}</div>
                <div><strong>Date:</strong> {{ optional($transaction?->date)->format('d M, Y') ?? 'N/A' }}</div>
                <div><strong>Type:</strong> {{ $transaction?->type?->label() ?? 'N/A' }}</div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Document Details</h3>
                @if ($documentType === 'payment')
                    <div class="row"><span>Payment No:</span> {{ $document->payment_no ?: 'Auto' }}</div>
                    <div class="row"><span>Method:</span> {{ $document->method?->label() ?? 'N/A' }}</div>
                    <div class="row"><span>Payee:</span> {{ $document->payee_name ?: 'N/A' }}</div>
                    <div class="row"><span>Payment Account:</span> {{ $document->paymentAccount?->name ?? 'N/A' }}</div>
                    <div class="row"><span>Purpose Account:</span> {{ $document->purposeAccount?->name ?? 'N/A' }}</div>
                @elseif ($documentType === 'collection')
                    <div class="row"><span>Collection No:</span> {{ $document->collection_no ?: 'Auto' }}</div>
                    <div class="row"><span>Method:</span> {{ $document->method?->label() ?? 'N/A' }}</div>
                    <div class="row"><span>Payer:</span> {{ $document->payer_name ?: 'N/A' }}</div>
                    <div class="row"><span>Collection Type:</span> {{ $document->collection_type?->label() ?? 'N/A' }}</div>
                    <div class="row"><span>Collection Account:</span> {{ $document->collectionAccount?->name ?? 'N/A' }}</div>
                    <div class="row"><span>Target Account:</span> {{ $document->targetAccount?->name ?? 'N/A' }}</div>
                @else
                    <div class="row"><span>Expense No:</span> {{ $document->expense_no ?: 'Auto' }}</div>
                    <div class="row"><span>Title:</span> {{ $document->title ?: 'N/A' }}</div>
                    <div class="row"><span>Expense Account:</span> {{ $document->expenseAccount?->name ?? 'N/A' }}</div>
                    <div class="row"><span>Payment Account:</span> {{ $document->paymentAccount?->name ?? 'N/A' }}</div>
                @endif
                <div class="row"><span>Amount:</span> {{ number_format((float) ($document->amount ?? 0), 2) }}</div>
            </div>

            <div class="card">
                <h3>Reference & Audit</h3>
                <div class="row"><span>Reference:</span> {{ $document->reference_type ? $document->reference_type.'#'.$document->reference_id : 'N/A' }}</div>
                <div class="row"><span>Created By:</span> {{ $document->creator?->name ?? 'N/A' }}</div>
                <div class="row"><span>Created At:</span> {{ optional($document->created_at)->format('d M, Y h:i A') ?? 'N/A' }}</div>
                <div class="row"><span>Transaction Notes:</span> {{ $transaction?->notes ?: 'N/A' }}</div>
                <div class="row"><span>Attachments:</span> {{ $transaction?->attachments?->count() ?? 0 }}</div>
            </div>
        </div>

        <h3 style="margin: 12px 0 0 0; font-size: 14px;">Transaction Lines</h3>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Description</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaction?->lines ?? [] as $line)
                    <tr>
                        <td>{{ $line->account?->name ?? 'N/A' }} ({{ $line->account?->code ?: 'No code' }})</td>
                        <td>{{ $line->description ?: 'N/A' }}</td>
                        <td class="text-right">{{ number_format((float) $line->debit, 3) }}</td>
                        <td class="text-right">{{ number_format((float) $line->credit, 3) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No transaction lines found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary">
            <strong>Total Debit:</strong> {{ number_format((float) ($transaction?->total_debit ?? 0), 3) }}
            &nbsp; | &nbsp;
            <strong>Total Credit:</strong> {{ number_format((float) ($transaction?->total_credit ?? 0), 3) }}
        </div>

        <div class="notes">
            <strong>Notes:</strong><br>
            {{ $document->notes ?: 'No notes provided.' }}
        </div>

        <div class="foot">
            <div>
                <div class="signature">Prepared By</div>
            </div>
            <div style="text-align: right;">
                <div style="margin-left: auto;" class="signature">Authorized Signature</div>
            </div>
        </div>
    </div>
</body>
</html>

