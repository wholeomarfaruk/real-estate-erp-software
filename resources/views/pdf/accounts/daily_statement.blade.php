@php
    $money = static fn (mixed $value): string => number_format((float) $value, 2);
    $expenses = collect($statement->expenses ?? []);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Statement Sheet - {{ $statement->statement_ref ?? 'Daily Statement' }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 20px;
        }

        .statement-wrapper {
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 20px;
        }

        .header h4 {
            margin: 3px 0;
            font-size: 14px;
            font-weight: 600;
        }

        .header p {
            margin: 2px 0;
            font-size: 12px;
        }

        .top-meta {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 11px;
            vertical-align: middle;
        }

        th {
            background: #f1f1f1;
            text-align: center;
            font-weight: 700;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .section-title {
            background: #ddd;
            font-weight: bold;
            text-align: left;
        }

        .signature-area {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 200px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
        }

        .total-row {
            font-weight: bold;
            background: #f7f7f7;
        }

        .small {
            font-size: 10px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="statement-wrapper">

    <!-- Header -->
    <div class="header">
        <h2>Star Unity Development Ltd.</h2>
        <h4>Statement of Daily Accounts Sheet</h4>
        <p>Bashundhara River View</p>
        <p>Dated: {{ optional($statement->statement_date)->format('d-m-Y') }} Day: {{ optional($statement->statement_date)->format('l') }}</p>
    </div>

    <!-- Meta -->
    <div class="top-meta">
        <div>
            <strong>Ref No:</strong> {{ $statement->statement_ref }}
        </div>
        <div>
            <strong>Date:</strong> {{ optional($statement->statement_date)->format('d.m.y') }}
        </div>
    </div>

    <!-- Bank Statement -->
    <table>
        <thead>
        <tr>
            <th>Sl No</th>
            <th>Bank Name</th>
            <th>Chq No</th>
            <th>Open Bal.</th>
            <th>Deposit</th>
            <th>Bank Trans.</th>
            <th>Total Taka</th>
            <th>Withdrawn</th>
            <th>Bank Trans.</th>
            <th>Closing Bal.</th>
        </tr>
        </thead>

        <tbody>
        @forelse($statement->bankDetails as $key => $bank)
            <tr>
                <td class="text-center">{{ $key + 1 }}</td>
                <td>{{ $bank->bankAccount->bank_name ?? '-' }}</td>
                <td>{{ $bank->cheque_no ?: '-' }}</td>
                <td class="text-right">{{ $money($bank->opening_balance ?? 0) }}</td>
                <td class="text-right">{{ $money($bank->deposit ?? 0) }}</td>
                <td class="text-right">{{ $money($bank->bank_transfer_in ?? 0) }}</td>
                <td class="text-right">{{ $money($bank->total_taka ?? 0) }}</td>
                <td class="text-right">{{ $money($bank->withdrawn ?? 0) }}</td>
                <td class="text-right">{{ $money($bank->bank_transfer_out ?? 0) }}</td>
                <td class="text-right">{{ $money($bank->closing_balance ?? 0) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center">No bank transactions found for this date.</td>
            </tr>
        @endforelse

        <tr class="total-row">
            <td colspan="3" class="text-right">Total</td>
            <td class="text-right">{{ $money($bankTotals['total_opening'] ?? 0) }}</td>
            <td class="text-right">{{ $money($bankTotals['total_deposit'] ?? 0) }}</td>
            <td class="text-right">{{ $money($bankTotals['total_transfer_in'] ?? 0) }}</td>
            <td class="text-right">{{ $money((float) ($bankTotals['total_opening'] ?? 0) + (float) ($bankTotals['total_deposit'] ?? 0) + (float) ($bankTotals['total_transfer_in'] ?? 0)) }}</td>
            <td class="text-right">{{ $money($bankTotals['total_withdrawn'] ?? 0) }}</td>
            <td class="text-right">{{ $money($bankTotals['total_transfer_out'] ?? 0) }}</td>
            <td class="text-right">{{ $money($bankTotals['total_closing'] ?? 0) }}</td>
        </tr>
        </tbody>
    </table>

    <!-- Cash / HO Section -->
    <table>
        <thead>
        <tr>
            <th>MR No.</th>
            <th>Particulars</th>
            <th>Open Bal.</th>
            <th>Cash Received</th>
            <th>IOU Ip/Dec</th>
            <th>Bank Trans.</th>
            <th>Total Taka</th>
            <th>Expenses</th>
            <th>Closing Bal.</th>
        </tr>
        </thead>

        <tbody>

        @forelse($statement->cashDetails as $cash)
            <tr>
                <td>{{ $cash->mr_no ?: '-' }}</td>
                <td>{{ $cash->particulars ?: '-' }}</td>
                <td class="text-right">{{ abs((float) ($cash->opening_balance ?? 0)) > 0.004 ? $money($cash->opening_balance) : '' }}</td>
                <td class="text-right">{{ abs((float) ($cash->cash_received ?? 0)) > 0.004 ? $money($cash->cash_received) : '' }}</td>
                <td class="text-right">{{ abs((float) ($cash->iou_received ?? 0)) > 0.004 ? $money($cash->iou_received) : '' }}</td>
                <td class="text-right">{{ abs((float) ($cash->bank_transfer ?? 0)) > 0.004 ? $money($cash->bank_transfer) : '' }}</td>
                <td class="text-right">{{ $money($cash->total_taka ?? 0) }}</td>
                <td class="text-right">{{ abs((float) ($cash->expenses ?? 0)) > 0.004 ? $money($cash->expenses) : '' }}</td>
                <td class="text-right">{{ $money($cash->closing_balance ?? 0) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">No cash/head office transactions found for this date.</td>
            </tr>
        @endforelse

        <tr class="total-row">
            <td colspan="2" class="text-right">Grand Total</td>
            <td class="text-right">{{ $money($cashTotals['grand_total_opening'] ?? 0) }}</td>
            <td class="text-right">{{ $money($cashTotals['grand_total_received'] ?? 0) }}</td>
            <td class="text-right">{{ $money($cashTotals['grand_total_iou'] ?? 0) }}</td>
            <td class="text-right">{{ $money($cashTotals['grand_total_transfer'] ?? 0) }}</td>
            <td class="text-right">{{ $money((float) ($cashTotals['grand_total_opening'] ?? 0) + (float) ($cashTotals['grand_total_received'] ?? 0) + (float) ($cashTotals['grand_total_iou'] ?? 0) + (float) ($cashTotals['grand_total_transfer'] ?? 0)) }}</td>
            <td class="text-right">{{ $money($cashTotals['grand_total_expenses'] ?? 0) }}</td>
            <td class="text-right">{{ $money($cashTotals['grand_total_closing'] ?? 0) }}</td>
        </tr>

        </tbody>
    </table>

    <!-- Expense Section -->
    <table>
        <thead>
        <tr>
            <th>V. No</th>
            <th>Particulars</th>
            <th>Req. No.</th>
            <th>Taka</th>
            <th>Bank Trans.</th>
            <th>Bank Name</th>
        </tr>
        </thead>

        <tbody>

        @forelse($expenses as $expense)
            <tr>
                <td>{{ $expense->voucher_no ?: '-' }}</td>
                <td>{{ $expense->particulars ?: '-' }}</td>
                <td>{{ $expense->req_no ?: '-' }}</td>
                <td class="text-right">{{ abs((float) ($expense->amount ?? 0)) > 0.004 ? $money($expense->amount) : '' }}</td>
                <td class="text-right">{{ abs((float) ($expense->bank_transfer ?? 0)) > 0.004 ? $money($expense->bank_transfer) : '' }}</td>
                <td>{{ $expense->bank_name ?: '-' }}</td>
            </tr>
        @empty
            @for($i = 0; $i < 5; $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        @endforelse

        <!-- Empty Rows for printing -->
        @for($i = 0; $i < max(0, 20 - $expenses->count()); $i++)
            <tr>
                <td>&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endfor

        <tr class="total-row">
            <td colspan="3" class="text-right">IOU Payment</td>
            <td class="text-right">{{ $money($expenseTotals['iou_payment'] ?? 0) }}</td>
            <td></td>
            <td></td>
        </tr>

        <tr class="total-row">
            <td colspan="3" class="text-right">Closing Balance + Bank</td>
            <td class="text-right">{{ $money($expenseTotals['closing_balance'] ?? 0) }}</td>
            <td></td>
            <td></td>
        </tr>

        </tbody>
    </table>

    <!-- Footer Summary -->
    <table>
        <tbody>
        <tr>
            <td class="bold">Closing Balance : Cash HO</td>
            <td class="text-right bold">{{ isset($cashTotals['ho_in_hand']) ? $money($cashTotals['ho_in_hand']->closing_balance) : '0.00' }}</td>
        </tr>

        <tr>
            <td class="bold">Closing Balance : Hand IOU</td>
            <td class="text-right bold">{{ isset($cashTotals['ho_iou']) ? $money($cashTotals['ho_iou']->closing_balance) : '0.00' }}</td>
        </tr>

        <tr>
            <td class="bold">Total Amount</td>
            <td class="text-right bold">{{ $money((float) ($cashTotals['grand_total_closing'] ?? 0) + (float) ($bankTotals['total_closing'] ?? 0)) }}</td>
        </tr>
        </tbody>
    </table>

    <!-- Signature -->
    <div class="signature-area">

        <div class="signature-box">
            <div class="signature-line">
                Prepared By
                @if($statement->prepared_by)
                    <div style="font-size: 10px; margin-top: 5px;">{{ $statement->prepared_by }}</div>
                @endif
            </div>
        </div>

        <div class="signature-box">
            <div class="signature-line">
                Checked By
                @if($statement->checked_by)
                    <div style="font-size: 10px; margin-top: 5px;">{{ $statement->checked_by }}</div>
                @endif
            </div>
        </div>

        <div class="signature-box">
            <div class="signature-line">
                Approved By
                @if($statement->approved_by)
                    <div style="font-size: 10px; margin-top: 5px;">{{ $statement->approved_by }}</div>
                @endif
            </div>
        </div>

    </div>

</div>

@if($showActions ?? true)
    <div class="no-print" style="margin-top: 30px; text-align: center;">
        @if($allowPrint ?? false)
            <button onclick="window.print()" style="padding: 8px 18px; border: 1px solid #111827; background: #111827; color: #fff; border-radius: 6px; cursor: pointer;">Print</button>
        @endif
        @if(! empty($backUrl))
            <button onclick="window.location.href='{{ $backUrl }}'" style="padding: 8px 18px; border: 1px solid #9ca3af; background: #fff; color: #111827; border-radius: 6px; cursor: pointer; margin-left: 8px;">Back</button>
        @endif
    </div>
@endif

</body>
</html>
