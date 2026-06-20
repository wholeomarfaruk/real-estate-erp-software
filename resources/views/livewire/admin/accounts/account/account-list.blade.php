<div x-data x-init="$store.pageName = { name: 'Chart of Accounts', slug: 'accounts-chart' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Chart of Accounts</h1>
            <p class="text-sm text-gray-500">Manage account hierarchy, status, and balances used in accounting entries.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Chart of Accounts</li>
            </ol>
        </nav>
    </div>

    @include('livewire.admin.accounts.banking.partials.nav-tabs', ['active' => 'chart-of-accounts'])

    {{-- ── Stats bar ─────────────────────────────────────────────────────── --}}
    @php
        $liquidity   = (float) $totalCashBalance + (float) $totalBankBalance;
        $cbCount     = $cashBankAccounts->count();
    @endphp
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {{-- Total Cash --}}
        <div class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500">Total Cash Balance</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-700">{{ number_format((float) $totalCashBalance, 2) }}</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                </span>
            </div>
            <span class="absolute inset-x-0 bottom-0 h-1 bg-emerald-500/80"></span>
        </div>

        {{-- Total Bank --}}
        <div class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500">Total Bank Balance</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-blue-700">{{ number_format((float) $totalBankBalance, 2) }}</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V10m4 11V10m6 11V10m4 11V10M2 10l10-7 10 7"/></svg>
                </span>
            </div>
            <span class="absolute inset-x-0 bottom-0 h-1 bg-blue-500/80"></span>
        </div>

        {{-- Combined liquidity --}}
        <div class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500">Combined Liquidity</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900">{{ number_format($liquidity, 2) }}</p>
                    <p class="mt-1 text-[11px] text-gray-400">Cash + Bank across {{ $cbCount }} {{ \Illuminate\Support\Str::plural('account', $cbCount) }}</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 14 3-3 3 3 5-5"/></svg>
                </span>
            </div>
            <span class="absolute inset-x-0 bottom-0 h-1 bg-gray-800/70"></span>
        </div>

        {{-- Cash & Bank accounts breakdown --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-gray-500">Cash &amp; Bank Accounts</p>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 font-mono text-[10px] font-semibold text-gray-600">{{ $cbCount }}</span>
            </div>
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                @forelse ($cashBankAccounts as $balanceAccount)
                    @php $isCash = $balanceAccount->type?->value === \App\Enums\Accounts\AccountType::CASH->value; @endphp
                    <span class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-[11px] {{ $isCash ? 'border-emerald-100 bg-emerald-50/60 text-emerald-700' : 'border-blue-100 bg-blue-50/60 text-blue-700' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $isCash ? 'bg-emerald-500' : 'bg-blue-500' }}"></span>
                        {{ $balanceAccount->name }}
                        <span class="font-mono font-semibold">{{ number_format((float) $balanceAccount->computed_balance, 2) }}</span>
                    </span>
                @empty
                    <span class="text-xs text-gray-400">No cash or bank accounts found.</span>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        {{-- ── Filter / toolbar ──────────────────────────────────────────── --}}
        @php $hasFilters = trim($search) !== '' || $typeFilter !== '' || $statusFilter !== ''; @endphp
        <div class="flex flex-col gap-3 px-5 py-4 sm:px-6 sm:py-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                {{-- Search --}}
                <div class="relative w-full sm:max-w-xs">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search by code or name"
                        class="h-11 w-full rounded-lg border border-gray-300 pl-9 pr-9 text-sm text-gray-800 transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none"
                    >
                    @if (trim($search) !== '')
                        <button type="button" wire:click="$set('search', '')" class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-0.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Clear search">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    @endif
                </div>

                {{-- Type --}}
                <select wire:model.live="typeFilter" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none sm:w-44">
                    <option value="">All Types</option>
                    @foreach ($types as $accountType)
                        <option value="{{ $accountType->value }}">{{ $accountType->label() }}</option>
                    @endforeach
                </select>

                {{-- Status --}}
                <select wire:model.live="statusFilter" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none sm:w-40">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                {{-- Reset --}}
                @if ($hasFilters)
                    <button type="button" wire:click="$set('search', ''); $set('typeFilter', ''); $set('statusFilter', '')"
                        class="inline-flex h-11 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/></svg>
                        Reset
                    </button>
                @endif
            </div>

            {{-- Add account --}}
            @can('accounts.chart.create')
                <button
                    type="button"
                    wire:click="openCreateModal"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800"
                >
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Account
                </button>
            @endcan
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="space-y-4">
                @forelse ($tree as $group)
                    @php
                        $groupEnum = $group->group;
                        $accent = match ($groupEnum?->value) {
                            'asset'     => ['ring' => 'ring-emerald-200', 'bar' => 'bg-emerald-500', 'chip' => 'bg-emerald-50 text-emerald-700', 'text' => 'text-emerald-700'],
                            'liability' => ['ring' => 'ring-rose-200',    'bar' => 'bg-rose-500',    'chip' => 'bg-rose-50 text-rose-700',    'text' => 'text-rose-700'],
                            'equity'    => ['ring' => 'ring-violet-200',  'bar' => 'bg-violet-500',  'chip' => 'bg-violet-50 text-violet-700', 'text' => 'text-violet-700'],
                            'income'    => ['ring' => 'ring-blue-200',    'bar' => 'bg-blue-500',    'chip' => 'bg-blue-50 text-blue-700',    'text' => 'text-blue-700'],
                            'expense'   => ['ring' => 'ring-amber-200',   'bar' => 'bg-amber-500',   'chip' => 'bg-amber-50 text-amber-700',  'text' => 'text-amber-700'],
                            default     => ['ring' => 'ring-gray-200',    'bar' => 'bg-gray-400',    'chip' => 'bg-gray-100 text-gray-700',   'text' => 'text-gray-700'],
                        };
                    @endphp

                    <div x-data="{ open: true }" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm ring-1 {{ $accent['ring'] }}">
                        {{-- Group header --}}
                        <div class="flex items-center gap-3 px-4 py-3 sm:px-5">
                            <span class="h-8 w-1.5 shrink-0 rounded-full {{ $accent['bar'] }}"></span>

                            <button type="button" @click="open = !open" class="flex min-w-0 flex-1 items-center gap-2 text-left">
                                <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="open ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 5.23a.75.75 0 0 1 1.06.02l4 4.25a.75.75 0 0 1 0 1.04l-4 4.25a.75.75 0 1 1-1.08-1.04L10.69 10 7.23 6.29a.75.75 0 0 1-.02-1.06Z" clip-rule="evenodd" />
                                </svg>
                                <span class="truncate text-base font-semibold text-gray-800">{{ $group->name }}</span>
                                <span class="hidden rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide sm:inline {{ $accent['chip'] }}">
                                    {{ $groupEnum?->label() ?? 'Group' }}
                                </span>
                                @if ($group->is_locked)
                                    <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                @endif
                                <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] text-gray-500">{{ $group->treeChildren->count() }}</span>
                            </button>

                            <div class="shrink-0 text-right">
                                <p class="text-[10px] uppercase tracking-wide text-gray-400">Balance</p>
                                <p class="font-mono text-sm font-semibold {{ $accent['text'] }}">{{ number_format((float) $group->rollup_balance, 2) }}</p>
                            </div>

                            @include('livewire.admin.accounts.account.partials.account-row-actions', ['account' => $group])
                        </div>

                        {{-- Children --}}
                        <div x-show="open" x-cloak x-transition.opacity class="divide-y divide-gray-50 border-t border-gray-100">
                            @forelse ($group->treeChildren as $child)
                                @include('livewire.admin.accounts.account.partials.account-tree-row', ['node' => $child])
                            @empty
                                <p class="px-5 py-4 text-xs italic text-gray-400">No child accounts.</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-5 py-16 text-center">
                        <p class="text-sm font-medium text-gray-700">No accounts found.</p>
                        <p class="mt-1 text-xs text-gray-500">Try changing filters or add a new account.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showFormModal') }" x-show="open" x-transition
         class="fixed inset-0 z-50 grid place-content-center bg-gray-900/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
        @php
            $groupAccent = [
                'asset'     => ['dot' => 'bg-emerald-500', 'chip' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
                'liability' => ['dot' => 'bg-rose-500',    'chip' => 'bg-rose-50 text-rose-700 ring-rose-200'],
                'equity'    => ['dot' => 'bg-violet-500',  'chip' => 'bg-violet-50 text-violet-700 ring-violet-200'],
                'income'    => ['dot' => 'bg-blue-500',    'chip' => 'bg-blue-50 text-blue-700 ring-blue-200'],
                'expense'   => ['dot' => 'bg-amber-500',   'chip' => 'bg-amber-50 text-amber-700 ring-amber-200'],
            ];
        @endphp
        <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-gray-200">

            {{-- Header --}}
            <div class="flex items-start justify-between border-b border-gray-100 px-6 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gray-900 text-white">
                        <svg class="h-4.5 w-4.5" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-gray-900">{{ $editingId ? 'Edit Account' : 'Create Account' }}</h2>
                        <p class="text-xs text-gray-500">{{ $editingId ? 'Update this chart-of-accounts entry' : 'Add a new entry to the chart of accounts' }}</p>
                    </div>
                </div>
                <button type="button" @click="open = false; $wire.closeFormModal()" class="-me-2 -mt-1 rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="flex min-h-0 flex-1 flex-col">
                <div class="flex-1 overflow-y-auto px-6 py-5">

                    {{-- ── Basics ── --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model.defer="name" autofocus class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none" placeholder="e.g. Petty Cash, Sales Revenue">
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Code</label>
                            <input type="text" wire:model.defer="code" class="h-10 w-full rounded-lg border border-gray-300 px-3 font-mono text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none" placeholder="Optional · e.g. 1001">
                            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            @php $selectedGroup = $this->group; @endphp
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Group <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                @if($selectedGroup && isset($groupAccent[$selectedGroup]))
                                    <span class="pointer-events-none absolute left-3 top-1/2 h-2.5 w-2.5 -translate-y-1/2 rounded-full {{ $groupAccent[$selectedGroup]['dot'] }}"></span>
                                @endif
                                <select wire:model.live="group" class="h-10 w-full rounded-lg border border-gray-300 pr-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none {{ $selectedGroup && isset($groupAccent[$selectedGroup]) ? 'pl-8' : 'pl-3' }}">
                                    <option value="">— Select group —</option>
                                    @foreach ($groupOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('group') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Type <span class="text-rose-500">*</span></label>
                            <select wire:model.defer="type" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none">
                                @foreach ($types as $accountType)
                                    <option value="{{ $accountType->value }}">{{ $accountType->label() }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Sub Type</label>
                            <select wire:model.defer="sub_type" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none">
                                <option value="">Select sub type</option>
                                @foreach (\App\Enums\Accounts\AccountSubType::options() as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('sub_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Parent Account</label>
                            <select wire:model.defer="parent_id" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none">
                                <option value="">No Parent (top-level)</option>
                                @foreach ($parentOptions as $parentOption)
                                    <option value="{{ $parentOption->id }}">{{ $parentOption->name }} ({{ $parentOption->type?->label() ?? '-' }})</option>
                                @endforeach
                            </select>
                            @error('parent_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- ── Status ── --}}
                    <label for="account_active" class="mt-4 flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-gray-50/60 px-4 py-3">
                        <span>
                            <span class="block text-sm font-medium text-gray-800">Active account</span>
                            <span class="block text-xs text-gray-500">Inactive accounts are hidden from selection in new entries.</span>
                        </span>
                        <input id="account_active" type="checkbox" wire:model.defer="is_active" class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    </label>

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50/60 px-6 py-3">
                    <button type="button" @click="open = false; $wire.closeFormModal()" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 disabled:opacity-60">
                        <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        {{ $editingId ? 'Update Account' : 'Save Account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
