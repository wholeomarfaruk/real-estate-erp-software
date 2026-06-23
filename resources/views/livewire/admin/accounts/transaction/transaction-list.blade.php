<div x-data="{ drawerOpen: @entangle('showDrawer').live }" x-init="$store.pageName = { name: 'Account Transactions', slug: 'accounts-transactions' }">

    {{-- Page header --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Transactions</h1>
            <p class="text-sm text-gray-500">All confirmed transactions — debits, credits, and ledger entries.</p>
        </div>
    </div>

    @include('livewire.admin.accounts.banking.partials.nav-tabs', ['active' => 'transactions'])

    {{-- KPI Strip --}}
    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
        {{-- Income --}}
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 border-l-4 border-l-emerald-600">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Total Income</p>
            <p class="mt-2 font-serif text-2xl font-normal text-emerald-700">
                {{ number_format((float) ($kpi->total_income ?? 0), 2) }}</p>
            <p class="mt-1 font-mono text-[11px] text-gray-400">{{ $typeCounts->income_count ?? 0 }} transactions</p>
        </div>
        {{-- Expense --}}
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 border-l-4 border-l-rose-700">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Total Expense</p>
            <p class="mt-2 font-serif text-2xl font-normal text-rose-700">
                {{ number_format((float) ($kpi->total_expense ?? 0), 2) }}</p>
            <p class="mt-1 font-mono text-[11px] text-gray-400">{{ $typeCounts->expense_count ?? 0 }} transactions</p>
        </div>
        {{-- Advance --}}
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 border-l-4 border-l-cyan-600">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Advance</p>
            @php
                $advIn = (float) ($kpi->advance_in ?? 0);
                $advOut = (float) ($kpi->advance_out ?? 0);
            @endphp
            <p class="mt-2 font-serif text-2xl font-normal text-cyan-700">{{ number_format(abs($advIn - $advOut), 2) }}
            </p>
            <div class="mt-1 flex gap-2 font-mono text-[10px]">
                <span class="font-semibold text-emerald-600">↓ {{ number_format($advIn, 0) }}</span>
                <span class="font-semibold text-rose-600">↑ {{ number_format($advOut, 0) }}</span>
            </div>
        </div>
        {{-- Net Position --}}
        @php $net = (float)($kpi->net_position ?? 0); @endphp
        <div class="rounded-xl border border-gray-200 bg-[#0d2a4a] px-4 py-3 text-white">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-white/60">Net Flow</p>
            <p class="mt-2 font-serif text-2xl font-normal">{{ number_format(abs($net), 2) }}</p>
            <p class="mt-1 font-mono text-[11px] text-white/60">{{ $net >= 0 ? '▲ Surplus' : '▼ Deficit' }} · excl.
                advances</p>
        </div>
        {{-- Total count --}}
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Total Transactions</p>
            <p class="mt-2 font-serif text-2xl font-normal text-gray-800">
                {{ number_format((int) ($kpi->total_count ?? 0)) }}</p>
            <p class="mt-1 font-mono text-[11px] text-gray-400">{{ $kpi->adjusted_count ?? 0 }} reversed</p>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="flex flex-wrap items-center gap-2 rounded-t-xl border border-b-0 border-gray-200 bg-white px-3 py-2.5">

        {{-- Type segment tabs --}}
        <div class="flex gap-0.5 rounded-lg bg-gray-100 p-0.5">
            @php
                use App\Enums\Accounts\TransactionType;

                $tabs = [
                    '' => [
                        'label' => 'All',
                        'count' => $typeCounts->total ?? 0,
                    ],
                ];

                foreach (TransactionType::cases() as $type) {
                    $tabs[$type->value] = [
                        'label' => $type->label(),
                        'count' => $typeCounts->{$type->value . '_count'} ?? 0,
                    ];
                }
            @endphp
            @foreach ($tabs as $tabValue => $tab)
                <button type="button" wire:click="$set('typeFilter', '{{ $tabValue }}')"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition
                        {{ $typeFilter === $tabValue
                            ? 'bg-white text-gray-800 shadow-sm font-semibold'
                            : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $tab['label'] }}
                    <span
                        class="rounded-full px-1.5 py-px font-mono text-[10px] font-semibold
                        {{ $typeFilter === $tabValue ? 'bg-[#0d2a4a] text-white' : 'bg-black/5 text-gray-600' }}">
                        {{ $tab['count'] }}
                    </span>
                </button>
            @endforeach
        </div>

        <div class="flex-1"></div>

        {{-- Search --}}
        <div class="relative">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search ref, party, notes…"
                class="h-9 w-56 rounded-md border border-gray-200 bg-white pl-8 pr-3 text-xs text-gray-700 placeholder-gray-400 focus:border-gray-400 focus:outline-none">
        </div>

        {{-- Account filter --}}
        <select wire:model.live="accountFilter"
            class="h-9 rounded-md border border-gray-200 bg-white px-2.5 text-xs text-gray-700 focus:border-gray-400 focus:outline-none appearance-none pr-7"
            style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 8px center;">
            <option value="">All accounts</option>
            @foreach ($accounts as $acc)
                <option value="{{ $acc->id }}">{{ $acc->name }}{{ $acc->code ? ' · ' . $acc->code : '' }}
                </option>
            @endforeach
        </select>


        {{-- Method filter --}}
        <select wire:model.live="methodFilter"
            class="h-9 rounded-md border border-gray-200 bg-white px-2.5 text-xs text-gray-700 focus:border-gray-400 focus:outline-none appearance-none pr-7"
            style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 8px center;">
            <option value="">All methods</option>
            @foreach ($methods as $method)
                <option value="{{ $method->value }}">{{ $method->label() }}</option>
            @endforeach
        </select>

        {{-- Date from --}}
        <input type="date" wire:model.live="dateFrom"
            class="h-9 rounded-md border border-gray-200 bg-white px-2.5 text-xs text-gray-700 focus:border-gray-400 focus:outline-none flatpickr-only-date">

        {{-- Date to --}}
        <input type="date" wire:model.live="dateTo"
            class="h-9 rounded-md border border-gray-200 bg-white px-2.5 text-xs text-gray-700 focus:border-gray-400 focus:outline-none flatpickr-only-date">
    </div>

    {{-- Workspace: table + side drawer --}}
    <div class="flex items-stretch">

        {{-- Table side --}}
        <div class="min-w-0 flex-1 overflow-hidden border border-t-0 border-gray-200 bg-white"
            :class="drawerOpen ? 'rounded-bl-xl' : 'rounded-b-xl'">
            <div class="max-w-full overflow-x-auto min-h-[55vh]">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th
                                class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                                Date / Ref</th>
                            <th
                                class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                                Account</th>
                            <th
                                class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                                Reference</th>
                            <th
                                class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                                Method</th>
                            <th
                                class="px-4 py-2.5 text-right text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                                Debit</th>
                            <th
                                class="px-4 py-2.5 text-right text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                                Credit</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $transaction)
                            @php
                                $hasLines = $transaction->lines->isNotEmpty();
                                $lineDebit = (float) $transaction->lines->sum('debit');
                                $lineCredit = (float) $transaction->lines->sum('credit');
                                $rowDebit = $lineDebit;
                                $rowCredit = $lineCredit;
                                $isAdv = $transaction->type?->value === 'advance';
                                $isIncome = $transaction->type?->value === 'income';
                            @endphp
                            <tr wire:click="openDrawer({{ $transaction->id }})"
                                class="cursor-pointer transition hover:bg-gray-50 {{ $viewingId === $transaction->id ? 'bg-blue-50 hover:bg-blue-50' : '' }}">
                                {{-- Date / Ref --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-mono text-xs font-semibold text-gray-800">
                                        TXN-{{ $transaction->id }}</div>
                                    <div class="mt-0.5 font-mono text-[10px] text-gray-400">
                                        {{ optional($transaction->datetime)->format('d M Y, H:i') }}
                                    </div>
                                </td>

                                {{-- Account --}}
                                <td class="px-4 py-3">
                                    @if ($hasLines)
                                        @php
                                            // Double-entry: a line is either a debit (money IN to that account)
                                            // or a credit (money OUT of that account).
                                            $debitLines = $transaction->lines->filter(fn ($l) => (float) $l->debit > 0)->values();
                                            $creditLines = $transaction->lines->filter(fn ($l) => (float) $l->credit > 0)->values();
                                            $drLine = $debitLines->first();
                                            $crLine = $creditLines->first();
                                            $drExtra = max(0, $debitLines->count() - 1);
                                            $crExtra = max(0, $creditLines->count() - 1);
                                        @endphp
                                        <div class="space-y-1">
                                            {{-- Dr — debit side --}}
                                            <div class="flex items-center gap-1.5 text-xs">
                                                <span class="inline-flex items-center justify-center rounded bg-emerald-50 px-1 py-px text-[9px] font-bold uppercase tracking-wide text-emerald-700">
                                                    Dr
                                                </span>
                                                <span class="font-medium text-gray-800">{{ \Illuminate\Support\Str::limit($drLine?->account?->name ?? '—', 14) }}</span>
                                                @if ($drExtra)
                                                    <span class="font-mono text-[9px] text-gray-400">+{{ $drExtra }}</span>
                                                @endif
                                                <span class="ml-auto font-mono text-[10px] font-semibold text-emerald-700">{{ number_format($lineDebit, 2) }}</span>
                                            </div>
                                            {{-- Cr — credit side --}}
                                            <div class="flex items-center gap-1.5 text-xs">
                                                <span class="inline-flex items-center justify-center rounded bg-rose-50 px-1 py-px text-[9px] font-bold uppercase tracking-wide text-rose-700">
                                                    Cr
                                                </span>
                                                <span class="font-medium text-gray-800">{{ \Illuminate\Support\Str::limit($crLine?->account?->name ?? '—', 14) }}</span>
                                                @if ($crExtra)
                                                    <span class="font-mono text-[9px] text-gray-400">+{{ $crExtra }}</span>
                                                @endif
                                                <span class="ml-auto font-mono text-[10px] font-semibold text-rose-700">{{ number_format($lineCredit, 2) }}</span>
                                            </div>
                                        </div>
                                        <div class="mt-0.5 font-mono text-[10px] text-gray-400">
                                            {{ $transaction->lines->count() }} ledger lines · double-entry
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-300">No ledger lines</span>
                                    @endif
                                </td>

                                {{-- Reference --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-medium text-gray-800">
                                            {{ ucfirst($transaction->type?->value ?? '—') }}
                                        </span>
                                        @if ($isAdv)
                                            <span
                                                class="rounded bg-cyan-50 border border-cyan-200 px-1 py-px text-[9px] font-bold uppercase tracking-wide text-cyan-700">Adv</span>
                                        @endif
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-1.5 font-mono text-[10px] text-gray-400">
                                        @if ($transaction->reference_no)
                                            <span>{{ $transaction->reference_no }}</span>
                                        @elseif ($transaction->reference_type)
                                            <span>{{ $transaction->reference_type }}#{{ $transaction->reference_id }}</span>
                                        @else
                                            <span class="text-gray-300">No reference</span>
                                        @endif
                                        @if ($transaction->adjusted_at)
                                            <span
                                                class="rounded bg-amber-50 border border-amber-200 px-1 py-px text-[9px] font-semibold uppercase text-amber-700">Rev</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Method --}}
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[10.5px] text-gray-600">
                                        {{ ucwords(str_replace('_', ' ', $transaction->method ?? '')) ?: '—' }}
                                    </span>
                                </td>

                                {{-- Debit --}}
                                <td class="px-4 py-3 text-right font-mono text-xs font-semibold whitespace-nowrap">
                                    @if ($rowDebit > 0)
                                        <span class="{{ $isAdv ? 'text-cyan-700' : 'text-emerald-700' }}">
                                            +{{ number_format($rowDebit, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>

                                {{-- Credit --}}
                                <td class="px-4 py-3 text-right font-mono text-xs font-semibold whitespace-nowrap">
                                    @if ($rowCredit > 0)
                                        <span class="{{ $isAdv ? 'text-cyan-700' : 'text-rose-700' }}">
                                            −{{ number_format($rowCredit, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>

                                {{-- Open --}}
                                <td class="px-4 py-3 text-right">
                                    @can('accounts.transaction.view')
                                        <button type="button"
                                            class="inline-flex items-center gap-1 rounded px-1.5 py-1 text-[11px] font-medium text-gray-300 transition hover:text-[#0d2a4a] {{ $viewingId === $transaction->id ? 'text-[#0d2a4a] font-semibold' : '' }}">
                                            Open
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <polyline points="9 18 15 12 9 6" />
                                            </svg>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <p class="text-sm font-medium text-gray-600">No transactions found.</p>
                                    <p class="mt-1 text-xs text-gray-400">Try adjusting your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Table footer / pagination --}}
            <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50 px-4 py-2.5">
                <span class="text-xs text-gray-400">
                    Showing {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }} of
                    {{ $transactions->total() }} transactions
                </span>
                @if ($transactions->hasPages())
                    {{ $transactions->links() }}
                @endif
            </div>
        </div>

        {{-- Side Drawer --}}
        <div x-cloak x-show="drawerOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4"
            class="flex w-[420px] shrink-0 flex-col border border-t-0 border-l-0 border-gray-200 bg-white rounded-br-xl overflow-hidden"
            style="min-height: 100%">
            @if ($viewTransaction)
                @php
                    $vHasLines = $viewTransaction->lines->isNotEmpty();
                    $vLineDebit = (float) $viewTransaction->lines->sum('debit');
                    $vLineCredit = (float) $viewTransaction->lines->sum('credit');
                    $vIsIncome = $viewTransaction->type?->value === 'income';
                    $vIsAdv = $viewTransaction->type?->value === 'advance';
                    $vIsExpense = $viewTransaction->type?->value === 'expense';
                    $vDebit = $vLineDebit;
                    $vCredit = $vLineCredit;
                    $amount =
                        $vIsIncome || ($vIsAdv && $vDebit > 0)
                            ? $vDebit
                            : $vCredit;
                    $amtSign = $vIsIncome || ($vIsAdv && $vDebit > 0) ? '+' : '−';
                    $amtClass = $vIsAdv ? 'text-cyan-700' : ($vIsIncome ? 'text-emerald-700' : 'text-rose-700');
                @endphp

                {{-- Drawer head --}}
                <div class="border-b border-gray-200 px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-mono text-sm font-semibold text-gray-800">TXN-{{ $viewTransaction->id }}
                            </div>
                            <div class="mt-0.5 text-[10px] uppercase tracking-widest text-gray-400">
                                {{ optional($viewTransaction->datetime)->format('d M Y, H:i') }} ·
                                {{ $viewTransaction->creator?->name ?? 'N/A' }}
                            </div>
                        </div>
                        <button type="button" wire:click="closeDrawer"
                            class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-3 font-serif text-4xl font-normal {{ $amtClass }}">
                        {{ $amtSign }}<span
                            class="mr-1 font-sans text-sm opacity-60">BDT</span>{{ number_format($amount, 2) }}
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        {{-- Type badge --}}
                        @if ($vIsAdv)
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-cyan-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-cyan-600"></span>
                                ADVANCE · {{ $vDebit > 0 ? 'Received' : 'Paid' }}
                            </span>
                        @elseif ($vIsIncome)
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>INCOME
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-rose-700">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-rose-700"></span>{{ strtoupper($viewTransaction->type?->value ?? 'UNKNOWN') }}
                            </span>
                        @endif
                        {{-- Method pill --}}
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[10.5px] text-gray-600">
                            {{ ucwords(str_replace('_', ' ', $viewTransaction->method ?? '')) ?: '—' }}
                        </span>
                        @if ($viewTransaction->adjusted_at)
                            <span
                                class="rounded bg-amber-50 border border-amber-200 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-amber-700">Reversed</span>
                        @endif
                    </div>
                </div>

                {{-- Drawer body --}}
                <div class="flex-1 overflow-y-auto px-5 py-4">

                    {{-- Adjusted banner --}}
                    @if ($viewTransaction->adjusted_at)
                        <div
                            class="mb-4 flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path
                                    d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                <line x1="12" y1="9" x2="12" y2="13" />
                                <line x1="12" y1="17" x2="12.01" y2="17" />
                            </svg>
                            <p class="text-xs text-amber-800">
                                <strong class="font-semibold text-gray-800">Reversed</strong> on
                                {{ optional($viewTransaction->adjusted_at)->format('d M Y, H:i') }}
                                by {{ $viewTransaction->adjustedByUser?->name ?? 'N/A' }}
                                @if ($viewTransaction->adjusted_transaction_id)
                                    · linked TXN-{{ $viewTransaction->adjusted_transaction_id }}
                                @endif
                            </p>
                        </div>
                    @endif

                    {{-- Field grid --}}
                    <dl class="grid grid-cols-[100px_1fr] gap-x-3 gap-y-2.5 text-xs">
                        <dt class="pt-px text-[10px] font-semibold uppercase tracking-wide text-gray-400">Date &amp;
                            Time</dt>
                        <dd class="font-mono text-xs font-medium text-gray-700">
                            {{ optional($viewTransaction->datetime)->format('d M Y, H:i') }}</dd>

                        <dt class="pt-px text-[10px] font-semibold uppercase tracking-wide text-gray-400">Type</dt>
                        <dd class="text-gray-700">
                            {{ ucfirst($viewTransaction->type?->value ?? '—') }}
                        </dd>

                        @if ($viewTransaction->name)
                            <dt class="pt-px text-[10px] font-semibold uppercase tracking-wide text-gray-400">Party
                            </dt>
                            <dd>
                                <span class="font-medium text-gray-800">{{ $viewTransaction->name }}</span>
                                @if ($viewTransaction->phone)
                                    <span
                                        class="block font-mono text-[10px] text-gray-400">{{ $viewTransaction->phone }}</span>
                                @endif
                            </dd>
                        @endif

                        <dt class="pt-px text-[10px] font-semibold uppercase tracking-wide text-gray-400">Created by
                        </dt>
                        <dd class="text-gray-700">{{ $viewTransaction->creator?->name ?? 'N/A' }}</dd>
                    </dl>

                    {{-- Journal Entry / Ledger Lines (double-entry) --}}
                    @if ($vHasLines)
                        <div class="mt-4">
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Journal
                                Entry</p>
                            <div class="overflow-hidden rounded-lg border border-gray-200">
                                <table class="min-w-full border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-gray-50 text-[10px] uppercase tracking-wide text-gray-400">
                                            <th class="px-3 py-2 text-left font-semibold">Account</th>
                                            <th class="px-3 py-2 text-right font-semibold">Debit</th>
                                            <th class="px-3 py-2 text-right font-semibold">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($viewTransaction->lines as $line)
                                            <tr>
                                                <td class="px-3 py-2">
                                                    <div class="font-medium text-gray-800">
                                                        {{ $line->account?->name ?? '—' }}</div>
                                                    @if ($line->account?->code || $line->notes)
                                                        <div class="mt-0.5 font-mono text-[10px] text-gray-400">
                                                            {{ $line->account?->code }}{{ $line->account?->code && $line->notes ? ' · ' : '' }}{{ $line->notes }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono text-emerald-700">
                                                    {{ (float) $line->debit > 0 ? number_format((float) $line->debit, 2) : '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono text-rose-700">
                                                    {{ (float) $line->credit > 0 ? number_format((float) $line->credit, 2) : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t border-gray-200 bg-gray-50 font-semibold">
                                            <td class="px-3 py-2 text-[10px] uppercase tracking-wide text-gray-500">Total
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono text-gray-800">
                                                {{ number_format($vLineDebit, 2) }}</td>
                                            <td class="px-3 py-2 text-right font-mono text-gray-800">
                                                {{ number_format($vLineCredit, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            @if (round($vLineDebit, 3) !== round($vLineCredit, 3))
                                <p class="mt-1.5 text-[10px] font-semibold text-rose-600">⚠ Entry is not balanced.</p>
                            @else
                                <p class="mt-1.5 text-[10px] text-emerald-600">✓ Balanced double-entry.</p>
                            @endif
                        </div>
                    @endif

                    {{-- Source reference --}}
                    @if ($viewTransactionReference)
                        <div class="mt-4">
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Source
                                Reference</p>
                            <x-transaction-reference-card :reference="$viewTransactionReference" />
                        </div>
                    @endif

                    {{-- Notes --}}
                    <div class="mt-4">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Notes</p>
                        <div
                            class="rounded-md border border-gray-100 bg-gray-50 px-3 py-2.5 text-xs italic leading-relaxed text-gray-600">
                            {{ $viewTransaction->notes ?: 'No notes.' }}
                        </div>
                    </div>

                    {{-- Attachments --}}
                    <div class="mt-4">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                            Attachments
                            <span
                                class="ml-1 rounded-full bg-gray-100 px-1.5 py-px font-mono text-[9.5px] text-gray-600">{{ $viewTransactionFiles->count() }}</span>
                        </p>

                        @if ($viewTransactionFiles->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5">
                                @include('livewire.admin.accounts.partials.attachment-list', [
                                    'attachments' => $viewTransactionFiles,
                                    'transactionId' => $viewTransaction->id,
                                    'fancyboxGroup' => 'txn-drawer-' . $viewTransaction->id,
                                    'canRemove' => false,
                                    'emptyMessage' => '',
                                ])
                            </div>
                        @else
                            <p class="text-xs text-gray-400">No attachments yet.</p>
                        @endif

                        {{-- Upload more attachments --}}
                        @can('accounts.transaction-attachment.create')
                            <div class="mt-3 rounded-lg border border-dashed border-gray-200 p-3">
                                <x-media-picker-field
                                    field="newAttachments"
                                    :value="$newAttachments"
                                    label="Add attachments"
                                    placeholder="Select files to upload"
                                    :multiple="true"
                                    type="all"
                                    :required="false"
                                />
                                @if (!empty($newAttachments))
                                    <button type="button" wire:click="saveAttachments"
                                        wire:loading.attr="disabled" wire:target="saveAttachments"
                                        class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-indigo-700 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="saveAttachments">Save attachments</span>
                                        <span wire:loading wire:target="saveAttachments">Saving…</span>
                                    </button>
                                @endif
                            </div>
                        @endcan
                    </div>

                </div>

                {{-- Drawer footer --}}
                <div class="flex gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3 rounded-br-xl">
                    @if ($viewTransaction->adjusted_at)
                        <button type="button" disabled
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-400 cursor-not-allowed">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 4 23 10 17 10" />
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10" />
                            </svg>
                            Reversed
                        </button>
                    @else
                        <button type="button"
                            wire:click="reverse"
                            wire:confirm="Reverse this transaction? A mirror-image entry will be posted to cancel it out."
                            wire:loading.attr="disabled" wire:target="reverse"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-50 disabled:opacity-50">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 4 23 10 17 10" />
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10" />
                            </svg>
                            <span wire:loading.remove wire:target="reverse">Reverse</span>
                            <span wire:loading wire:target="reverse">Reversing…</span>
                        </button>
                    @endif
                    <button type="button"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-50">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9" />
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <rect x="6" y="14" width="12" height="8" />
                        </svg>
                        Print
                    </button>
                </div>
            @else
                {{-- Empty drawer state --}}
                <div class="grid flex-1 place-items-center p-10 text-center text-gray-400">
                    <div>
                        <p class="font-serif text-5xl text-gray-200">⇄</p>
                        <p class="mt-3 text-sm text-gray-400">Select a transaction to see details</p>
                    </div>
                </div>
            @endif
        </div>

    </div>{{-- /workspace --}}

</div>
