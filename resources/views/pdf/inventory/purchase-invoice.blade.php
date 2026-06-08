<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #111827; background: #fff; }
        .page { max-width: 780px; margin: 0 auto; padding: 24px 28px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 16px; }
        .header-left { display: table-cell; width: 60%; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
        .company-name { font-size: 18px; font-weight: 700; }
        .doc-title { font-size: 13px; font-weight: 700; color: #374151; margin-top: 2px; letter-spacing: .5px; text-transform: uppercase; }
        .meta-line { font-size: 11px; color: #374151; margin-top: 2px; }

        /* Status badge */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-partial { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dbeafe; color: #1e40af; }
        .badge-pending { background: #f3f4f6; color: #374151; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        /* Two-col info block */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info-table td { border: 1px solid #e5e7eb; padding: 8px 10px; vertical-align: top; width: 50%; }
        .info-section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; margin-bottom: 5px; }
        .info-row { margin-bottom: 2px; }
        .info-label { color: #6b7280; }

        /* Items table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .items-table th { background: #f9fafb; border: 1px solid #e5e7eb; padding: 6px 8px; font-size: 10px; text-transform: uppercase; letter-spacing: .3px; color: #374151; }
        .items-table td { border: 1px solid #e5e7eb; padding: 6px 8px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Totals */
        .totals-wrap { display: table; width: 100%; margin-bottom: 14px; }
        .totals-notes { display: table-cell; width: 55%; vertical-align: top; border: 1px solid #e5e7eb; padding: 8px 10px; }
        .totals-box { display: table-cell; width: 45%; vertical-align: top; }
        .totals-inner { width: 100%; border-collapse: collapse; }
        .totals-inner td { padding: 4px 8px; border: 1px solid #e5e7eb; }
        .totals-inner .grand td { background: #f9fafb; font-weight: 700; border-top: 2px solid #d1d5db; }

        /* Payments */
        .payments-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .payments-table th { background: #f9fafb; border: 1px solid #e5e7eb; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #374151; }
        .payments-table td { border: 1px solid #e5e7eb; padding: 6px 8px; }
        .green { color: #065f46; }

        /* Footer signatures */
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 32px; }
        .sig-table td { width: 33%; padding: 0 8px; text-align: center; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #9ca3af; padding-top: 5px; font-size: 10px; color: #6b7280; }

        .section-title { font-size: 11px; font-weight: 700; margin-bottom: 6px; color: #374151; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 12px 0; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ $companyName }}</div>
            <div class="doc-title">Purchase Invoice</div>
        </div>
        <div class="header-right">
            <div class="meta-line"><strong>Invoice No:</strong> {{ $invoice->invoice_no }}</div>
            <div class="meta-line"><strong>Date:</strong> {{ $invoice->invoice_date?->format('d M, Y') }}</div>
            @if($invoice->due_date)
            <div class="meta-line"><strong>Due Date:</strong> {{ $invoice->due_date->format('d M, Y') }}</div>
            @endif
            <div class="meta-line" style="margin-top:5px;">
                @php
                    $sv = $invoice->status->value;
                    $badgeClass = match($sv) {
                        'paid'           => 'badge-paid',
                        'partially_paid' => 'badge-partial',
                        'approved'       => 'badge-approved',
                        'pending'        => 'badge-pending',
                        'cancelled'      => 'badge-cancelled',
                        default          => 'badge-pending',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $invoice->status->label() }}</span>
            </div>
        </div>
    </div>

    {{-- Supplier + Invoice Info --}}
    <table class="info-table">
        <tr>
            <td>
                <div class="info-section-title">Supplier</div>
                <div class="info-row"><strong>{{ $invoice->supplier->name }}</strong></div>
                @if($invoice->supplier->company_name)
                <div class="info-row"><span class="info-label">Company:</span> {{ $invoice->supplier->company_name }}</div>
                @endif
                @if($invoice->supplier->phone)
                <div class="info-row"><span class="info-label">Phone:</span> {{ $invoice->supplier->phone }}</div>
                @endif
                @if($invoice->supplier->email)
                <div class="info-row"><span class="info-label">Email:</span> {{ $invoice->supplier->email }}</div>
                @endif
                @if($invoice->supplier->address)
                <div class="info-row"><span class="info-label">Address:</span> {{ $invoice->supplier->address }}</div>
                @endif
            </td>
            <td>
                <div class="info-section-title">Invoice Details</div>
                @if($invoice->purchaseOrder)
                <div class="info-row"><span class="info-label">PO Ref:</span> {{ $invoice->purchaseOrder->po_no }}</div>
                @endif
                @if($invoice->supplier_invoice_no)
                <div class="info-row"><span class="info-label">Supplier Inv No:</span> {{ $invoice->supplier_invoice_no }}</div>
                @endif
                <div class="info-row"><span class="info-label">Created by:</span> {{ $invoice->creator?->name ?? '—' }}</div>
                @if($invoice->approver)
                <div class="info-row"><span class="info-label">Approved by:</span> {{ $invoice->approver->name }}
                    @if($invoice->confirmed_at) on {{ $invoice->confirmed_at->format('d M, Y') }}@endif
                </div>
                @endif
                @if($invoice->remarks)
                <div class="info-row" style="margin-top:4px;"><span class="info-label">Remarks:</span> {{ $invoice->remarks }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Line Items --}}
    <div class="section-title">Items</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;" class="text-center">#</th>
                <th style="width:38%; text-align:left;">Product</th>
                <th style="width:12%;" class="text-center">Qty</th>
                <th style="width:13%;" class="text-right">Unit Price</th>
                <th style="width:13%;" class="text-right">Discount</th>
                <th style="width:14%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoice->items as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $item->product?->name ?? '—' }}</td>
                <td class="text-right">{{ number_format((float)$item->qty, 2) }}</td>
                <td class="text-right">{{ number_format((float)$item->unit_price, 2) }}</td>
                <td class="text-right">{{ (float)$item->discount_amount > 0 ? number_format((float)$item->discount_amount, 2) : '—' }}</td>
                <td class="text-right">{{ number_format((float)$item->total_amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center" style="padding:12px; color:#9ca3af;">No items.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals + Notes --}}
    <div class="totals-wrap">
        <div class="totals-notes">
            <div class="info-section-title">Notes / Remarks</div>
            <div style="color:#374151;">{{ $invoice->remarks ?: '—' }}</div>
        </div>
        <div class="totals-box">
            <table class="totals-inner">
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">৳ {{ number_format((float)$invoice->subtotal, 2) }}</td>
                </tr>
                @if((float)$invoice->discount_amount > 0)
                <tr>
                    <td>Discount</td>
                    <td class="text-right">- ৳ {{ number_format((float)$invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if((float)$invoice->shipping_amount > 0)
                <tr>
                    <td>Shipping</td>
                    <td class="text-right">+ ৳ {{ number_format((float)$invoice->shipping_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="grand">
                    <td>Total</td>
                    <td class="text-right">৳ {{ number_format((float)$invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="green">Paid</td>
                    <td class="text-right green">৳ {{ number_format((float)$invoice->paid_amount, 2) }}</td>
                </tr>
                <tr>
                    <td style="color:#92400e; font-weight:600;">Balance Due</td>
                    <td class="text-right" style="color:#92400e; font-weight:600;">৳ {{ number_format((float)$invoice->due_amount, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Payment History --}}
    @if($invoice->bankingPaymentRequests->isNotEmpty())
    <hr class="divider">
    <div class="section-title">Payment History</div>
    <table class="payments-table">
        <thead>
            <tr>
                <th style="text-align:left;">Request No</th>
                <th style="text-align:left;">Date</th>
                <th style="text-align:left;">Bank</th>
                <th style="text-align:left;">By</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->bankingPaymentRequests as $pr)
            <tr>
                <td>{{ $pr->request_no }}</td>
                <td>{{ $pr->payment_date?->format('d M, Y') ?? $pr->completed_at?->format('d M, Y') ?? '—' }}</td>
                <td>{{ $pr->bankAccount?->bank_name ?? '—' }}</td>
                <td>{{ $pr->requestedBy?->name ?? '—' }}</td>
                <td class="text-right green">৳ {{ number_format((float)$pr->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Signatures --}}
    <table class="sig-table">
        <tr>
            <td><div class="sig-line">Prepared By<br>{{ $invoice->creator?->name ?? '' }}</div></td>
            <td><div class="sig-line">Approved By<br>{{ $invoice->approver?->name ?? '' }}</div></td>
            <td><div class="sig-line">Authorized Signature</div></td>
        </tr>
    </table>

</div>
</body>
</html>
