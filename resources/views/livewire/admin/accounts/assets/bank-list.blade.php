<div x-data x-init="$store.pageName = { name: 'Bank Accounts' }">

    {{-- ── Page header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Bank Accounts</h1>
            <p class="text-sm text-gray-500">Company bank, cash, MFS and wallet accounts — balances and ledger links.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="openDepositModal"
                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="19" x2="12" y2="5" />
                    <polyline points="5 12 12 5 19 12" />
                </svg>
                Deposit / Opening Balance
            </button>
            @can('accounts.chart.edit')
                <button type="button" wire:click="openCreateModal"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    Add Account
                </button>
            @endcan
        </div>
    </div>

    {{-- ── Banking nav tabs ────────────────────────────────────────────────── --}}
    @include('livewire.admin.accounts.banking.partials.nav-tabs', ['active' => 'bank-accounts'])

    {{-- ── Totals bar ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-3 mb-4 sm:grid-cols-3 xl:grid-cols-5">
        {{-- Total Balance --}}
        <div class="col-span-2 sm:col-span-1 xl:col-span-1 rounded-xl bg-gray-900 px-5 py-4 text-white">
            <p class="text-xs font-semibold uppercase tracking-widest text-white/60">Total Balance (BDT)</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format($totalBalance, 2) }}</p>
            <p class="mt-1.5 text-xs text-white/50">{{ $activeCount }} active accounts</p>
        </div>
        @foreach ($typeBalances as $typeKey => $info)
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3"
                style="border-left: 4px solid {{ $info['color'] }}">
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ $info['label'] }}</p>
                <p class="mt-1.5 text-xl font-semibold text-gray-800">{{ number_format($info['balance'], 2) }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ $info['count'] }}
                    {{ $info['count'] === 1 ? 'account' : 'accounts' }}</p>
            </div>
        @endforeach
    </div>

    {{-- ── Filters ─────────────────────────────────────────────────────────── --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        {{-- Search --}}
        <div class="relative">
            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search bank, code, A/C…"
                class="h-9 rounded-lg border border-gray-300 pl-8 pr-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none min-w-52">
        </div>

        {{-- Status segment --}}
        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-100 p-0.5 text-xs">
            @foreach (['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $val => $lbl)
                <button type="button" wire:click="$set('statusFilter', '{{ $val }}')"
                    class="rounded-md px-3 py-1.5 font-medium transition
                           {{ $statusFilter === $val ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $lbl }}
                </button>
            @endforeach
        </div>

        {{-- Type segment --}}
        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-100 p-0.5 text-xs">
            <button type="button" wire:click="$set('typeFilter', '')"
                class="rounded-md px-3 py-1.5 font-medium transition
                       {{ $typeFilter === '' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                All
            </button>
            @foreach ($types as $type)
                <button type="button" wire:click="$set('typeFilter', '{{ $type->value }}')"
                    class="rounded-md px-3 py-1.5 font-medium transition
                           {{ $typeFilter === $type->value ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $type->label() }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ── Accounts grid ───────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">

        @forelse($accounts as $account)
            @php
                $color = $account->type_color;
                $typeBg = match ($account->type) {
                    'bank' => 'bg-blue-50 border-blue-200 text-blue-900',
                    'cash' => 'bg-amber-50 border-amber-300 text-amber-800',
                    'mfs' => 'bg-pink-50 border-pink-200 text-pink-800',
                    'wallet' => 'bg-violet-50 border-violet-200 text-violet-800',
                    default => 'bg-gray-100 border-gray-200 text-gray-700',
                };
                $typeLabel = match ($account->type) {
                    'bank' => 'Bank',
                    'cash' => 'Cash',
                    'mfs' => 'MFS',
                    'wallet' => 'Wallet',
                    default => strtoupper($account->type ?? 'N/A'),
                };
            @endphp

            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 cursor-pointer transition hover:shadow-md hover:-translate-y-px"
                wire:click="openDetailModal({{ $account->id }})">

                {{-- Left color stripe --}}
                <div class="absolute inset-y-0 left-0 w-1 rounded-l-xl" style="background: {{ $color }}"></div>

                {{-- Head: logo + name + pills --}}
                <div class="flex items-start justify-between gap-3 pl-2">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-base font-bold text-white"
                            style="background: {{ $color }}">
                            {{ $account->logo_initial }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 leading-tight">{{ $account->bank_name }}</p>
                            <p class="text-xs text-gray-400 mt-0.5 truncate max-w-[160px]">
                                {{ $account->branch ?: '—' }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <span
                            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[9.5px] font-bold uppercase tracking-wide {{ $typeBg }}">
                            {{ $typeLabel }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[9.5px] font-semibold uppercase tracking-wide
                            {{ $account->status === 'active' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-gray-100 border-gray-200 text-gray-500' }}">
                            <span
                                class="h-1.5 w-1.5 rounded-full {{ $account->status === 'active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                            {{ $account->status === 'active' ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                {{-- Label row --}}
                <div class="mt-3 pl-2 flex items-center justify-between">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">
                        {{ $account->note ? \Illuminate\Support\Str::limit($account->note, 28) : $typeLabel . ' Account' }}
                    </span>
                    @if ($account->code)
                        <span
                            class="font-mono text-[9.5px] font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $account->code }}</span>
                    @endif
                </div>

                {{-- A/C number --}}
                <div class="mt-1.5 pl-2 flex items-baseline gap-2">
                    <span class="text-[9px] font-semibold uppercase tracking-wide text-gray-400">A/C</span>
                    <span
                        class="font-mono text-xs text-gray-600 font-medium tracking-wide">{{ $account->masked_ac_number }}</span>
                </div>

                {{-- Balance --}}
                <div class="mt-2 pl-2">
                    <span class="text-xs text-gray-400 mr-1">{{ $account->account?->code ? 'BDT' : '' }}</span>
                    <span
                        class="text-2xl font-semibold text-gray-900 tracking-tight">{{ number_format($account->computed_balance, 2) }}</span>
                </div>

                {{-- Flows --}}
                <div class="mt-3 pl-2 grid grid-cols-2 gap-2 border-t border-dashed border-gray-200 pt-3">
                    <div>
                        <p
                            class="flex items-center gap-1 text-[9.5px] font-semibold uppercase tracking-wide text-gray-400">
                            <svg class="h-2.5 w-2.5 text-emerald-500" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="12" y1="19" x2="12" y2="5" />
                                <polyline points="5 12 12 5 19 12" />
                            </svg>
                            Today In
                        </p>
                        <p class="mt-0.5 font-mono text-xs font-semibold text-emerald-600">
                            +{{ number_format($account->today_inflow, 0) }}</p>
                    </div>
                    <div>
                        <p
                            class="flex items-center gap-1 text-[9.5px] font-semibold uppercase tracking-wide text-gray-400">
                            <svg class="h-2.5 w-2.5 text-rose-500" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <polyline points="19 12 12 19 5 12" />
                            </svg>
                            Today Out
                        </p>
                        <p class="mt-0.5 font-mono text-xs font-semibold text-rose-600">
                            −{{ number_format($account->today_outflow, 0) }}</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="mt-3 pl-2 flex items-center justify-between border-t border-gray-100 pt-2.5">
                    <p class="text-[10.5px] text-gray-400">
                        Last txn
                        @if ($account->account_id)
                            <span
                                class="font-mono text-gray-600">{{ optional(\App\Models\Transaction::whereHas('lines', fn ($l) => $l->where('account_id', $account->account_id))->latest('datetime')->value('datetime'))->format('d M Y') ?? '—' }}</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </p>
                    <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                        @can('accounts.chart.edit')
                            <button type="button" wire:click.stop="openEditModal({{ $account->id }})" title="Edit"
                                class="flex h-6 w-6 items-center justify-center rounded border border-gray-200 text-gray-400 hover:border-gray-400 hover:text-gray-700 transition">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                            </button>
                            <button type="button" x-data="livewireConfirm"
                                @click.stop="confirmAction({
                                    id: {{ $account->id }},
                                    method: 'toggleStatus',
                                    title: 'Change account status?',
                                    text: 'Status will be updated immediately.',
                                    confirmText: 'Yes, update'
                                })"
                                title="{{ $account->status === 'active' ? 'Deactivate' : 'Activate' }}"
                                class="flex h-6 w-6 items-center justify-center rounded border border-gray-200 text-gray-400 hover:border-gray-400 hover:text-gray-700 transition">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18.36 6.64a9 9 0 1 1-12.73 0" />
                                    <line x1="12" y1="2" x2="12" y2="12" />
                                </svg>
                            </button>
                        @endcan
                        @can('accounts.chart.delete')
                            <button type="button" x-data="livewireConfirm"
                                @click.stop="confirmAction({
                                    id: {{ $account->id }},
                                    method: 'deleteAccount',
                                    title: 'Delete account?',
                                    text: 'This action is permanent.',
                                    confirmText: 'Yes, delete'
                                })"
                                title="Delete"
                                class="flex h-6 w-6 items-center justify-center rounded border border-rose-200 text-rose-400 hover:bg-rose-50 hover:border-rose-400 transition">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6" />
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                    <path d="M10 11v6M14 11v6" />
                                </svg>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 rounded-xl border border-dashed border-gray-200 bg-white px-6 py-16 text-center">
                <p class="text-sm font-medium text-gray-600">No accounts found.</p>
                <p class="mt-1 text-xs text-gray-400">Try adjusting your filters or add a new account.</p>
            </div>
        @endforelse

        {{-- Add card --}}
        @can('accounts.chart.edit')
            <div wire:click="openCreateModal"
                class="flex min-h-52 cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-transparent text-gray-400 transition hover:border-gray-500 hover:bg-gray-50 hover:text-gray-600">
                <div class="flex h-11 w-11 items-center justify-center rounded-full border border-current">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                </div>
                <p class="mt-2.5 text-sm font-semibold">Add bank account</p>
                <p class="mt-0.5 text-xs">Connect a new account</p>
            </div>
        @endcan
    </div>

    {{-- Pagination --}}
    @if ($accounts->hasPages())
        <div class="mt-6">{{ $accounts->links() }}</div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════════════════
         DETAIL MODAL
    ═══════════════════════════════════════════════════════════════════════════ --}}
    <div x-cloak x-data="{ open: @entangle('showDetailModal') }" x-show="open" x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        role="dialog" aria-modal="true">

        <div x-show="open" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl"
            @click.outside="open = false; $wire.closeDetailModal()">

            @if ($viewingAccount)
                @php
                    $va = $viewingAccount;
                    $vColor = $va->type_color;
                @endphp

                {{-- Modal header --}}
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-bold text-white"
                            style="background: {{ $vColor }}">
                            {{ $va->logo_initial }}
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">{{ $va->bank_name }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $va->branch ?: '—' }} ·
                                {{ $va->code ?: 'No code' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @can('accounts.chart.edit')
                            <button type="button"
                                wire:click="openEditModal({{ $va->id }}); $dispatch('close-detail')"
                                @click="open = false; $wire.closeDetailModal()"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                                Edit
                            </button>
                        @endcan
                        <button type="button" @click="open = false; $wire.closeDetailModal()"
                            class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Balance card --}}
                <div class="mx-6 mt-5 rounded-xl px-6 py-5 text-white flex items-center justify-between gap-4"
                    style="background: linear-gradient(135deg, {{ $vColor }} 0%, #1a4373 100%)">
                    <div>
                        <p class="text-[10.5px] font-semibold uppercase tracking-widest text-white/60">Current Balance
                        </p>
                        <p class="mt-2 text-4xl font-semibold tracking-tight">
                            <span
                                class="text-sm text-white/60 mr-1 align-top mt-1 inline-block">BDT</span>{{ number_format($va->computed_balance, 2) }}
                        </p>
                        <p class="mt-2 text-xs text-white/60 font-mono">A/C {{ $va->masked_ac_number }}</p>
                    </div>
                    <div class="flex gap-5 shrink-0">
                        <div class="text-right">
                            <p class="text-[9.5px] uppercase tracking-wide text-white/60">Today In</p>
                            <p class="mt-1 font-mono text-sm font-semibold text-emerald-300">
                                +{{ number_format($va->today_inflow ?? 0, 0) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9.5px] uppercase tracking-wide text-white/60">Today Out</p>
                            <p class="mt-1 font-mono text-sm font-semibold text-rose-300">
                                −{{ number_format($va->today_outflow ?? 0, 0) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Body --}}
                <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">

                    {{-- Left: Account Details --}}
                    <div>
                        <h3 class="mb-3 text-[10.5px] font-bold uppercase tracking-widest text-gray-400">Account
                            Details</h3>
                        <dl class="space-y-2.5 text-sm">
                            @foreach ([
        'Holder' => $va->holder_name,
        'Branch' => $va->branch,
        'Route Code' => $va->route_code,
        'SWIFT' => $va->swift_code,
        'Phone' => $va->phone,
        'Email' => $va->email,
        'Address' => $va->address,
    ] as $label => $value)
                                @if ($value && $value !== '—')
                                    <div class="flex gap-3">
                                        <dt class="w-24 shrink-0 text-xs text-gray-400">{{ $label }}</dt>
                                        <dd class="text-gray-700 break-words">{{ $value }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                    </div>

                    {{-- Right: COA + Notes + Files --}}
                    <div class="space-y-4">

                        {{-- COA Link --}}
                        @if ($va->account)
                            <div>
                                <h3 class="mb-2 text-[10.5px] font-bold uppercase tracking-widest text-gray-400">Chart
                                    of Accounts</h3>
                                <div
                                    class="flex items-center gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-900">
                                        <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M2.25 18.75a60.09 60.09 0 0 1 15.549-15.549" />
                                            <path d="M18.75 2.25v4.5m0-4.5h-4.5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-mono text-sm font-semibold text-gray-900">
                                            {{ $va->account->code }} — {{ $va->account->name }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ strtoupper($va->account->type?->value ?? '') }} ·
                                            {{ strtoupper($va->account->sub_type ?? '') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Notes --}}
                        @if ($va->note)
                            <div>
                                <h3 class="mb-1.5 text-[10.5px] font-bold uppercase tracking-widest text-gray-400">
                                    Notes</h3>
                                <p class="text-sm italic text-gray-600 leading-relaxed">{{ $va->note }}</p>
                            </div>
                        @endif

                        {{-- Files --}}
                        @if (!empty($va->files))
                            <div>
                                <h3 class="mb-2 text-[10.5px] font-bold uppercase tracking-widest text-gray-400">
                                    Documents
                                    <span
                                        class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[9px] text-gray-600">{{ count($va->files) }}</span>
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($va->files as $fileId)
                                        @php $fileUrl = file_path($fileId); @endphp
                                        @if ($fileUrl)
                                            <a href="{{ $fileUrl }}" target="_blank"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-600 hover:border-gray-400 hover:bg-white transition">
                                                <svg class="h-3 w-3 text-gray-400" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path
                                                        d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                    <polyline points="14 2 14 8 20 8" />
                                                </svg>
                                                File {{ $loop->iteration }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Recent transactions --}}
                @if (!empty($va->recent_transactions) && $va->recent_transactions->count())
                    <div class="border-t border-gray-100 px-6 pb-6">
                        <h3 class="mb-3 mt-4 text-[10.5px] font-bold uppercase tracking-widest text-gray-400">Recent
                            Transactions</h3>
                        <div class="overflow-hidden rounded-lg border border-gray-200">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50">
                                        <th
                                            class="px-3 py-2 text-left text-[9.5px] font-semibold uppercase tracking-wide text-gray-400">
                                            Date</th>
                                        <th
                                            class="px-3 py-2 text-left text-[9.5px] font-semibold uppercase tracking-wide text-gray-400">
                                            Notes</th>
                                        <th
                                            class="px-3 py-2 text-right text-[9.5px] font-semibold uppercase tracking-wide text-gray-400">
                                            Debit</th>
                                        <th
                                            class="px-3 py-2 text-right text-[9.5px] font-semibold uppercase tracking-wide text-gray-400">
                                            Credit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($va->recent_transactions as $txn)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 font-mono text-gray-500">
                                                {{ \Carbon\Carbon::parse($txn->datetime)->format('d M Y') }}</td>
                                            <td class="px-3 py-2 text-gray-700 max-w-[180px] truncate">
                                                {{ $txn->notes ?: ($txn->name ?: '—') }}</td>
                                            <td class="px-3 py-2 text-right font-mono font-semibold text-emerald-600">
                                                {{ $txn->acct_debit > 0 ? number_format($txn->acct_debit, 2) : '—' }}
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono font-semibold text-rose-600">
                                                {{ $txn->acct_credit > 0 ? number_format($txn->acct_credit, 2) : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            @endif {{-- end viewingAccount --}}
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════════════════════════════
         DEPOSIT / OPENING BALANCE MODAL
    ═══════════════════════════════════════════════════════════════════════════ --}}
    <div x-cloak x-data="{ open: @entangle('showDepositModal') }" x-show="open" x-transition
        x-on:keydown.escape.window="open = false; $wire.closeDepositModal()"
        class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">

        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl"
            @click.outside="open = false; $wire.closeDepositModal()">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-900">Deposit / Opening Balance</h2>
                <button type="button" @click="open = false; $wire.closeDepositModal()"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="space-y-4 px-6 py-5">

                {{-- Bank Account --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">Bank / Cash Account <span
                            class="text-red-500">*</span></label>
                    <select wire:model.live="deposit_bank_account_id"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">— Select account —</option>
                        @foreach ($allBankAccounts as $ba)
                            <option value="{{ $ba->id }}">
                                {{ $ba->bank_name }}
                                @if ($ba->ac_number)
                                    · {{ $ba->ac_number }}
                                @endif
                                — {{ $ba->account?->code }} {{ $ba->account?->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($allBankAccounts->isEmpty())
                        <p class="mt-1 text-xs text-amber-600">No active accounts with a linked Chart of Accounts
                            entry. Link COA accounts in Bank settings first.</p>
                    @endif
                    @error('deposit_bank_account_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Transaction Type --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">Transaction Type <span
                            class="text-red-500">*</span></label>
                    <select wire:model.live="deposit_source_type"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach ($depositSourceTypes as $st)
                            <option value="{{ $st->value }}">{{ $st->label() }}</option>
                        @endforeach
                    </select>
                    @error('deposit_source_type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category --}}
                @if ($depositCategories->isNotEmpty())
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Category</label>
                        <select wire:model.live="deposit_category_id"
                            class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                            <option value="">— None —</option>
                            @foreach ($depositCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Amount + Date --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Amount <span
                                class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" wire:model.lazy="deposit_amount"
                            placeholder="0.00"
                            class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-right text-sm focus:border-indigo-500 focus:outline-none">
                        @error('deposit_amount')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Date <span
                                class="text-red-500">*</span></label>
                        <input type="date" wire:model.lazy="deposit_date"
                            class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none flatpickr-only-date">
                        @error('deposit_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">Description <span
                            class="text-red-500">*</span></label>
                    <input type="text" wire:model.lazy="deposit_description"
                        placeholder="e.g. Opening balance deposit"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('deposit_description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                <button type="button" @click="open = false; $wire.closeDepositModal()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="button" wire:click="createDeposit"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                    Submit to Banking
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         CREATE / EDIT MODAL
    ═══════════════════════════════════════════════════════════════════════════ --}}
    <div x-cloak x-data="{ open: @entangle('showFormModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl overflow-y-auto max-h-[90vh]">

            <div class="flex items-start justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900">{{ $editingId ? 'Edit Account' : 'Add Account' }}</h2>
                <button type="button" @click="open = false; $wire.closeFormModal()"
                    class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                {{-- Account Type --}}
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Account Type <span
                            class="text-rose-500">*</span></label>
                    <div x-data="{ type: @entangle('type') }" class="mt-1.5 flex flex-wrap gap-2">
                        @foreach ($types as $typeOption)
                            <label class="cursor-pointer" wire:key="type-{{ $typeOption->value }}">
                                <input type="radio" wire:model.live="type" value="{{ $typeOption->value }}"
                                    class="sr-only">

                                <span :class="type === '{{ $typeOption->value }}' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-400'"
                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition">
                                    {{ $typeOption->label() }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('type')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Code --}}
                <div>
                    <label class="text-sm font-medium text-gray-700">Code</label>
                    <input type="text" wire:model.defer="code" placeholder="e.g. DBBL-SC-01"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('code')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Bank / Account Name --}}
                <div>
                    <label class="text-sm font-medium text-gray-700">
                        {{ $type === 'bank' ? 'Bank Name' : ($type === 'cash' ? 'Cash Name / Label' : ($type === 'mfs' ? 'MFS Provider' : 'Wallet / Gateway Name')) }}
                        <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model.defer="bank_name" placeholder="Name"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('bank_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- A/C Number (skip for cash) --}}
                @if ($type !== 'cash')
                    <div>
                        <label class="text-sm font-medium text-gray-700">Account Number</label>
                        <input type="text" wire:model.defer="ac_number" placeholder="Account number"
                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @error('ac_number')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Holder Name --}}
                <div>
                    <label class="text-sm font-medium text-gray-700">Holder Name <span
                            class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="holder_name" placeholder="Account holder"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('holder_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Branch --}}
                <div>
                    <label class="text-sm font-medium text-gray-700">Branch / Location</label>
                    <input type="text" wire:model.defer="branch" placeholder="Branch or location"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('branch')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Route / SWIFT (bank only) --}}
                @if ($type === 'bank')
                    <div>
                        <label class="text-sm font-medium text-gray-700">Route Code</label>
                        <input type="text" wire:model.defer="route_code" placeholder="Route code"
                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">SWIFT Code</label>
                        <input type="text" wire:model.defer="swift_code" placeholder="SWIFT code"
                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    </div>
                @endif

                {{-- Phone --}}
                <div>
                    <label class="text-sm font-medium text-gray-700">Phone</label>
                    <input type="text" wire:model.defer="phone" placeholder="Phone number"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                {{-- Email --}}
                <div>
                    <label class="text-sm font-medium text-gray-700">Email</label>
                    <input type="email" wire:model.defer="email" placeholder="Email address"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                </div>

                {{-- Status --}}
                <div>
                    <label class="text-sm font-medium text-gray-700">Status</label>
                    <select wire:model.defer="status"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                {{-- COA Account --}}
                <div>
                    <label class="text-sm font-medium text-gray-700">Chart of Accounts Link</label>
                    <select wire:model.defer="account_id"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">— Select Account —</option>
                        @foreach ($assetAccounts as $assetAccount)
                            <option value="{{ $assetAccount->id }}">{{ $assetAccount->name }}
                                ({{ $assetAccount->code }})</option>
                        @endforeach
                    </select>
                    @error('account_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Address --}}
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Address</label>
                    <textarea wire:model.defer="address" rows="2" placeholder="Address"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                </div>

                {{-- Note --}}
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Note</label>
                    <textarea wire:model.defer="note" rows="2" placeholder="Internal note"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                </div>

                {{-- Files (media picker) --}}
                <div class="sm:col-span-2">
                    <x-media-picker-field field="files" label="Documents / Attachments" :value="$files"
                        placeholder="Attach documents" :multiple="true" type="any" />
                </div>

                {{-- Actions --}}
                <div class="sm:col-span-2 mt-2 flex justify-end gap-2">
                    <button type="button" @click="open = false; $wire.closeFormModal()"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 transition">
                        {{ $editingId ? 'Update Account' : 'Save Account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
