<div x-data x-init="$store.pageName = { name: 'Stock Request Details', slug: 'stock-request-view' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold">Stock Request Details</h1>

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
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.stock-requests.index') }}">
                        Stock Request
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">{{ $stockRequest->request_no }}</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Request No</p>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $stockRequest->request_no }}</h2>
                </div>
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
                <div class="space-y-2 text-right">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $priorityClass }}">
                        {{ $stockRequest->priority?->label() ?? 'N/A' }}
                    </span>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                        {{ $stockRequest->status?->label() ?? 'N/A' }}
                    </span>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-500">Request Date</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($stockRequest->request_date)->format('d M, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Requester Store</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockRequest->requesterStore?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Source Store</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockRequest->sourceStore?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Project</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockRequest->project?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Requested By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockRequest->requester?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Approved By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockRequest->approver?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Rejected By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockRequest->rejecter?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Fulfilled By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockRequest->fulfiller?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Fulfilled At</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($stockRequest->fulfilled_at)->format('d M, Y h:i A') ?? 'N/A' }}</p>
                </div>
            </div>

            @if ($stockRequest->remarks)
                <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Remarks</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $stockRequest->remarks }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Actions</h3>
            <p class="mt-1 text-xs text-gray-500">Request itself does not move stock. Transfers fulfill it.</p>

            <div class="mt-4 space-y-2">
                <a href="{{ route('admin.inventory.stock-requests.index') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Back to List
                </a>

                @can('inventory.stock_request.update')
                    @if ($stockRequest->status?->value === 'draft')
                        <a href="{{ route('admin.inventory.stock-requests.edit', $stockRequest) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Edit Draft
                        </a>
                    @endif
                @endcan

                @can('inventory.stock_request.submit')
                    @if ($stockRequest->status?->value === 'draft')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'submitRequest',
                                title: 'Submit this stock request?',
                                text: 'This will move the request to pending approval.',
                                confirmText: 'Yes, submit'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                            Submit Request
                        </button>
                    @endif
                @endcan

                @can('inventory.stock_request.approve')
                    @if ($stockRequest->status?->value === 'pending')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'approveRequest',
                                title: 'Approve this stock request?',
                                text: 'Approved request can be fulfilled via stock transfers.',
                                confirmText: 'Yes, approve'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Approve Request
                        </button>
                    @endif
                @endcan

                @can('inventory.stock_request.reject')
                    @if ($stockRequest->status?->value === 'pending')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'rejectRequest',
                                title: 'Reject this stock request?',
                                text: 'Rejected request cannot be fulfilled.',
                                confirmText: 'Yes, reject'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                            Reject Request
                        </button>
                    @endif
                @endcan

                @can('inventory.stock_request.update')
                    @if (in_array($stockRequest->status?->value, ['draft', 'pending', 'approved'], true))
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'cancelRequest',
                                title: 'Cancel this stock request?',
                                text: 'Cancelled request cannot be edited or fulfilled.',
                                confirmText: 'Yes, cancel'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-zinc-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800">
                            Cancel Request
                        </button>
                    @endif
                @endcan

                @can('inventory.stock.transfer.create')
                    @if (in_array($stockRequest->status?->value, ['approved', 'partially_fulfilled'], true))
                        <a href="{{ route('admin.inventory.stock-transfers.create', ['stock_request_id' => $stockRequest->id]) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100">
                            Create Transfer From Request
                        </a>
                    @endif
                @endcan

                @can('inventory.stock_request.approve')
                    @if (in_array($stockRequest->status?->value, ['approved', 'partially_fulfilled'], true))
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'recalculateFulfillment',
                                title: 'Recalculate fulfillment now?',
                                text: 'This will sync fulfilled quantities from linked completed transfers.',
                                confirmText: 'Yes, recalculate'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 transition hover:bg-blue-100">
                            Recalculate Fulfillment
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h3 class="text-base font-semibold text-gray-800">Fulfillment Summary</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs text-gray-500">Target Quantity</p>
                <p class="text-lg font-semibold text-gray-800">{{ number_format($targetQty, 3) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs text-gray-500">Fulfilled Quantity</p>
                <p class="text-lg font-semibold text-emerald-700">{{ number_format($fulfilledQty, 3) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs text-gray-500">Remaining Quantity</p>
                <p class="text-lg font-semibold text-indigo-700">{{ number_format($remainingQty, 3) }}</p>
            </div>
        </div>
    </div>

    @if ($stockRequest->status?->value === 'pending' && (auth()->user()?->can('inventory.stock_request.approve') || auth()->user()?->can('inventory.stock_request.reject')))
        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Approval Section</h3>
            <p class="mt-1 text-xs text-gray-500">You can adjust approved quantities before approving.</p>

            <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
                <div class="max-w-full overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Requested Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Approved Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($stockRequest->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <p class="font-medium text-gray-800">{{ $item->product?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $item->product?->sku ?? 'No SKU' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $item->quantity, 3) }}</td>
                                    <td class="px-4 py-3 min-w-[160px]">
                                        @can('inventory.stock_request.approve')
                                            <input type="number" min="0" step="0.001" wire:model="approvalQuantities.{{ $item->id }}"
                                                class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                        @else
                                            <p class="text-sm text-gray-700">{{ number_format((float) ($item->approved_quantity ?? $item->quantity), 3) }}</p>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                @can('inventory.stock_request.approve')
                    <div>
                        <label for="approvalRemarks" class="text-sm font-medium text-gray-700">Approval Remarks (optional)</label>
                        <textarea id="approvalRemarks" wire:model="approvalRemarks" rows="2"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                            placeholder="Any approval note"></textarea>
                    </div>
                @endcan

                @can('inventory.stock_request.reject')
                    <div>
                        <label for="rejectionRemarks" class="text-sm font-medium text-gray-700">Rejection Remarks (optional)</label>
                        <textarea id="rejectionRemarks" wire:model="rejectionRemarks" rows="2"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                            placeholder="Reason for rejection"></textarea>
                    </div>
                @endcan
            </div>
        </div>
    @endif

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h3 class="text-base font-semibold text-gray-800">Linked Transfers</h3>

        @if (in_array($stockRequest->status?->value, ['approved', 'partially_fulfilled'], true) && auth()->user()?->can('inventory.stock_request.update'))
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label for="transfer_transaction_id" class="text-sm font-medium text-gray-700">Link Transfer</label>
                    <select id="transfer_transaction_id" wire:model="transfer_transaction_id"
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">Select transfer transaction</option>
                        @foreach ($transferCandidates as $transfer)
                            <option value="{{ $transfer->id }}">
                                {{ $transfer->transfer_no }} - {{ optional($transfer->transfer_date)->format('d M, Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="button" wire:click="linkTransfer"
                        class="inline-flex h-11 items-center rounded-lg bg-indigo-600 px-4 text-sm font-medium text-white transition hover:bg-indigo-700">
                        Link Transfer
                    </button>
                </div>
            </div>
        @endif

        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Transfer No</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Transfer Date</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Route</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($stockRequest->transfers as $transfer)
                            @php
                                $transferStatusClass = match ($transfer->status?->value) {
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'approved' => 'bg-indigo-100 text-indigo-700',
                                    'requested' => 'bg-amber-100 text-amber-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-5 py-4 text-sm font-medium text-gray-800">{{ $transfer->transfer_no }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ optional($transfer->transfer_date)->format('d M, Y') }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    {{ $transfer->senderStore?->name ?? 'N/A' }}
                                    <span class="text-xs text-gray-500">to</span>
                                    {{ $transfer->receiverStore?->name ?? 'N/A' }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $transferStatusClass }}">
                                        {{ $transfer->status?->label() ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.inventory.stock-transfers.view', $transfer) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                        View Transfer
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">No linked transfers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h3 class="text-base font-semibold text-gray-800">Items</h3>
        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Product</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Requested Qty</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Approved Qty</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Fulfilled Qty</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Remaining Qty</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($stockRequest->items as $item)
                            @php
                                $target = (float) ($item->approved_quantity ?? $item->quantity);
                                $remaining = max(0, round($target - (float) $item->fulfilled_quantity, 3));
                            @endphp
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800">{{ $item->product?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $item->product?->sku ?? 'No SKU' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->quantity, 3) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) ($item->approved_quantity ?? $item->quantity), 3) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->fulfilled_quantity, 3) }}</td>
                                <td class="px-5 py-4 text-sm {{ $remaining > 0 ? 'text-indigo-700' : 'text-emerald-700' }}">{{ number_format($remaining, 3) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $item->remarks ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
