<div x-data x-init="$store.pageName = { name: 'Stock In / Purchase Receive', slug: 'stock-receives' }">
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
                <li class="text-sm text-gray-800">Stock Receive</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Total Receives</p>
            <p class="mt-2 text-2xl font-semibold text-gray-800">{{ number_format($totalReceives) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Posted Receives</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ number_format($postedReceives) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Draft Receives</p>
            <p class="mt-2 text-2xl font-semibold text-amber-700">{{ number_format($draftReceives) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                <div class="md:col-span-2">
                    <label for="search" class="sr-only">Search</label>
                    <input
                        id="search"
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="No, PO, supplier, voucher, remarks"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="statusFilter" class="sr-only">Status</label>
                    <select id="statusFilter" wire:model.live="statusFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="storeFilter" class="sr-only">Store</label>
                    <select id="storeFilter" wire:model.live="storeFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Stores</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="supplierFilter" class="sr-only">Supplier</label>
                    <select id="supplierFilter" wire:model.live="supplierFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Suppliers</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <input type="date" wire:model.live="dateFrom" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <input type="date" wire:model.live="dateTo" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            @can('inventory.stock.receive.create')
                <div class="mt-4">
                    <a href="{{ route('admin.inventory.stock-receives.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Stock Receive
                    </a>
                </div>
            @endcan
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Receive</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Store</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Total</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($receives as $receive)
                                @php
                                    $statusClass = match ($receive->status?->value) {
                                        'posted' => 'bg-green-100 text-green-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-medium text-gray-800">{{ $receive->receive_no }}</p>
                                        <p class="text-xs text-gray-500">{{ optional($receive->receive_date)->format('d M, Y') }}</p>
                                        @if ($receive->purchaseOrder)
                                            <p class="text-xs text-indigo-600">PO: {{ $receive->purchaseOrder->po_no }}</p>
                                        @endif
                                        @if ($receive->supplier_voucher)
                                            <p class="text-xs text-gray-500">Voucher: {{ $receive->supplier_voucher }}</p>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>{{ $receive->supplier?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $receive->supplier?->phone ?? '' }}</p>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $receive->store?->name ?? 'N/A' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ number_format((float) ($receive->grand_total ?? 0), 2) }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                            {{ $receive->status?->label() ?? 'N/A' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <span class="sr-only">Open actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-48 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('inventory.stock.receive.view')
                                                    <a href="{{ route('admin.inventory.stock-receives.view', $receive) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        View
                                                    </a>
                                                @endcan

                                                @can('inventory.stock.receive.update')
                                                    @if ($receive->canEditReceive())
                                                        <a href="{{ route('admin.inventory.stock-receives.edit', $receive) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            {{ $receive->status?->value === 'posted' ? 'Adjust' : 'Edit' }}
                                                        </a>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock.receive.post')
                                                    @if ($receive->status?->value === 'draft')
                                                        <button
                                                            type="button"
                                                            x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $receive->id }},
                                                                method: 'postReceive',
                                                                title: 'Post this receive?',
                                                                text: 'This will update stock balance and ledger.',
                                                                confirmText: 'Yes, post now'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Post
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock.receive.update')
                                                    @if ($receive->status?->value === 'draft')
                                                        <button
                                                            type="button"
                                                            x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $receive->id }},
                                                                method: 'cancelReceive',
                                                                title: 'Cancel this receive?',
                                                                text: 'Cancelled receive will not affect stock.',
                                                                confirmText: 'Yes, cancel'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Cancel
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock.receive.delete')
                                                    @if ($receive->status?->value === 'draft')
                                                        <button
                                                            type="button"
                                                            x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $receive->id }},
                                                                method: 'deleteReceive',
                                                                title: 'Delete this draft?',
                                                                text: 'This draft receive will be deleted permanently.',
                                                                confirmText: 'Yes, delete'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                            Delete
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <div class="mx-auto max-w-sm">
                                            <p class="text-sm font-medium text-gray-700">No stock receive documents found</p>
                                            <p class="mt-1 text-xs text-gray-500">Create a new receive or adjust your filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($receives->hasPages())
                <div class="mt-6">
                    {{ $receives->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
