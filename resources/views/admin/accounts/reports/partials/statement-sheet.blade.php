@php
    $bankRows = $report['bank_rows'] ?? [];
    $cashRows = $report['cash_rows'] ?? [];
    $expenseRows = $report['expense_rows'] ?? [];
    $totals = $report['totals'] ?? [];
    $meta = $report['meta'] ?? [];

    $bankTotals = [
        'opening' => collect($bankRows)->sum('opening_balance'),
        'deposit' => collect($bankRows)->sum('deposit'),
        'transfer_in' => collect($bankRows)->sum('bank_transfer_in'),
        'total' => collect($bankRows)->sum('total_taka'),
        'withdrawn' => collect($bankRows)->sum('withdrawn'),
        'transfer_out' => collect($bankRows)->sum('bank_transfer_out'),
        'closing' => collect($bankRows)->sum('closing_balance'),
    ];

    $cashOpening = $cashRows !== []
        ? (float) data_get($cashRows, '0.opening_balance', 0)
        : (float) ($totals['closing_cash'] ?? 0);

    $cashTotals = [
        'opening' => $cashOpening,
        'received' => collect($cashRows)->sum('cash_received'),
        'iou' => collect($cashRows)->sum('iou_adjustment'),
        'bank_transfer' => collect($cashRows)->sum('bank_transfer'),
        'total' => $cashRows !== []
            ? (float) data_get(last($cashRows), 'total_taka', $cashOpening)
            : $cashOpening,
        'expenses' => collect($cashRows)->sum('expenses'),
        'closing' => (float) ($totals['closing_cash'] ?? 0),
    ];

    $expenseTotals = [
        'taka' => collect($expenseRows)->sum('taka'),
        'bank_transfer' => collect($expenseRows)->sum('bank_transfer'),
    ];

    $formatSigned = static function (float $amount): string {
        if (abs($amount) < 0.005) {
            return '-';
        }

        return $amount > 0
            ? '+'.number_format($amount, 2)
            : number_format($amount, 2);
    };
@endphp

<div class="space-y-6">
    <div class="statement-sheet-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-400">Accounts Reports</p>
                <h2 class="mt-2 text-xl font-bold text-gray-900">{{ $meta['statement_title'] ?? 'Statement Sheet' }}</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Period: {{ $meta['period_label'] ?? '-' }}
                    @if (! empty($meta['bank_account_name']))
                        <span class="mx-2 text-gray-300">|</span>
                        Bank: {{ $meta['bank_account_name'] }}
                    @endif
                </p>
            </div>

            <div class="min-w-[220px] rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                <p><span class="font-medium text-gray-700">Generated:</span> {{ now()->format('d M Y h:i A') }}</p>
                <p class="mt-1"><span class="font-medium text-gray-700">Report Type:</span> {{ ucfirst((string) ($meta['statement_type'] ?? 'custom')) }}</p>
            </div>
        </div>

        @if (! ($meta['has_transactions'] ?? false))
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                No transactions were found in the selected date range. Existing opening and closing balances are still shown from the current ledger.
            </div>
        @endif

        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-slate-500">Bank Balance</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format((float) ($totals['closing_bank'] ?? 0), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-emerald-600">Cash HO</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ number_format((float) ($totals['closing_cash'] ?? 0), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-amber-600">Hand IOU</p>
                <p class="mt-2 text-2xl font-semibold text-amber-700">{{ number_format((float) ($totals['closing_iou'] ?? 0), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-indigo-600">Total Amount</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-700">{{ number_format((float) ($totals['total_amount'] ?? 0), 2) }}</p>
            </div>
        </div>
    </div>

    <section class="statement-sheet-card rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">A. Bank Summary</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="statement-table min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">SL</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Bank Name</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Opening Balance</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Deposit</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Bank Transfer In</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Total Taka</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Withdrawn</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Bank Transfer Out</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Closing Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($bankRows as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row['sl'] }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $row['bank_name'] }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row['opening_balance'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-emerald-700">{{ number_format((float) $row['deposit'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-emerald-700">{{ number_format((float) $row['bank_transfer_in'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800">{{ number_format((float) $row['total_taka'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-rose-600">{{ number_format((float) $row['withdrawn'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-rose-600">{{ number_format((float) $row['bank_transfer_out'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format((float) $row['closing_balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-500">
                                No bank transactions found for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-gray-200 bg-gray-50">
                    <tr>
                        <th colspan="2" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $bankTotals['opening'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $bankTotals['deposit'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $bankTotals['transfer_in'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $bankTotals['total'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $bankTotals['withdrawn'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $bankTotals['transfer_out'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-900">{{ number_format((float) $bankTotals['closing'], 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="statement-sheet-card rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">B. Cash / HO Summary</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="statement-table min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">MR No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Particulars</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Opening Balance</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Cash Received</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">IOU Increase/Decrease</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Bank Transfer</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Total Taka</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Expenses</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Closing Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cashRows as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $row['mr_no'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <p>{{ $row['particulars'] }}</p>
                                @if (! empty($row['date_label']))
                                    <p class="mt-1 text-xs text-gray-500">{{ $row['date_label'] }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row['opening_balance'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-emerald-700">{{ number_format((float) $row['cash_received'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm {{ (float) $row['iou_adjustment'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $formatSigned((float) $row['iou_adjustment']) }}</td>
                            <td class="px-4 py-3 text-right text-sm {{ (float) $row['bank_transfer'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $formatSigned((float) $row['bank_transfer']) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-800">{{ number_format((float) $row['total_taka'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-rose-600">{{ number_format((float) $row['expenses'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ number_format((float) $row['closing_balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-500">
                                No cash or head-office transactions found for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-gray-200 bg-gray-50">
                    <tr>
                        <th colspan="2" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $cashTotals['opening'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $cashTotals['received'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ $formatSigned((float) $cashTotals['iou']) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ $formatSigned((float) $cashTotals['bank_transfer']) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $cashTotals['total'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $cashTotals['expenses'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-900">{{ number_format((float) $cashTotals['closing'], 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="statement-sheet-card rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">C. Expense / Voucher</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="statement-table min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">V No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Particulars</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Req No</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Taka</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500">Bank Transfer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Bank Name</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($expenseRows as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $row['voucher_no'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <p>{{ $row['particulars'] }}</p>
                                @if (! empty($row['notes']))
                                    <p class="mt-1 text-xs text-gray-500">{{ $row['notes'] }}</p>
                                @elseif (! empty($row['date_label']))
                                    <p class="mt-1 text-xs text-gray-500">{{ $row['date_label'] }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row['req_no'] }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row['taka'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row['bank_transfer'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row['bank_name'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                No expense vouchers found for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-gray-200 bg-gray-50">
                    <tr>
                        <th colspan="3" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $expenseTotals['taka'], 2) }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">{{ number_format((float) $expenseTotals['bank_transfer'], 2) }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">-</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="statement-sheet-card rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">D. Footer Summary</h3>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">Closing Balance: Bank</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ number_format((float) ($totals['closing_bank'] ?? 0), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">Closing Balance: Cash HO</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ number_format((float) ($totals['closing_cash'] ?? 0), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">Closing Balance: Hand IOU</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ number_format((float) ($totals['closing_iou'] ?? 0), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3">
                <p class="text-xs uppercase tracking-wide text-indigo-600">Total Amount</p>
                <p class="mt-2 text-lg font-semibold text-indigo-700">{{ number_format((float) ($totals['total_amount'] ?? 0), 2) }}</p>
            </div>
        </div>
    </section>
</div>
