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

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Cash Balance</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format((float) $totalCashBalance, 3) }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Bank Balance</p>
            <p class="mt-1 text-2xl font-semibold text-blue-700">{{ number_format((float) $totalBankBalance, 3) }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm sm:col-span-2 xl:col-span-2">
            <p class="text-xs text-gray-500">Cash &amp; Bank Accounts</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @forelse ($cashBankAccounts as $balanceAccount)
                    <span class="inline-flex rounded-lg border border-gray-200 px-2.5 py-1 text-xs text-gray-700">
                        {{ $balanceAccount->name }}: {{ number_format((float) $balanceAccount->computed_balance, 3) }}
                    </span>
                @empty
                    <span class="text-xs text-gray-500">No cash or bank accounts found.</span>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div class="md:col-span-2">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search by code or name"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div>
                    <select wire:model.live="typeFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Types</option>
                        @foreach ($types as $accountType)
                            <option value="{{ $accountType->value }}">{{ $accountType->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select wire:model.live="statusFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div>
                    @can('accounts.chart.create')
                        <button
                            type="button"
                            wire:click="openCreateModal"
                            class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800"
                        >
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Account
                        </button>
                    @endcan
                </div>
            </div>
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

    <div x-cloak x-data="{ open: @entangle('showFormModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">{{ $editingId ? 'Edit Account' : 'Create Account' }}</h2>
                <button type="button" @click="open = false; $wire.closeFormModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Code</label>
                    <input type="text" wire:model.defer="code" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Optional code">
                    @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="name" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Account name">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Type <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="type" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach ($types as $accountType)
                            <option value="{{ $accountType->value }}">{{ $accountType->label() }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Sub Type</label>
                    <select wire:model.defer="sub_type" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                       <option value="">Select sub type</option>
                        @foreach (\App\Enums\Accounts\AccountSubType::options() as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    @error('sub_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>


                <div>
                    <label class="text-sm font-medium text-gray-700">Parent Account</label>
                    <select wire:model.defer="parent_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">No Parent</option>
                        @foreach ($parentOptions as $parentOption)
                            <option value="{{ $parentOption->id }}">
                                {{ $parentOption->name }} ({{ $parentOption->type?->label() ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Status</label>
                    <div class="mt-2 flex h-10 items-center gap-2 rounded-lg border border-gray-300 px-3">
                        <input id="account_active" type="checkbox" wire:model.defer="is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="account_active" class="text-sm text-gray-700">Active account</label>
                    </div>
                    @error('is_active') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Allowed References</label>
                    <p class="mt-1 text-xs text-gray-500">Choose which reference types can be used with this account.</p>

                    <div class="mt-2 grid grid-cols-1 gap-2 rounded-lg border border-gray-300 p-3 sm:grid-cols-2">
                        @forelse ($referenceOptions as $referenceKey => $referenceLabel)
                            <label class="flex items-center gap-3 rounded-lg px-2 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                                <input
                                    type="checkbox"
                                    wire:model.defer="allowed_reference_keys"
                                    value="{{ $referenceKey }}"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                <span>{{ $referenceLabel }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">No reference types configured.</p>
                        @endforelse
                    </div>

                    @error('allowed_reference_keys') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    @error('allowed_reference_keys.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 mt-2 flex justify-end gap-2">
                    <button type="button" @click="open = false; $wire.closeFormModal()" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                        {{ $editingId ? 'Update Account' : 'Save Account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
