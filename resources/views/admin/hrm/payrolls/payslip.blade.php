<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payroll->employee?->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 18px; }
        .sheet { max-width: 900px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px; }
        .row { display: flex; justify-content: space-between; gap: 20px; }
        .muted { color: #6b7280; }
        .title { font-size: 24px; font-weight: 700; margin: 0; }
        .section { margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; font-size: 13px; }
        th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
        .toolbar { text-align: right; margin-bottom: 10px; }
        @media print {
            .toolbar { display: none; }
            .sheet { border: 0; padding: 0; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="toolbar">
            <button type="button" onclick="window.print()">Print</button>
        </div>

        <div class="row">
            <div>
                <p class="title">Payslip</p>
                <p class="muted">{{ \Carbon\Carbon::createFromDate($payroll->year, $payroll->month, 1)->format('F Y') }}</p>
            </div>
            <div style="text-align:right">
                <p><strong>Payroll ID:</strong> #{{ $payroll->id }}</p>
                <p><strong>Date:</strong> {{ optional($payroll->payroll_date)->format('d M, Y') ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="section row">
            <div>
                <p><strong>Employee:</strong> {{ $payroll->employee?->name }}</p>
                <p><strong>Employee ID:</strong> {{ $payroll->employee?->employee_id }}</p>
                <p><strong>Department:</strong> {{ $payroll->employee?->department?->name ?: 'N/A' }}</p>
                <p><strong>Designation:</strong> {{ $payroll->employee?->designation?->name ?: 'N/A' }}</p>
            </div>
            <div style="text-align:right">
                <p><strong>Gross:</strong> {{ number_format((float) $payroll->gross_salary, 2) }}</p>
                <p><strong>Net:</strong> {{ number_format((float) $payroll->net_salary, 2) }}</p>
                <p><strong>Paid:</strong> {{ number_format($paidAmount, 2) }}</p>
                <p><strong>Due:</strong> {{ number_format(max(0, (float) $payroll->net_salary - $paidAmount), 2) }}</p>
            </div>
        </div>

        <div class="section">
            <h3>Earnings & Bonus</h3>
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Label</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($itemsByType['earning'] ?? collect())->merge($itemsByType['bonus'] ?? collect()) as $item)
                        <tr>
                            <td>{{ ucfirst($item->type) }}</td>
                            <td>{{ $item->label }}</td>
                            <td class="text-right">{{ number_format((float) $item->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No earning/bonus items.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3>Deductions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Label</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($itemsByType['deduction'] ?? [] as $item)
                        <tr>
                            <td>{{ $item->label }}</td>
                            <td class="text-right">{{ number_format((float) $item->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">No deductions.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

