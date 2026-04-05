<div x-data x-init="$store.pageName = { name: 'Supplier Management', slug: 'suppliers' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>

        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.dashboard') }}">
                        Dashboard
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">Suppliers</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                <div class="md:col-span-3">
                    <label for="search" class="sr-only">Search</label>
                    <input
                        id="search"
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search by code, name, contact, phone, or address"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="statusFilter" class="sr-only">Status</label>
                    <select id="statusFilter" wire:model.live="statusFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>

                <div>
                    <label for="hasDueFilter" class="sr-only">Due Filter</label>
                    <select id="hasDueFilter" wire:model.live="hasDueFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Due States</option>
                        <option value="due">With Due</option>
                        <option value="no_due">No Due</option>
                    </select>
                </div>

                <div class="md:col-span-1">
                    @can('supplier.create')
                        <a href="{{ route('admin.supplier.suppliers.create') }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                            Create
                        </a>
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
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Name</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Contact Person</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Phone</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Email</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Address</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Opening Balance</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Current Due</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Created</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($suppliers as $supplier)
                                <tr>
                                    <td class="px-5 py-4 text-sm font-medium text-gray-800">
                                        {{ $supplier->code ?: 'N/A' }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="text-sm font-medium text-gray-800">{{ $supplier->name }}</p>
                                        @if ($supplier->company_name)
                                            <p class="text-xs text-gray-500">{{ $supplier->company_name }}</p>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $supplier->contact_person ?: 'N/A' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>{{ $supplier->phone ?: 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $supplier->alternate_phone ?: 'N/A' }}</p>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $supplier->email ?: 'N/A' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $supplier->address ? \Illuminate\Support\Str::limit($supplier->address, 36) : 'N/A' }}
                                    </td>

                                    <td class="px-5 py-4 text-right text-sm text-gray-700">
                                        {{ number_format((float) ($supplier->opening_balance ?? 0), 2) }}
                                        <p class="text-xs text-gray-500">{{ ucfirst($supplier->opening_balance_type ?: 'payable') }}</p>
                                    </td>

                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">
                                        {{ number_format((float) ($supplier->current_due ?? 0), 2) }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <x-supplier-status-badge :status="$supplier->status_label" />
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ optional($supplier->created_at)->format('d M, Y') }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <span class="sr-only">Open actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-52 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('supplier.view')
                                                    <a href="{{ route('admin.supplier.suppliers.view', $supplier) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        View
                                                    </a>
                                                @endcan

                                                @can('supplier.edit')
                                                    <a href="{{ route('admin.supplier.suppliers.edit', $supplier) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Edit
                                                    </a>
                                                @endcan

                                                @can('supplier.status.change')
                                                    @if ($supplier->is_blocked)
                                                        <button
                                                            type="button"
                                                            x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $supplier->id }},
                                                                method: 'unblockSupplier',
                                                                title: 'Unblock supplier?',
                                                                text: 'Supplier will move to inactive after unblocking.',
                                                                confirmText: 'Yes, unblock'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100"
                                                        >
                                                            Unblock
                                                        </button>
                                                    @else
                                                        <button
                                                            type="button"
                                                            x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $supplier->id }},
                                                                method: '{{ $supplier->status ? 'deactivateSupplier' : 'activateSupplier' }}',
                                                                title: 'Change supplier status?',
                                                                text: 'Supplier status will be updated immediately.',
                                                                confirmText: 'Yes, update'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100"
                                                        >
                                                            {{ $supplier->status ? 'Deactivate' : 'Activate' }}
                                                        </button>

                                                        <button
                                                            type="button"
                                                            x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $supplier->id }},
                                                                method: 'blockSupplier',
                                                                title: 'Block supplier?',
                                                                text: 'Blocked supplier will be hidden from active purchase flows.',
                                                                confirmText: 'Yes, block'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50"
                                                        >
                                                            Block
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-5 py-12 text-center">
                                        <div class="mx-auto max-w-sm">
                                            <p class="text-sm font-medium text-gray-700">No suppliers found</p>
                                            <p class="mt-1 text-xs text-gray-500">Try changing search/filter or create a new supplier.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($suppliers->hasPages())
                <div class="mt-6">
                    {{ $suppliers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
