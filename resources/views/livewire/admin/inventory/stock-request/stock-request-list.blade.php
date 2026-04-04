<div x-data x-init="$store.pageName = { name: 'Stock Request / Requisition', slug: 'stock-requests' }">
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
                <li class="text-sm text-gray-800">Stock Request</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Total Requests</p>
            <p class="mt-2 text-2xl font-semibold text-gray-800">{{ number_format($totalRequests) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Pending</p>
            <p class="mt-2 text-2xl font-semibold text-amber-700">{{ number_format($pendingRequests) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Approved</p>
            <p class="mt-2 text-2xl font-semibold text-indigo-700">{{ number_format($approvedRequests) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Partially Fulfilled</p>
            <p class="mt-2 text-2xl font-semibold text-blue-700">{{ number_format($partialRequests) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Fulfilled</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ number_format($fulfilledRequests) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-8">
                <div class="md:col-span-2">
                    <label for="search" class="sr-only">Search</label>
                    <input
                        id="search"
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Request no or remarks"
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
                    <label for="requesterStoreFilter" class="sr-only">Requester Store</label>
                    <select id="requesterStoreFilter" wire:model.live="requesterStoreFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">Requester Store</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sourceStoreFilter" class="sr-only">Source Store</label>
                    <select id="sourceStoreFilter" wire:model.live="sourceStoreFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">Source Store</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="priorityFilter" class="sr-only">Priority</label>
                    <select id="priorityFilter" wire:model.live="priorityFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Priority</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="projectFilter" class="sr-only">Project</label>
                    <select id="projectFilter" wire:model.live="projectFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <input type="date" wire:model.live="dateFrom" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <input type="date" wire:model.live="dateTo" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            @can('inventory.stock_request.create')
                <div class="mt-4">
                    <a href="{{ route('admin.inventory.stock-requests.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Stock Request
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
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Request</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Route</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Project</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Priority</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Qty</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($stockRequests as $stockRequest)
                                @php
                                    $statusClass = match ($stockRequest->status?->value) {
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'approved' => 'bg-indigo-100 text-indigo-700',
                                        'partially_fulfilled' => 'bg-blue-100 text-blue-700',
                                        'fulfilled' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        'cancelled' => 'bg-zinc-100 text-zinc-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };

                                    $priorityClass = match ($stockRequest->priority?->value) {
                                        'urgent' => 'bg-red-100 text-red-700',
                                        'high' => 'bg-orange-100 text-orange-700',
                                        'low' => 'bg-green-100 text-green-700',
                                        default => 'bg-blue-100 text-blue-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-medium text-gray-800">{{ $stockRequest->request_no }}</p>
                                        <p class="text-xs text-gray-500">{{ optional($stockRequest->request_date)->format('d M, Y') }}</p>
                                        <p class="text-xs text-gray-500">By: {{ $stockRequest->requester?->name ?? 'N/A' }}</p>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>{{ $stockRequest->requesterStore?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">From: {{ $stockRequest->sourceStore?->name ?? 'Not selected' }}</p>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>{{ $stockRequest->project?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $stockRequest->project?->code ?? '' }}</p>
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $priorityClass }}">
                                            {{ $stockRequest->priority?->label() ?? 'N/A' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>Req: {{ number_format((float) ($stockRequest->total_requested_qty ?? 0), 3) }}</p>
                                        <p class="text-xs text-gray-500">Fulfilled: {{ number_format((float) ($stockRequest->total_fulfilled_qty ?? 0), 3) }}</p>
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                            {{ $stockRequest->status?->label() ?? 'N/A' }}
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

                                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-56 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('inventory.stock_request.view')
                                                    <a href="{{ route('admin.inventory.stock-requests.view', $stockRequest) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        View
                                                    </a>
                                                @endcan

                                                @can('inventory.stock_request.update')
                                                    @if ($stockRequest->status?->value === 'draft')
                                                        <a href="{{ route('admin.inventory.stock-requests.edit', $stockRequest) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Edit
                                                        </a>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock_request.submit')
                                                    @if ($stockRequest->status?->value === 'draft')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $stockRequest->id }},
                                                                method: 'submitRequest',
                                                                title: 'Submit this stock request?',
                                                                text: 'This will move request to pending approval.',
                                                                confirmText: 'Yes, submit'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Submit
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock_request.approve')
                                                    @if ($stockRequest->status?->value === 'pending')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $stockRequest->id }},
                                                                method: 'approveRequest',
                                                                title: 'Approve this stock request?',
                                                                text: 'Approved request can be fulfilled through transfers.',
                                                                confirmText: 'Yes, approve'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Approve
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock_request.reject')
                                                    @if ($stockRequest->status?->value === 'pending')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $stockRequest->id }},
                                                                method: 'rejectRequest',
                                                                title: 'Reject this stock request?',
                                                                text: 'Rejected request cannot be edited or fulfilled.',
                                                                confirmText: 'Yes, reject'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Reject
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock_request.update')
                                                    @if (in_array($stockRequest->status?->value, ['draft', 'pending', 'approved'], true))
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $stockRequest->id }},
                                                                method: 'cancelRequest',
                                                                title: 'Cancel this stock request?',
                                                                text: 'Cancelled request cannot be edited.',
                                                                confirmText: 'Yes, cancel'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Cancel
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock.transfer.create')
                                                    @if (in_array($stockRequest->status?->value, ['approved', 'partially_fulfilled'], true))
                                                        <a href="{{ route('admin.inventory.stock-transfers.create', ['stock_request_id' => $stockRequest->id]) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Create Transfer
                                                        </a>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock_request.approve')
                                                    @if (in_array($stockRequest->status?->value, ['approved', 'partially_fulfilled'], true))
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $stockRequest->id }},
                                                                method: 'recalculateFulfillment',
                                                                title: 'Recalculate fulfillment?',
                                                                text: 'This will re-sync fulfilled quantities from linked completed transfers.',
                                                                confirmText: 'Yes, recalculate'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Recalculate Fulfillment
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.stock_request.delete')
                                                    @if ($stockRequest->status?->value === 'draft')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $stockRequest->id }},
                                                                method: 'deleteRequest',
                                                                title: 'Delete this draft request?',
                                                                text: 'This draft stock request will be deleted permanently.',
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
                                    <td colspan="7" class="px-5 py-12 text-center">
                                        <div class="mx-auto max-w-sm">
                                            <p class="text-sm font-medium text-gray-700">No stock requests found</p>
                                            <p class="mt-1 text-xs text-gray-500">Create a request or adjust your filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($stockRequests->hasPages())
                <div class="mt-6">
                    {{ $stockRequests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
