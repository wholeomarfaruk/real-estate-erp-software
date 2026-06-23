<div>
    <div class="max-w-7xl mx-auto px-6 py-7">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-ink-1">Daily Statement</h1>
                <p class="text-ink-3 mt-1 text-sm">Bank accounts ledger, receipts, and payments for a single day.</p>
            </div>
            <a href="{{ route('admin.reports.index') }}"
               class="inline-flex items-center gap-1.5 text-ink-2 hover:text-ink-1 text-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Reports
            </a>
        </div>

        {{-- Date Selector --}}
        <div class="bg-paper border border-rule rounded-xl p-5 mb-6">
            <div class="flex items-center gap-4 justify-between">
                <div class="flex items-center gap-3">
                    <button wire:click="previousDay()"
                            class="inline-flex items-center justify-center w-10 h-10 rounded hover:bg-ink-5 text-ink-2 hover:text-accent transition">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>

                    <div class="w-48">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-ink-3 mb-2">Select Date</label>
                        <input type="date" wire:model.live="selectedDate" class="input w-full">
                    </div>

                    <button wire:click="nextDay()"
                            class="inline-flex items-center justify-center w-10 h-10 rounded hover:bg-ink-5 text-ink-2 hover:text-accent transition">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>

                <div class="flex gap-2">
                    <a href="{{ $printUrl }}" target="_blank" title="Print"
                       class="btn btn-secondary p-2" aria-label="Print">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        </svg>
                    </a>
                    <a href="{{ $pdfUrl }}" title="PDF" class="btn btn-secondary p-2" aria-label="PDF">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </a>
                    <a href="{{ $excelUrl }}" title="Excel" class="btn btn-secondary p-2" aria-label="Excel">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                            <path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"></path>
                            <line x1="9" y1="7" x2="9" y2="17"></line>
                            <line x1="15" y1="7" x2="15" y2="17"></line>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        @if($report)
            @php
                $totalReceipts = ($report['receipt_totals']['cash'] ?? 0) + ($report['receipt_totals']['bank'] ?? 0);
                $totalPayments = $report['payment_totals']['cash'] ?? 0;
            @endphp

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-paper border border-rule rounded-xl p-4">
                    <div class="text-ink-3 text-xs uppercase tracking-wide mb-1">Opening Balance</div>
                    <div class="text-2xl font-bold text-ink-1">{{ number_format((float)$report['bank_totals']['opening'], 0) }}</div>
                    <div class="text-xs text-ink-3 mt-1">Bank: {{ number_format((float)$report['bank_totals']['opening'], 0) }}</div>
                </div>
                <div class="bg-paper border border-rule rounded-xl p-4">
                    <div class="text-ink-3 text-xs uppercase tracking-wide mb-1">Total Receipts</div>
                    <div class="text-2xl font-bold text-green-600">{{ number_format($totalReceipts, 0) }}</div>
                </div>
                <div class="bg-paper border border-rule rounded-xl p-4">
                    <div class="text-ink-3 text-xs uppercase tracking-wide mb-1">Total Payments</div>
                    <div class="text-2xl font-bold text-red-600">{{ number_format($totalPayments, 0) }}</div>
                </div>
                <div class="bg-paper border border-rule rounded-xl p-4">
                    <div class="text-ink-3 text-xs uppercase tracking-wide mb-1">Closing Balance</div>
                    <div class="text-2xl font-bold text-accent">{{ number_format((float)$report['closing']['cash'] + (float)$report['closing']['bank'], 0) }}</div>
                    <div class="text-xs text-ink-3 mt-1">Cash: {{ number_format((float)$report['closing']['cash'], 0) }}</div>
                </div>
            </div>

            {{-- Bank Accounts Ledger --}}
            <div class="bg-paper border border-rule rounded-xl overflow-hidden mb-6">
                <div class="px-5 py-3.5 border-b border-rule">
                    <h3 class="font-semibold text-ink-1">Bank Accounts Ledger</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-ink-5/60 text-ink-2 border-b border-rule">
                                <th class="px-4 py-3 text-left font-semibold text-xs uppercase tracking-wide">Bank Name</th>
                                <th class="px-4 py-3 text-right font-semibold text-xs uppercase tracking-wide">Opening Balance</th>
                                <th class="px-4 py-3 text-right font-semibold text-xs uppercase tracking-wide">Closing Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['banks'] as $bank)
                                <tr class="border-b border-rule last:border-0 hover:bg-ink-5/20 transition">
                                    <td class="px-4 py-3 text-left">{{ $bank['name'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float)$bank['opening'], 0) }}</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ number_format((float)$bank['closing'], 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-10 text-center text-ink-3">No bank accounts found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Receipts Section --}}
            <div class="bg-paper border border-rule rounded-xl overflow-hidden mb-6">
                <div class="px-5 py-3.5 border-b border-rule flex items-center justify-between">
                    <h3 class="font-semibold text-ink-1">Daily Receipts</h3>
                    <span class="text-xs text-ink-3">{{ count($report['receipts']) }} transaction(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-ink-5/60 text-ink-2 border-b border-rule">
                                <th class="px-4 py-3 text-left font-semibold text-xs uppercase tracking-wide">MR No</th>
                                <th class="px-4 py-3 text-left font-semibold text-xs uppercase tracking-wide">Particulars</th>
                                <th class="px-4 py-3 text-center font-semibold text-xs uppercase tracking-wide">Account</th>
                                <th class="px-4 py-3 text-right font-semibold text-xs uppercase tracking-wide">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['receipts'] as $receipt)
                                <tr class="border-b border-rule last:border-0 hover:bg-ink-5/20 transition">
                                    <td class="px-4 py-3 font-medium text-accent">{{ $receipt['mr_no'] }}</td>
                                    <td class="px-4 py-3">{{ $receipt['particulars'] }}</td>
                                    <td class="px-4 py-3 text-center text-ink-3 text-xs">{{ $receipt['account'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-green-600">{{ number_format((float)($receipt['cash'] + $receipt['bank']), 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-ink-3">No receipts for this date</td>
                                </tr>
                            @endforelse
                            @if(count($report['receipts']) > 0)
                                <tr class="bg-ink-5/40 font-semibold border-t border-rule">
                                    <td colspan="3" class="px-4 py-3 text-right">Total Receipts</td>
                                    <td class="px-4 py-3 text-right text-green-600">{{ number_format($totalReceipts, 0) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Payments Section --}}
            <div class="bg-paper border border-rule rounded-xl overflow-hidden mb-6">
                <div class="px-5 py-3.5 border-b border-rule flex items-center justify-between">
                    <h3 class="font-semibold text-ink-1">Daily Payments</h3>
                    <span class="text-xs text-ink-3">{{ count($report['payments']) }} transaction(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-ink-5/60 text-ink-2 border-b border-rule">
                                <th class="px-4 py-3 text-left font-semibold text-xs uppercase tracking-wide">Account Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-xs uppercase tracking-wide">Particulars</th>
                                <th class="px-4 py-3 text-center font-semibold text-xs uppercase tracking-wide">Payment Method</th>
                                <th class="px-4 py-3 text-right font-semibold text-xs uppercase tracking-wide">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['payments'] as $payment)
                                <tr class="border-b border-rule last:border-0 hover:bg-ink-5/20 transition">
                                    <td class="px-4 py-3">{{ $payment['account'] }}</td>
                                    <td class="px-4 py-3 text-ink-2">{{ $payment['particulars'] }}</td>
                                    <td class="px-4 py-3 text-center text-ink-3 text-xs">Cash</td>
                                    <td class="px-4 py-3 text-right font-medium text-red-600">{{ number_format((float)$payment['cash'], 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-ink-3">No payments for this date</td>
                                </tr>
                            @endforelse
                            @if(count($report['payments']) > 0)
                                <tr class="bg-ink-5/40 font-semibold border-t border-rule">
                                    <td colspan="3" class="px-4 py-3 text-right">Total Payments</td>
                                    <td class="px-4 py-3 text-right text-red-600">{{ number_format($totalPayments, 0) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
