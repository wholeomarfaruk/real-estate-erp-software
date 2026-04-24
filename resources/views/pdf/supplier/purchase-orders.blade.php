<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Purchase Orders - {{ $supplier->name }}</title>
</head>
<body style="margin:0; padding:0; background:#ffffff; color:#111827; font-family:'DejaVu Sans', Arial, sans-serif; font-size:11px;">
    <div style="width:100%; max-width:780px; margin:0 auto; padding:14px;">
        <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
            <tr>
                <td style="width:60%; vertical-align:top;">
                    <div style="font-size:18px; font-weight:700;">{{ $companyName ?: 'Company' }}</div>
                    <div style="font-size:12px; color:#4b5563;">Supplier-wise Purchase Orders</div>
                </td>
                <td style="width:40%; vertical-align:top; text-align:right;">
                    <div><strong>Generated:</strong> {{ $generatedAt->format('d M, Y h:i A') }}</div>
                    <div><strong>Total POs:</strong> {{ $purchaseOrders->count() }}</div>
                </td>
            </tr>
        </table>

        <table style="width:100%; border-collapse:collapse; border:1px solid #d1d5db; margin-bottom:12px;">
            <tr>
                <td style="padding:8px; width:16%; background:#f9fafb; border:1px solid #e5e7eb;"><strong>Supplier</strong></td>
                <td style="padding:8px; width:34%; border:1px solid #e5e7eb;">{{ $supplier->name }}</td>
                <td style="padding:8px; width:16%; background:#f9fafb; border:1px solid #e5e7eb;"><strong>Code</strong></td>
                <td style="padding:8px; width:34%; border:1px solid #e5e7eb;">{{ $supplier->code ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding:8px; background:#f9fafb; border:1px solid #e5e7eb;"><strong>Phone</strong></td>
                <td style="padding:8px; border:1px solid #e5e7eb;">{{ $supplier->phone ?: 'N/A' }}</td>
                <td style="padding:8px; background:#f9fafb; border:1px solid #e5e7eb;"><strong>Email</strong></td>
                <td style="padding:8px; border:1px solid #e5e7eb;">{{ $supplier->email ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding:8px; background:#f9fafb; border:1px solid #e5e7eb;"><strong>Address</strong></td>
                <td colspan="3" style="padding:8px; border:1px solid #e5e7eb;">{{ $supplier->address ?: 'N/A' }}</td>
            </tr>
        </table>

        @forelse ($purchaseOrders as $po)
            @php
                $poTotal = (float) $po->items->sum(fn ($item) => (float) $item->estimated_total_price);
            @endphp
            <div style="border:1px solid #d1d5db; margin-bottom:12px;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="padding:8px; width:20%; background:#f9fafb; border:1px solid #e5e7eb;"><strong>PO No</strong></td>
                        <td style="padding:8px; width:30%; border:1px solid #e5e7eb;">{{ $po->po_no }}</td>
                        <td style="padding:8px; width:20%; background:#f9fafb; border:1px solid #e5e7eb;"><strong>PO Date</strong></td>
                        <td style="padding:8px; width:30%; border:1px solid #e5e7eb;">{{ optional($po->order_date)->format('d M, Y') ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px; background:#f9fafb; border:1px solid #e5e7eb;"><strong>Store</strong></td>
                        <td style="padding:8px; border:1px solid #e5e7eb;">{{ $po->store?->name ?? 'N/A' }} ({{ $po->store?->code ?? 'N/A' }})</td>
                        <td style="padding:8px; background:#f9fafb; border:1px solid #e5e7eb;"><strong>Project</strong></td>
                        <td style="padding:8px; border:1px solid #e5e7eb;">{{ $po->store?->project?->name ?? 'N/A' }}</td>
                    </tr>
                </table>

                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:6%;">#</th>
                            <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:42%; text-align:left;">Product</th>
                            <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:14%;">Unit</th>
                            <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:12%;">Qty</th>
                            <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:13%;">Rate</th>
                            <th style="border:1px solid #e5e7eb; background:#f3f4f6; padding:6px; width:13%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($po->items as $index => $item)
                            <tr>
                                <td style="border:1px solid #e5e7eb; padding:6px; text-align:center;">{{ $index + 1 }}</td>
                                <td style="border:1px solid #e5e7eb; padding:6px;">{{ $item->product?->name ?? 'N/A' }}</td>
                                <td style="border:1px solid #e5e7eb; padding:6px; text-align:center;">{{ $item->product?->unit?->code ?? $item->unit ?? 'N/A' }}</td>
                                <td style="border:1px solid #e5e7eb; padding:6px; text-align:right;">{{ number_format((float) $item->quantity, 3) }}</td>
                                <td style="border:1px solid #e5e7eb; padding:6px; text-align:right;">{{ number_format((float) $item->estimated_unit_price, 2) }}</td>
                                <td style="border:1px solid #e5e7eb; padding:6px; text-align:right;">{{ number_format((float) $item->estimated_total_price, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="border:1px solid #e5e7eb; padding:8px; text-align:center;">No item found for this supplier in this PO.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="border:1px solid #e5e7eb; padding:6px; text-align:right; background:#f9fafb;"><strong>PO Total</strong></td>
                            <td style="border:1px solid #e5e7eb; padding:6px; text-align:right; background:#f9fafb;"><strong>{{ number_format($poTotal, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @empty
            <div style="border:1px solid #e5e7eb; padding:10px; text-align:center;">
                No purchase order found for this supplier.
            </div>
        @endforelse
    </div>
</body>
</html>
