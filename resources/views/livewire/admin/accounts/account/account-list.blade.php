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
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Code</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Account</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Parent</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Created</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($accounts as $account)
                                <tr>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $account->code ?: 'N/A' }}</td>

                                    <td class="px-5 py-4 text-sm text-gray-800 font-medium" style="padding-left: {{ 20 + ((int) ($account->depth ?? 0) * 16) }}px;">
                                        {{ $account->name }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $account->parent?->name ?: 'Root' }}</td>

                                    <td class="px-5 py-4">
                                        <x-account-type-badge :type="$account->type" />
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $account->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">{{ optional($account->created_at)->format('d M, Y') }}</td>

                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <span class="sr-only">Open actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-52 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('accounts.chart.edit')
                                                    <button
                                                        type="button"
                                                        wire:click="openEditModal({{ $account->id }})"
                                                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100"
                                                    >
                                                        Edit
                                                    </button>

                                                    <button
                                                        type="button"
                                                        x-data="livewireConfirm"
                                                        @click="confirmAction({
                                                            id: {{ $account->id }},
                                                            method: 'toggleStatus',
                                                            title: 'Change account status?',
                                                            text: 'Account status will be updated immediately.',
                                                            confirmText: 'Yes, update status'
                                                        })"
                                                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100"
                                                    >
                                                        {{ $account->is_active ? 'Mark Inactive' : 'Mark Active' }}
                                                    </button>
                                                @endcan

                                                @can('accounts.chart.delete')
                                                    <button
                                                        type="button"
                                                        x-data="livewireConfirm"
                                                        @click="confirmAction({
                                                            id: {{ $account->id }},
                                                            method: 'deleteAccount',
                                                            title: 'Delete account?',
                                                            text: 'Used accounts cannot be deleted and this action is permanent.',
                                                            confirmText: 'Yes, delete account'
                                                        })"
                                                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50"
                                                    >
                                                        Delete
                                                    </button>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No accounts found.</p>
                                        <p class="mt-1 text-xs text-gray-500">Try changing filters or add a new account.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($accounts->hasPages())
                <div class="mt-6">
                    {{ $accounts->links() }}
                </div>
            @endif
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
                    <label class="text-sm font-medium text-gray-700">Type <span class="text-rose-500">*</span></label>
                    <select wire:model.defer="type" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        @foreach ($types as $accountType)
                            <option value="{{ $accountType->value }}">{{ $accountType->label() }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="name" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Account name">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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
