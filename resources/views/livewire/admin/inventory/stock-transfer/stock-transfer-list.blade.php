<div x-data x-init="$store.pageName = { name: 'Stock Transfer', slug: 'stock-transfers' }">
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
                <li class="text-sm text-gray-800">Stock Transfer</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Total Transfers</p>
            <p class="mt-2 text-2xl font-semibold text-gray-800">{{ number_format($totalTransfers) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Draft</p>
            <p class="mt-2 text-2xl font-semibold text-amber-700">{{ number_format($draftTransfers) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Approved</p>
            <p class="mt-2 text-2xl font-semibold text-indigo-700">{{ number_format($approvedTransfers) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Completed</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ number_format($completedTransfers) }}</p>
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
                        placeholder="Transfer no or remarks"
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
                    <label for="senderStoreFilter" class="sr-only">Sender Store</label>
                    <select id="senderStoreFilter" wire:model.live="senderStoreFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Sender Stores</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="receiverStoreFilter" class="sr-only">Receiver Store</label>
                    <select id="receiverStoreFilter" wire:model.live="receiverStoreFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Receiver Stores</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <input type="date" wire:model.live="dateFrom" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <input type="date" wire:model.live="dateTo" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            @can('inventory.stock.transfer.create')
                <div class="mt-4">
                    <a href="{{ route('admin.inventory.stock-transfers.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Transfer
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
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Transfer</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Route</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Items</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Total Value</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($transfers as $transfer)
                                @php
                                    $statusClass = match ($transfer->status?->value) {
                                        'requested' => 'bg-blue-100 text-blue-700',
                                        'approved' => 'bg-indigo-100 text-indigo-700',
                                        'completed' => 'bg-green-100 text-green-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-medium text-gray-800">{{ $transfer->transfer_no }}</p>
                                        <p class="text-xs text-gray-500">{{ optional($transfer->transfer_date)->format('d M, Y') }}</p>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>{{ $transfer->senderStore?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">to {{ $transfer->receiverStore?->name ?? 'N/A' }}</p>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ number_format((int) $transfer->items_count) }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ number_format((float) ($transfer->transfer_total ?? 0), 2) }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                            {{ $transfer->status?->label() ?? 'N/A' }}
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

                                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-52 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('inventory.stock.transfer.view')
                                                    <a href="{{ route('admin.inventory.stock-transfers.view', $transfer) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        View
                                                    </a>
                                                @endcan

                                                @can('inventory.stock.transfer.update')
                                                    @if ($transfer->status?->value === 'draft')
                                                        <a href="{{ route('admin.inventory.stock-transfers.edit', $transfer) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Edit
                                                        </a>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock.transfer.request')
                                                    @if ($transfer->status?->value === 'draft')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $transfer->id }},
                                                                method: 'requestTransfer',
                                                                title: 'Request this transfer?',
                                                                text: 'This will send transfer for approval.',
                                                                confirmText: 'Yes, request'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Request
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock.transfer.approve')
                                                    @if ($transfer->status?->value === 'requested')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $transfer->id }},
                                                                method: 'approveTransfer',
                                                                title: 'Approve this transfer?',
                                                                text: 'Approved transfer can be completed.',
                                                                confirmText: 'Yes, approve'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Approve
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock.transfer.complete')
                                                    @if ($transfer->status?->value === 'approved')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $transfer->id }},
                                                                method: 'completeTransfer',
                                                                title: 'Complete this transfer?',
                                                                text: 'This will update stock balances and ledger.',
                                                                confirmText: 'Yes, complete'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Complete
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock.transfer.update')
                                                    @if (in_array($transfer->status?->value, ['draft', 'requested', 'approved'], true))
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $transfer->id }},
                                                                method: 'cancelTransfer',
                                                                title: 'Cancel this transfer?',
                                                                text: 'Cancelled transfer will not affect stock.',
                                                                confirmText: 'Yes, cancel'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Cancel
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock.transfer.delete')
                                                    @if ($transfer->status?->value === 'draft')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $transfer->id }},
                                                                method: 'deleteTransfer',
                                                                title: 'Delete this draft transfer?',
                                                                text: 'This action cannot be undone.',
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
                                            <p class="text-sm font-medium text-gray-700">No transfers found</p>
                                            <p class="mt-1 text-xs text-gray-500">Create a transfer or refine filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($transfers->hasPages())
                <div class="mt-6">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
