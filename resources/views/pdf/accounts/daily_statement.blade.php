@php
    $money   = static fn (mixed $v): string => number_format((float) $v, 2);
    $expenses = collect($statement->expenses ?? []);
    $incomes  = collect($statement->incomes  ?? []);

    // Totals
    $totalIncomeCash  = $incomeTotals['total_cash']          ?? 0;
    $totalIncomeBank  = $incomeTotals['total_bank_transfer'] ?? 0;
    $totalIncomeGrand = $incomeTotals['grand_total']         ?? 0;

    $totalExpCash  = $expenseTotals['total_cash']          ?? 0;
    $totalExpBank  = $expenseTotals['total_bank_transfer'] ?? 0;
    $totalExpGrand = (float)($expenseTotals['total_cash'] ?? 0) + (float)($expenseTotals['total_bank_transfer'] ?? 0);

    $closingCashHO   = isset($cashTotals['ho_in_hand'])  ? (float)$cashTotals['ho_in_hand']->closing_balance   : 0;
    $closingHandIOU  = isset($cashTotals['ho_iou'])      ? (float)$cashTotals['ho_iou']->closing_balance       : 0;
    $closingBank     = (float)($bankTotals['total_closing'] ?? 0);
    $totalClosingAll = $closingCashHO + $closingHandIOU + $closingBank;

    // Pad expense rows to minimum 20 rows for print
    $expPad = max(0, 20 - $expenses->count());
    // Pad income rows to minimum 10 rows for print
    $incPad = max(0, 10 - $incomes->count());
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Statement – {{ optional($statement->statement_date)->format('d-m-Y') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            font-size: 10.5px;
            color: #000;
            margin: 14px 18px;
        }

        /* ── Header ─────────────────────────────────────────── */
        .header {
            text-align: center;
            margin-bottom: 6px;
        }
        .header h2 { font-size: 15px; font-weight: 700; }
        .header h4 { font-size: 11px; font-weight: 600; margin-top: 2px; }
        .header p  { font-size: 10px; margin-top: 1px; }

        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            margin-bottom: 5px;
        }

        /* ── Tables ──────────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        th, td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 10px;
            vertical-align: middle;
        }
        th {
            background: #e8e8e8;
            font-weight: 700;
            text-align: center;
        }
        .r { text-align: right; }
        .c { text-align: center; }
        .l { text-align: left; }

        .section-head th {
            background: #c8ddf0;
            font-size: 10.5px;
            letter-spacing: 0.3px;
        }

        .total-row td {
            font-weight: 700;
            background: #f3f3f3;
        }
        .subtotal-row td {
            font-weight: 700;
            background: #fafafa;
        }
        .empty td { color: #fff; }

        /* ── Summary footer table ────────────────────────────── */
        .summary-table {
            width: 260px;
            margin-left: auto;
        }
        .summary-table td {
            padding: 2px 6px;
            border: 1px solid #000;
            font-size: 10px;
        }
        .summary-table .label { font-weight: 600; }
        .summary-table .value { text-align: right; font-weight: 700; }
        .summary-table .grand-row td { background: #e8e8e8; font-weight: 700; }

        /* ── Signature ───────────────────────────────────────── */
        .sig-row {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .sig-box {
            width: 170px;
            text-align: center;
            font-size: 10px;
        }
        .sig-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 3px;
        }

        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

{{-- ═══════════════════════════════════════════════════════ HEADER ══ --}}
<div class="header">
    <h2>Star Unity Development Ltd.</h2>
    <h4>Statement of Daily Accounts Sheet</h4>
    <p>Bashundhara River View</p>
    <p>Dated: {{ optional($statement->statement_date)->format('d-m-Y') }}
       &nbsp; Day: {{ optional($statement->statement_date)->format('l') }}</p>
</div>

<div class="meta-row">
    <span><strong>Ref No:</strong> {{ $statement->statement_ref }}</span>
    <span><strong>Dated:</strong> {{ optional($statement->statement_date)->format('d.m.y') }}</span>
</div>

{{-- ═══════════════════════════════════════════════════ BANK SUMMARY ══ --}}
<table>
    <thead>
        <tr class="section-head">
            <th colspan="10" class="l">Bank Accounts — Opening &amp; Closing Balance</th>
        </tr>
        <tr>
            <th style="width:28px">Sl</th>
            <th>Bank Name</th>
            <th style="width:55px">Chq. No</th>
            <th style="width:72px">Opening Bal.</th>
            <th style="width:72px">Deposit</th>
            <th style="width:72px">Bank Trans. (In)</th>
            <th style="width:72px">Total Taka</th>
            <th style="width:72px">Withdrawn</th>
            <th style="width:72px">Bank Trans. (Out)</th>
            <th style="width:72px">Closing Bal.</th>
        </tr>
    </thead>
    <tbody>
        @forelse($statement->bankDetails as $k => $bank)
        <tr>
            <td class="c">{{ $k + 1 }}</td>
            <td>{{ $bank->bankAccount->bank_name ?? '-' }}</td>
            <td class="c">{{ $bank->cheque_no ?: '-' }}</td>
            <td class="r">{{ $money($bank->opening_balance ?? 0) }}</td>
            <td class="r">{{ abs((float)($bank->deposit ?? 0)) > 0.004 ? $money($bank->deposit) : '' }}</td>
            <td class="r">{{ abs((float)($bank->bank_transfer_in ?? 0)) > 0.004 ? $money($bank->bank_transfer_in) : '' }}</td>
            <td class="r">{{ $money($bank->total_taka ?? 0) }}</td>
            <td class="r">{{ abs((float)($bank->withdrawn ?? 0)) > 0.004 ? $money($bank->withdrawn) : '' }}</td>
            <td class="r">{{ abs((float)($bank->bank_transfer_out ?? 0)) > 0.004 ? $money($bank->bank_transfer_out) : '' }}</td>
            <td class="r"><strong>{{ $money($bank->closing_balance ?? 0) }}</strong></td>
        </tr>
        @empty
        <tr><td colspan="10" class="c" style="color:#777;font-style:italic">No bank accounts found for this date.</td></tr>
        @endforelse

        <tr class="total-row">
            <td colspan="3" class="r">Total</td>
            <td class="r">{{ $money($bankTotals['total_opening']     ?? 0) }}</td>
            <td class="r">{{ $money($bankTotals['total_deposit']     ?? 0) }}</td>
            <td class="r">{{ $money($bankTotals['total_transfer_in'] ?? 0) }}</td>
            <td class="r">{{ $money((float)($bankTotals['total_opening'] ?? 0) + (float)($bankTotals['total_deposit'] ?? 0) + (float)($bankTotals['total_transfer_in'] ?? 0)) }}</td>
            <td class="r">{{ $money($bankTotals['total_withdrawn']    ?? 0) }}</td>
            <td class="r">{{ $money($bankTotals['total_transfer_out'] ?? 0) }}</td>
            <td class="r">{{ $money($bankTotals['total_closing']      ?? 0) }}</td>
        </tr>
    </tbody>
</table>

{{-- ══════════════════════════════════════════ CASH / HO RUNNING BALANCE ══ --}}
<table>
    <thead>
        <tr class="section-head">
            <th colspan="9" class="l">Cash / Head Office — Running Balance</th>
        </tr>
        <tr>
            <th style="width:60px">MR No.</th>
            <th>Particulars</th>
            <th style="width:72px">Open. Bal.</th>
            <th style="width:72px">Cash Received</th>
            <th style="width:72px">IOU Inc/(Decr)</th>
            <th style="width:72px">Bank Trans.</th>
            <th style="width:72px">Total Taka</th>
            <th style="width:72px">Expenses</th>
            <th style="width:72px">Closing Bal.</th>
        </tr>
    </thead>
    <tbody>
        @forelse($statement->cashDetails as $cash)
        <tr>
            <td>{{ $cash->mr_no ?: '-' }}</td>
            <td>{{ $cash->particulars ?: '-' }}</td>
            <td class="r">{{ abs((float)($cash->opening_balance ?? 0)) > 0.004 ? $money($cash->opening_balance) : '' }}</td>
            <td class="r">{{ abs((float)($cash->cash_received  ?? 0)) > 0.004 ? $money($cash->cash_received)   : '' }}</td>
            <td class="r">{{ abs((float)($cash->iou_received   ?? 0)) > 0.004 ? $money($cash->iou_received)    : '' }}</td>
            <td class="r">{{ abs((float)($cash->bank_transfer  ?? 0)) > 0.004 ? $money($cash->bank_transfer)   : '' }}</td>
            <td class="r">{{ $money($cash->total_taka         ?? 0) }}</td>
            <td class="r">{{ abs((float)($cash->expenses      ?? 0)) > 0.004 ? $money($cash->expenses)         : '' }}</td>
            <td class="r"><strong>{{ $money($cash->closing_balance ?? 0) }}</strong></td>
        </tr>
        @empty
        <tr><td colspan="9" class="c" style="color:#777;font-style:italic">No cash transactions found for this date.</td></tr>
        @endforelse

        <tr class="total-row">
            <td colspan="2" class="r">Grand Total</td>
            <td class="r">{{ $money($cashTotals['grand_total_opening']  ?? 0) }}</td>
            <td class="r">{{ $money($cashTotals['grand_total_received'] ?? 0) }}</td>
            <td class="r">{{ $money($cashTotals['grand_total_iou']      ?? 0) }}</td>
            <td class="r">{{ $money($cashTotals['grand_total_transfer'] ?? 0) }}</td>
            <td class="r">{{ $money((float)($cashTotals['grand_total_opening'] ?? 0) + (float)($cashTotals['grand_total_received'] ?? 0) + (float)($cashTotals['grand_total_iou'] ?? 0) + (float)($cashTotals['grand_total_transfer'] ?? 0)) }}</td>
            <td class="r">{{ $money($cashTotals['grand_total_expenses'] ?? 0) }}</td>
            <td class="r">{{ $money($cashTotals['grand_total_closing']  ?? 0) }}</td>
        </tr>
    </tbody>
</table>

{{-- ══════════════════════════════════════════════════ RECEIVE LIST (IN) ══ --}}
<table>
    <thead>
        <tr class="section-head">
            <th colspan="6" class="l">Received / Income (IN) — Cash &amp; Bank Transfer</th>
        </tr>
        <tr>
            <th style="width:65px">Ref. No</th>
            <th>Particulars</th>
            <th style="width:100px">Category</th>
            <th style="width:80px">Cash (Taka)</th>
            <th style="width:80px">Bank Transfer</th>
            <th>Bank Name</th>
        </tr>
    </thead>
    <tbody>
        @forelse($incomes as $income)
        <tr>
            <td class="c">{{ $income->ref_no ?: '-' }}</td>
            <td>{{ $income->particulars ?: '-' }}</td>
            <td>{{ $income->category ?: '-' }}</td>
            <td class="r">{{ abs((float)($income->cash ?? 0)) > 0.004 ? $money($income->cash) : '' }}</td>
            <td class="r">{{ abs((float)($income->bank_transfer ?? 0)) > 0.004 ? $money($income->bank_transfer) : '' }}</td>
            <td>{{ abs((float)($income->bank_transfer ?? 0)) > 0.004 ? ($income->bank_name ?: '-') : '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="c" style="color:#777;font-style:italic">No income transactions for this date.</td></tr>
        @endforelse

        @for($i = 0; $i < $incPad; $i++)
        <tr class="empty"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor

        <tr class="total-row">
            <td colspan="3" class="r">Total Received</td>
            <td class="r">{{ $money($totalIncomeCash) }}</td>
            <td class="r">{{ $money($totalIncomeBank) }}</td>
            <td class="r"><strong>{{ $money($totalIncomeGrand) }}</strong></td>
        </tr>
    </tbody>
</table>

{{-- ══════════════════════════════════════════════════ EXPENSE LIST (OUT) ══ --}}
<table>
    <thead>
        <tr class="section-head">
            <th colspan="6" class="l">Payments / Expenses (OUT) — Cash &amp; Bank Transfer</th>
        </tr>
        <tr>
            <th style="width:65px">V. No</th>
            <th>Particulars</th>
            <th style="width:100px">Req. No / Category</th>
            <th style="width:80px">Cash (Taka)</th>
            <th style="width:80px">Bank Trans.</th>
            <th>Bank Name</th>
        </tr>
    </thead>
    <tbody>
        @forelse($expenses as $expense)
        <tr>
            <td class="c">{{ $expense->voucher_no ?: '-' }}</td>
            <td>{{ $expense->particulars ?: '-' }}</td>
            <td>{{ $expense->req_no ?: '-' }}</td>
            <td class="r">{{ abs((float)($expense->amount ?? 0)) > 0.004 ? $money($expense->amount) : '' }}</td>
            <td class="r">{{ abs((float)($expense->bank_transfer ?? 0)) > 0.004 ? $money($expense->bank_transfer) : '' }}</td>
            <td>{{ abs((float)($expense->bank_transfer ?? 0)) > 0.004 ? ($expense->bank_name ?: '-') : '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="c" style="color:#777;font-style:italic">No expense transactions for this date.</td></tr>
        @endforelse

        @for($i = 0; $i < $expPad; $i++)
        <tr class="empty"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor

        <tr class="subtotal-row">
            <td colspan="3" class="r">IOU Payment</td>
            <td class="r">{{ $money($expenseTotals['iou_payment'] ?? 0) }}</td>
            <td></td><td></td>
        </tr>
        <tr class="total-row">
            <td colspan="3" class="r">Total Payments</td>
            <td class="r">{{ $money($totalExpCash) }}</td>
            <td class="r">{{ $money($totalExpBank) }}</td>
            <td class="r"><strong>{{ $money($totalExpGrand) }}</strong></td>
        </tr>
    </tbody>
</table>

{{-- ══════════════════════════════════════════════════════════ FOOTER ══ --}}
<table class="summary-table">
    <tbody>
        <tr>
            <td class="label">Closing Balance — Cash HO</td>
            <td class="value">{{ $money($closingCashHO) }}</td>
        </tr>
        <tr>
            <td class="label">Closing Balance — Hand IOU</td>
            <td class="value">{{ $money($closingHandIOU) }}</td>
        </tr>
        <tr>
            <td class="label">Closing Balance — Bank</td>
            <td class="value">{{ $money($closingBank) }}</td>
        </tr>
        <tr class="grand-row">
            <td class="label">Total Amount</td>
            <td class="value">{{ $money($totalClosingAll) }}</td>
        </tr>
    </tbody>
</table>

{{-- ══════════════════════════════════════════════════════ SIGNATURE ══ --}}
<div class="sig-row">
    <div class="sig-box">
        <div class="sig-line">
            Prepared By
            @if($statement->prepared_by)
            <div style="font-size:9px;margin-top:3px">{{ $statement->prepared_by }}</div>
            @endif
        </div>
    </div>
    <div class="sig-box">
        <div class="sig-line">
            Checked By
            @if($statement->checked_by)
            <div style="font-size:9px;margin-top:3px">{{ $statement->checked_by }}</div>
            @endif
        </div>
    </div>
    <div class="sig-box">
        <div class="sig-line">
            Approved By
            @if($statement->approved_by)
            <div style="font-size:9px;margin-top:3px">{{ $statement->approved_by }}</div>
            @endif
        </div>
    </div>
</div>

@if($showActions ?? true)
<div class="no-print" style="margin-top:24px;text-align:center">
    @if($allowPrint ?? false)
    <button onclick="window.print()"
        style="padding:7px 18px;border:1px solid #111827;background:#111827;color:#fff;border-radius:6px;cursor:pointer;font-size:12px">
        Print
    </button>
    @endif
    @if(!empty($backUrl))
    <button onclick="window.location.href='{{ $backUrl }}'"
        style="padding:7px 18px;border:1px solid #9ca3af;background:#fff;color:#111827;border-radius:6px;cursor:pointer;margin-left:8px;font-size:12px">
        Back
    </button>
    @endif
</div>
@endif

</body>
</html>
