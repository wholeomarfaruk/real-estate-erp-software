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
            <p class="mt-1 font-mono text-[11px] text-gray-400">{{ $kpi->adjusted_count ?? 0 }} adjusted</p>
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

        {{-- Category filter --}}
        <select wire:model.live="categoryFilter"
            class="h-9 rounded-md border border-gray-200 bg-white px-2.5 text-xs text-gray-700 focus:border-gray-400 focus:outline-none appearance-none pr-7"
            style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 8px center;">
            <option value="">All categories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}</option>
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
                                Category / Reference</th>
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
                                $isAdv = $transaction->type?->value === 'advance';
                                $isIncome = $transaction->type?->value === 'income';
                                $bankAcc = $transaction->account?->bankAccount;
                                $accColor = match ($transaction->account?->type?->value ?? '') {
                                    'bank' => '#0d2a4a',
                                    'cash' => '#b45309',
                                    'mfs' => '#be185d',
                                    'wallet' => '#6d28d9',
                                    default => '#4b5563',
                                };
                                $accInitial = $bankAcc
                                    ? mb_strtoupper(
                                        mb_substr($bankAcc->bank_name ?? ($transaction->account?->name ?? '?'), 0, 1),
                                    )
                                    : mb_strtoupper(mb_substr($transaction->account?->name ?? '?', 0, 1));
                                $accLabel = $bankAcc ? $bankAcc->bank_name : $transaction->account?->name ?? '—';
                                $accSub = $bankAcc
                                    ? $bankAcc->code ?? ($transaction->account?->code ?? '')
                                    : $transaction->account?->code ?? '';
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
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-grid h-6 w-6 shrink-0 place-items-center rounded-md text-[11px] font-bold text-white"
                                            style="background:{{ $accColor }}">{{ $accInitial }}</span>
                                        <div>
                                            <div class="text-xs font-medium text-gray-800 leading-tight">
                                                {{ \Illuminate\Support\Str::limit($accLabel, 22) }}</div>
                                            @if ($accSub)
                                                <div class="mt-0.5 font-mono text-[10px] text-gray-400">
                                                    {{ $accSub }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Category / Reference --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-medium text-gray-800">
                                            {{ $transaction->transactionCategory?->name ?? '—' }}
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
                                                class="rounded bg-amber-50 border border-amber-200 px-1 py-px text-[9px] font-semibold uppercase text-amber-700">Adj</span>
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
                                    @if ((float) $transaction->debit > 0)
                                        <span class="{{ $isAdv ? 'text-cyan-700' : 'text-emerald-700' }}">
                                            +{{ number_format((float) $transaction->debit, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>

                                {{-- Credit --}}
                                <td class="px-4 py-3 text-right font-mono text-xs font-semibold whitespace-nowrap">
                                    @if ((float) $transaction->credit > 0)
                                        <span class="{{ $isAdv ? 'text-cyan-700' : 'text-rose-700' }}">
                                            −{{ number_format((float) $transaction->credit, 2) }}
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
                    $vIsIncome = $viewTransaction->type?->value === 'income';
                    $vIsAdv = $viewTransaction->type?->value === 'advance';
                    $vIsExpense = $viewTransaction->type?->value === 'expense';
                    $amount =
                        $vIsIncome || ($vIsAdv && (float) $viewTransaction->debit > 0)
                            ? (float) $viewTransaction->debit
                            : (float) $viewTransaction->credit;
                    $amtSign = $vIsIncome || ($vIsAdv && (float) $viewTransaction->debit > 0) ? '+' : '−';
                    $amtClass = $vIsAdv ? 'text-cyan-700' : ($vIsIncome ? 'text-emerald-700' : 'text-rose-700');

                    $vBankAcc = $viewTransaction->account?->bankAccount;
                    $vAccColor = match ($viewTransaction->account?->type?->value ?? '') {
                        'bank' => '#0d2a4a',
                        'cash' => '#b45309',
                        'mfs' => '#be185d',
                        'wallet' => '#6d28d9',
                        default => '#4b5563',
                    };
                    $vAccInitial = $vBankAcc
                        ? mb_strtoupper(
                            mb_substr($vBankAcc->bank_name ?? ($viewTransaction->account?->name ?? '?'), 0, 1),
                        )
                        : mb_strtoupper(mb_substr($viewTransaction->account?->name ?? '?', 0, 1));
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
                                ADVANCE · {{ (float) $viewTransaction->debit > 0 ? 'Received' : 'Paid' }}
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
                                class="rounded bg-amber-50 border border-amber-200 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-amber-700">Adjusted</span>
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
                                <strong class="font-semibold text-gray-800">Adjusted</strong> on
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

                        <dt class="pt-px text-[10px] font-semibold uppercase tracking-wide text-gray-400">Category</dt>
                        <dd class="text-gray-700">
                            {{ $viewTransaction->transactionCategory?->name ?? '—' }}
                            @if ($viewTransaction->transactionCategory?->parent)
                                <span
                                    class="block font-mono text-[10px] text-gray-400">{{ $viewTransaction->transactionCategory->parent->name }}</span>
                            @endif
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

                    {{-- Bank account card --}}
                    <div class="mt-4">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Bank Account
                        </p>
                        <div class="flex items-center gap-3 rounded-lg border border-blue-100 bg-blue-50/40 p-3">
                            <span
                                class="inline-grid h-8 w-8 shrink-0 place-items-center rounded-lg text-sm font-bold text-white"
                                style="background:{{ $vAccColor }}">{{ $vAccInitial }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="font-mono text-xs font-semibold text-gray-800">
                                    {{ $vBankAcc ? $vBankAcc->bank_name : $viewTransaction->account?->name ?? '—' }}
                                </p>
                                <p class="mt-0.5 font-mono text-[10px] text-gray-500">
                                    {{ $viewTransaction->account?->code ?? '' }}
                                    @if ($vBankAcc?->code)
                                        · {{ $vBankAcc->code }}
                                    @endif
                                    @if ($vBankAcc?->ac_number)
                                        · {{ $vBankAcc->masked_ac_number }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Linked COA --}}
                    <div class="mt-4">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Linked Chart
                            of Accounts</p>
                        <div class="flex items-center gap-3 rounded-lg border border-blue-100 bg-[#eaf0f8] p-3">
                            <span
                                class="inline-grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[#0d2a4a] text-white">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                </svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="font-mono text-xs font-semibold text-gray-800">
                                    {{ $viewTransaction->account?->code ? $viewTransaction->account->code . ' · ' : '' }}{{ $viewTransaction->account?->name ?? '—' }}
                                </p>
                                <p class="mt-0.5 font-mono text-[10px] text-gray-500">
                                    {{ ucfirst($viewTransaction->account?->type?->value ?? '') }} · accounts.id
                                    #{{ $viewTransaction->account?->id ?? '—' }}
                                </p>
                            </div>
                            <a href="{{ route('admin.accounts.chart-of-accounts.index') }}"
                                class="text-[10.5px] font-semibold text-[#0d2a4a] hover:underline whitespace-nowrap">
                                Open ledger →
                            </a>
                        </div>
                    </div>

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
                    @if ($viewTransactionFiles->isNotEmpty())
                        <div class="mt-4">
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                                Attachments
                                <span
                                    class="ml-1 rounded-full bg-gray-100 px-1.5 py-px font-mono text-[9.5px] text-gray-600">{{ $viewTransactionFiles->count() }}</span>
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                @include('livewire.admin.accounts.partials.attachment-list', [
                                    'attachments' => $viewTransactionFiles,
                                    'transactionId' => $viewTransaction->id,
                                    'fancyboxGroup' => 'txn-drawer-' . $viewTransaction->id,
                                    'canRemove' => false,
                                    'emptyMessage' => '',
                                ])
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Drawer footer --}}
                <div class="flex gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3 rounded-br-xl">
                    <button type="button"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-50">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10" />
                        </svg>
                        Adjust
                    </button>
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
