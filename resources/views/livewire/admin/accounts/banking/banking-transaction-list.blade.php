<div x-data x-init="$store.pageName = { name: 'Banking Transactions' }">

    {{-- ── Page header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Banking Transactions</h1>
            <p class="text-sm text-gray-500">Record and view all bank account ledger entries.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="openDepositModal"
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Deposit
            </button>
            <button type="button" wire:click="openWithdrawalModal"
                class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-rose-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Withdrawal
            </button>
            <button type="button" wire:click="openTransferModal"
                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                    <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                </svg>
                Transfer
            </button>
        </div>
    </div>

    {{-- ── Nav tabs ─────────────────────────────────────────────────────────── --}}
    @include('livewire.admin.accounts.banking.partials.nav-tabs', ['active' => 'transactions'])

    {{-- ── KPI strip ───────────────────────────────────────────────────────── --}}
    @php
        $totalIn  = (float) ($kpi->total_debit  ?? 0);
        $totalOut = (float) ($kpi->total_credit ?? 0);
        $netBal   = $totalIn - $totalOut;
    @endphp
    <div class="grid grid-cols-1 gap-3 mb-5 sm:grid-cols-3">
        <div class="rounded-xl border border-emerald-200 bg-white px-4 py-3">
            <p class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Total In (Debit)
            </p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($totalIn, 2) }}</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-white px-4 py-3">
            <p class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                Total Out (Credit)
            </p>
            <p class="mt-2 text-2xl font-bold text-rose-700">{{ number_format($totalOut, 2) }}</p>
        </div>
        <div class="rounded-xl border {{ $netBal >= 0 ? 'border-blue-200' : 'border-amber-200' }} bg-white px-4 py-3">
            <p class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                <span class="h-2 w-2 rounded-full {{ $netBal >= 0 ? 'bg-blue-500' : 'bg-amber-500' }}"></span>
                Net Balance
            </p>
            <p class="mt-2 text-2xl font-bold {{ $netBal >= 0 ? 'text-blue-700' : 'text-amber-700' }}">
                {{ number_format(abs($netBal), 2) }}
                @if($netBal < 0)<span class="text-sm font-normal text-rose-500 ml-1">(deficit)</span>@endif
            </p>
        </div>
    </div>

    {{-- ── Filter bar ──────────────────────────────────────────────────────── --}}
    <div class="mb-3 flex flex-wrap items-center gap-2">
        <div class="relative">
            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search reference, name, notes…"
                class="h-9 rounded-lg border border-gray-300 pl-8 pr-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none min-w-52">
        </div>

        <select wire:model.live="bankAccountFilter"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none">
            <option value="">All Accounts</option>
            @foreach($bankAccounts as $ba)
                <option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>
            @endforeach
        </select>

        <select wire:model.live="typeFilter"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none">
            <option value="">All Types</option>
            @foreach($transactionTypes as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>

        <input type="date" wire:model.live="dateFrom"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none">
        <input type="date" wire:model.live="dateTo"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm text-gray-700 focus:border-indigo-500 focus:outline-none">

        @if($dateFrom || $dateTo || $bankAccountFilter || $typeFilter || $search)
            <button type="button" wire:click="$set('dateFrom', ''); $set('dateTo', ''); $set('bankAccountFilter', null); $set('typeFilter', ''); $set('search', '')"
                class="h-9 rounded-lg border border-gray-200 px-3 text-sm text-gray-500 hover:bg-gray-50">
                Clear filters
            </button>
        @endif
    </div>

    {{-- ── Table ───────────────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto min-h-[50vh]">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Account</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Method</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">Notes</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-emerald-600">DR (In)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-rose-600">CR (Out)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($lines as $line)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                {{ $line->transaction?->datetime?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($line->transaction?->type)
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium {{ $line->transaction->type->badgeClass() }}">
                                        {{ $line->transaction->type->label() }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $line->account?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($line->transaction?->method)
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $line->transaction->method->badgeClass() }}">
                                        {{ $line->transaction->method->label() }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 font-mono text-xs">
                                {{ $line->transaction?->reference_no ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-xs truncate">
                                {{ $line->description ?: ($line->transaction?->notes ?? '—') }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium">
                                @if ($line->debit > 0)
                                    <span class="text-emerald-700">{{ number_format($line->debit, 2) }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium">
                                @if ($line->credit > 0)
                                    <span class="text-rose-700">{{ number_format($line->credit, 2) }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center text-sm text-gray-400">
                                No transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($lines->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $lines->links() }}
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- DEPOSIT MODAL                                                           --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showDepositModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="closeDepositModal">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl" @click.stop>
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100">
                            <svg class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </span>
                        <h2 class="text-base font-semibold text-gray-900">Record Deposit</h2>
                    </div>
                    <button type="button" wire:click="closeDepositModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="deposit" class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Bank Account <span class="text-red-500">*</span></label>
                            <select wire:model="deposit_account_id"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none @error('deposit_account_id') border-red-400 @enderror">
                                <option value="">Select bank account…</option>
                                @foreach($bankAccounts as $ba)
                                    <option value="{{ $ba->id }}">{{ $ba->bank_name }}{{ $ba->ac_number ? ' (' . $ba->ac_number . ')' : '' }}</option>
                                @endforeach
                            </select>
                            @error('deposit_account_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Contra Account (Credit) <span class="text-red-500">*</span></label>
                            <select wire:model="deposit_contra_id"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none @error('deposit_contra_id') border-red-400 @enderror">
                                <option value="">Select account to credit…</option>
                                @foreach($contraAccounts as $ac)
                                    <option value="{{ $ac->id }}">{{ $ac->name }}{{ $ac->code ? ' [' . $ac->code . ']' : '' }}</option>
                                @endforeach
                            </select>
                            @error('deposit_contra_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Amount <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="deposit_amount" step="0.001" min="0.001" placeholder="0.00"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none @error('deposit_amount') border-red-400 @enderror">
                            @error('deposit_amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="deposit_date"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none @error('deposit_date') border-red-400 @enderror">
                            @error('deposit_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Method <span class="text-red-500">*</span></label>
                            <select wire:model="deposit_method"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none @error('deposit_method') border-red-400 @enderror">
                                <option value="">Select method…</option>
                                @foreach($entryMethods as $m)
                                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                @endforeach
                            </select>
                            @error('deposit_method') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Reference No</label>
                            <input type="text" wire:model="deposit_reference" placeholder="Cheque / ref no…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                            <textarea wire:model="deposit_notes" rows="2" placeholder="Optional description…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" wire:click="closeDepositModal"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="deposit">Record Deposit</span>
                            <span wire:loading wire:target="deposit">Saving…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- WITHDRAWAL MODAL                                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showWithdrawalModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="closeWithdrawalModal">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl" @click.stop>
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-100">
                            <svg class="h-4 w-4 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </span>
                        <h2 class="text-base font-semibold text-gray-900">Record Withdrawal</h2>
                    </div>
                    <button type="button" wire:click="closeWithdrawalModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="withdraw" class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Bank Account <span class="text-red-500">*</span></label>
                            <select wire:model="withdrawal_account_id"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none @error('withdrawal_account_id') border-red-400 @enderror">
                                <option value="">Select bank account…</option>
                                @foreach($bankAccounts as $ba)
                                    <option value="{{ $ba->id }}">{{ $ba->bank_name }}{{ $ba->ac_number ? ' (' . $ba->ac_number . ')' : '' }}</option>
                                @endforeach
                            </select>
                            @error('withdrawal_account_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Contra Account (Debit) <span class="text-red-500">*</span></label>
                            <select wire:model="withdrawal_contra_id"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none @error('withdrawal_contra_id') border-red-400 @enderror">
                                <option value="">Select account to debit…</option>
                                @foreach($contraAccounts as $ac)
                                    <option value="{{ $ac->id }}">{{ $ac->name }}{{ $ac->code ? ' [' . $ac->code . ']' : '' }}</option>
                                @endforeach
                            </select>
                            @error('withdrawal_contra_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Amount <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="withdrawal_amount" step="0.001" min="0.001" placeholder="0.00"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none @error('withdrawal_amount') border-red-400 @enderror">
                            @error('withdrawal_amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="withdrawal_date"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none @error('withdrawal_date') border-red-400 @enderror">
                            @error('withdrawal_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Method <span class="text-red-500">*</span></label>
                            <select wire:model="withdrawal_method"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none @error('withdrawal_method') border-red-400 @enderror">
                                <option value="">Select method…</option>
                                @foreach($entryMethods as $m)
                                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                @endforeach
                            </select>
                            @error('withdrawal_method') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Reference No</label>
                            <input type="text" wire:model="withdrawal_reference" placeholder="Cheque / ref no…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                            <textarea wire:model="withdrawal_notes" rows="2" placeholder="Optional description…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" wire:click="closeWithdrawalModal"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="withdraw">Record Withdrawal</span>
                            <span wire:loading wire:target="withdraw">Saving…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- TRANSFER MODAL                                                          --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($showTransferModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="closeTransferModal">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl" @click.stop>
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100">
                            <svg class="h-4 w-4 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                                <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                            </svg>
                        </span>
                        <h2 class="text-base font-semibold text-gray-900">Record Transfer</h2>
                    </div>
                    <button type="button" wire:click="closeTransferModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="transfer" class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">From Account <span class="text-red-500">*</span></label>
                            <select wire:model="transfer_from_id"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none @error('transfer_from_id') border-red-400 @enderror">
                                <option value="">Select source…</option>
                                @foreach($bankAccounts as $ba)
                                    <option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>
                                @endforeach
                            </select>
                            @error('transfer_from_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">To Account <span class="text-red-500">*</span></label>
                            <select wire:model="transfer_to_id"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none @error('transfer_to_id') border-red-400 @enderror">
                                <option value="">Select destination…</option>
                                @foreach($bankAccounts as $ba)
                                    <option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>
                                @endforeach
                            </select>
                            @error('transfer_to_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Amount <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="transfer_amount" step="0.001" min="0.001" placeholder="0.00"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none @error('transfer_amount') border-red-400 @enderror">
                            @error('transfer_amount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="transfer_date"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none @error('transfer_date') border-red-400 @enderror">
                            @error('transfer_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Method <span class="text-red-500">*</span></label>
                            <select wire:model="transfer_method"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none @error('transfer_method') border-red-400 @enderror">
                                <option value="">Select method…</option>
                                @foreach($entryMethods as $m)
                                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                @endforeach
                            </select>
                            @error('transfer_method') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Reference No</label>
                            <input type="text" wire:model="transfer_reference" placeholder="Ref / cheque no…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                            <textarea wire:model="transfer_notes" rows="2" placeholder="Optional description…"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" wire:click="closeTransferModal"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="transfer">Record Transfer</span>
                            <span wire:loading wire:target="transfer">Saving…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
