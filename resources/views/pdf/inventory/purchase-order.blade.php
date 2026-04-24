<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order {{ $purchaseOrder->po_no }}</title>
</head>
<body style="margin:0; padding:0; color:#111827; background:#ffffff; font-family:'DejaVu Sans', Arial, sans-serif; font-size:11px;">
    <div style="max-width:780px; margin:0 auto; padding:16px;">
        <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
            <tr>
                <td style="width:60%; vertical-align:top;">
                    <div style="font-size:18px; font-weight:700;">{{ $companyName ?: 'Company' }}</div>
                    <div style="font-size:12px; color:#4b5563;">Purchase Order</div>
                </td>
                <td style="width:40%; vertical-align:top; text-align:right;">
                    <div><strong>PO No:</strong> {{ $purchaseOrder->po_no }}</div>
                    <div><strong>Date:</strong> {{ optional($purchaseOrder->order_date)->format('d M, Y') ?: 'N/A' }}</div>
                    <div><strong>Status:</strong> {{ $purchaseOrder->status?->label() ?? 'N/A' }}</div>
                </td>
            </tr>
        </table>

        <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
            <tr>
                <td style="width:50%; border:1px solid #e5e7eb; padding:8px; vertical-align:top;">
                    <div style="font-size:12px; font-weight:700; margin-bottom:6px;">PO Details</div>
                    <div><strong>Store:</strong> {{ $purchaseOrder->store?->name ?? 'N/A' }}</div>
                    <div><strong>Store Code:</strong> {{ $purchaseOrder->store?->code ?? 'N/A' }}</div>
                    <div><strong>Project:</strong> {{ $purchaseOrder->store?->project?->name ?? 'N/A' }}</div>
                </td>
                <td style="width:50%; border:1px solid #e5e7eb; padding:8px; vertical-align:top;">
                    <div style="font-size:12px; font-weight:700; margin-bottom:6px;">Supplier Info</div>
                    <div><strong>Name:</strong> {{ $purchaseOrder->supplier?->name ?? 'N/A' }}</div>
                    <div><strong>Phone:</strong> {{ $purchaseOrder->supplier?->phone ?? 'N/A' }}</div>
                    <div><strong>Email:</strong> {{ $purchaseOrder->supplier?->email ?? 'N/A' }}</div>
                    <div><strong>Address:</strong> {{ $purchaseOrder->supplier?->address ?? 'N/A' }}</div>
                </td>
            </tr>
        </table>

        <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
            <thead>
                <tr>
                    <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:7%;">#</th>
                    <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; text-align:left; width:31%;">Product</th>
                    <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; text-align:left; width:18%;">Remarks</th>
                    <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:12%;">Qty</th>
                    <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:10%;">Unit</th>
                    <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:11%;">Rate</th>
                    <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:11%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchaseOrder->items as $index => $item)
                    <tr>
                        <td style="border:1px solid #e5e7eb; padding:6px; text-align:center;">{{ $index + 1 }}</td>
                        <td style="border:1px solid #e5e7eb; padding:6px;">{{ $item->product?->name ?? 'N/A' }}</td>
                        <td style="border:1px solid #e5e7eb; padding:6px;">{{ $item->remarks ?: 'N/A' }}</td>
                        <td style="border:1px solid #e5e7eb; padding:6px; text-align:right;">{{ number_format((float) $item->quantity, 3) }}</td>
                        <td style="border:1px solid #e5e7eb; padding:6px; text-align:center;">{{ $item->product?->unit?->code ?? $item->unit ?? 'N/A' }}</td>
                        <td style="border:1px solid #e5e7eb; padding:6px; text-align:right;">{{ number_format((float) $item->estimated_unit_price, 2) }}</td>
                        <td style="border:1px solid #e5e7eb; padding:6px; text-align:right;">{{ number_format((float) $item->estimated_total_price, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="border:1px solid #e5e7eb; padding:8px; text-align:center;">No items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
            <tr>
                <td style="width:60%; border:1px solid #e5e7eb; padding:8px; vertical-align:top;">
                    <div style="font-size:12px; font-weight:700; margin-bottom:6px;">Notes</div>
                    <div>{{ $purchaseOrder->remarks ?: 'N/A' }}</div>
                </td>
                <td style="width:40%; border:1px solid #e5e7eb; padding:8px; vertical-align:top;">
                    <table style="width:100%; border-collapse:collapse;">
                        <tr>
                            <td style="padding:4px 0;"><strong>Subtotal</strong></td>
                            <td style="padding:4px 0; text-align:right;">{{ number_format((float) $subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;"><strong>Discount</strong></td>
                            <td style="padding:4px 0; text-align:right;">{{ number_format((float) $discount, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0; border-top:1px solid #d1d5db;"><strong>Total</strong></td>
                            <td style="padding:6px 0; border-top:1px solid #d1d5db; text-align:right;"><strong>{{ number_format((float) $total, 2) }}</strong></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table style="width:100%; border-collapse:collapse; margin-top:28px;">
            <tr>
                <td style="width:50%; padding-right:8px;">
                    <div style="border-top:1px solid #9ca3af; padding-top:6px; width:220px;">Prepared By</div>
                </td>
                <td style="width:50%; text-align:right;">
                    <div style="border-top:1px solid #9ca3af; padding-top:6px; width:220px; margin-left:auto;">Authorized Signature</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
