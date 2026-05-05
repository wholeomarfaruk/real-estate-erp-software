<div x-data x-init="$store.pageName = { name: 'Manage Banks' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Manage Banks</h1>
            <p class="text-sm text-gray-500">Manage bank accounts and their details.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Manage Banks</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Bank Balance</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format((float) $totalBankBalance, 3) }}
            </p>
        </div>

    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div class="md:col-span-2">
                    <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search by code or name"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>
                <div class="md:col-span-2">
                    <select wire:model.live="statusFilter"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>

                    <button type="button" wire:click="openCreateModal"
                        class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Account
                    </button>

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
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Holder Name</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Balance</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Created</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($accounts as $account)
                                <tr>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $account->code ?: 'N/A' }}</td>

                                    <td class="px-5 py-4 text-sm text-gray-800 font-medium"
                                        style="padding-left: {{ 20 + (int) ($account->depth ?? 0) * 16 }}px;">
                                        {{ $account->bank_name }}<br>
                                        <span class="text-xs text-gray-500">({{ $account->ac_number }})</span>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $account->holder_name ?: 'N/A' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ number_format((float) $account->balance, 3) }}</td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $account->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ optional($account->created_at)->format('d M, Y') }}</td>

                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <span class="sr-only">Open actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path
                                                        d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" style="display: none;"
                                                x-transition
                                                class="absolute right-0 z-40 mt-10 w-52 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                <button type="button" wire:click="view({{ $account->id }})"
                                                    class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                    Details
                                                </button>
                                                @can('accounts.chart.edit')
                                                    <button type="button" wire:click="openEditModal({{ $account->id }})"
                                                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Edit
                                                    </button>

                                                    <button type="button" x-data="livewireConfirm"
                                                        @click="confirmAction({
                                                            id: {{ $account->id }},
                                                            method: 'toggleStatus',
                                                            title: 'Change account status?',
                                                            text: 'Account status will be updated immediately.',
                                                            confirmText: 'Yes, update status'
                                                        })"
                                                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        {{ $account->is_active ? 'Mark Inactive' : 'Mark Active' }}
                                                    </button>
                                                @endcan

                                                @can('accounts.chart.delete')
                                                    <button type="button" x-data="livewireConfirm"
                                                        @click="confirmAction({
                                                            id: {{ $account->id }},
                                                            method: 'deleteAccount',
                                                            title: 'Delete account?',
                                                            text: 'Used accounts cannot be deleted and this action is permanent.',
                                                            confirmText: 'Yes, delete account'
                                                        })"
                                                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50">
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
                                        <p class="mt-1 text-xs text-gray-500">Try changing filters or add a new account.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- @if ($accounts->hasPages())
                <div class="mt-6">
                    {{ $accounts->links() }}
                </div>
            @endif --}}
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showFormModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-lg overflow-y-auto max-h-[90vh]">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">{{ $editingId ? 'Edit Account' : 'Create Account' }}</h2>
                <button type="button" @click="open = false; $wire.closeFormModal()"
                    class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Code</label>
                    <input type="text" wire:model.defer="code"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Optional code">
                    @error('code')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Bank Name <span
                            class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="bank_name"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Bank name">
                    @error('bank_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Account Number <span
                            class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="ac_number"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Account number">
                    @error('ac_number')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Holder Name <span
                            class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="holder_name"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Holder name">
                    @error('holder_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Branch <span
                            class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="branch"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Branch">
                    @error('branch')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Route Code</label>
                    <input type="text" wire:model.defer="route_code"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Route code">
                    @error('route_code')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">SWIFT Code</label>
                    <input type="text" wire:model.defer="swift_code"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="SWIFT code">
                    @error('swift_code')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" wire:model.defer="status"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" wire:model.defer="phone"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Phone number">
                    @error('phone')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" wire:model.defer="email"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Email address">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Bank Address</label>
                    <textarea wire:model.defer="address"
                        class="mt-1 h-24 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Bank Address"></textarea>
                    @error('address')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Note</label>
                    <textarea wire:model.defer="note"
                        class="mt-1 h-24 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Note"></textarea>
                    @error('note')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Account</label>
                    <select name="account_id" id="account_id" wire:model.defer="account_id"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">Select Account</option>
                        @foreach($assetAccounts as $assetAccount)
                            <option value="{{ $assetAccount->id }}">{{ $assetAccount->name }} - {{ $assetAccount->code }}</option>
                        @endforeach
                    </select>
                    @error('account_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2 mt-2 flex justify-end gap-2">
                    <button type="button" @click="open = false; $wire.closeFormModal()"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                        {{ $editingId ? 'Update Account' : 'Save Account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
