<div x-data x-init="$store.pageName = { name: 'Stock Transfer Details', slug: 'stock-transfer-view' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold">Stock Transfer Details</h1>

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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.stock-transfers.index') }}">
                        Stock Transfer
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">{{ $transferTransaction->transfer_no }}</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Transfer No</p>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $transferTransaction->transfer_no }}</h2>
                </div>
                @php
                    $statusClass = match ($transferTransaction->status?->value) {
                        'requested' => 'bg-blue-100 text-blue-700',
                        'approved' => 'bg-indigo-100 text-indigo-700',
                        'completed' => 'bg-green-100 text-green-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                    {{ $transferTransaction->status?->label() ?? 'N/A' }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-500">Transfer Date</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($transferTransaction->transfer_date)->format('d M, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Sender Store</p>
                    <p class="text-sm font-medium text-gray-800">{{ $transferTransaction->senderStore?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Receiver Store</p>
                    <p class="text-sm font-medium text-gray-800">{{ $transferTransaction->receiverStore?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Requested By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $transferTransaction->requester?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Approved By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $transferTransaction->approver?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Received By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $transferTransaction->receiver?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Requested At</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($transferTransaction->requested_at)->format('d M, Y h:i A') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Approved At</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($transferTransaction->approved_at)->format('d M, Y h:i A') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Completed At</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($transferTransaction->received_at)->format('d M, Y h:i A') ?? 'N/A' }}</p>
                </div>
            </div>

            @if ($transferTransaction->remarks)
                <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Remarks</p>
                    <p class="text-sm text-gray-700">{{ $transferTransaction->remarks }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Actions</h3>
            <p class="mt-1 text-xs text-gray-500">Completion updates stock balances and stock ledger.</p>

            <div class="mt-4 space-y-2">
                <a href="{{ route('admin.inventory.stock-transfers.index') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Back to List
                </a>

                @can('inventory.stock.transfer.update')
                    @if ($transferTransaction->status?->value === 'draft')
                        <a href="{{ route('admin.inventory.stock-transfers.edit', $transferTransaction) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Edit Draft
                        </a>
                    @endif
                @endcan

                @can('inventory.stock.transfer.request')
                    @if ($transferTransaction->status?->value === 'draft')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'requestTransfer',
                                title: 'Request this transfer?',
                                text: 'This will send transfer for approval.',
                                confirmText: 'Yes, request'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                            Request Transfer
                        </button>
                    @endif
                @endcan

                @can('inventory.stock.transfer.approve')
                    @if ($transferTransaction->status?->value === 'requested')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'approveTransfer',
                                title: 'Approve this transfer?',
                                text: 'Approved transfer can be completed.',
                                confirmText: 'Yes, approve'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                            Approve Transfer
                        </button>
                    @endif
                @endcan

                @can('inventory.stock.transfer.complete')
                    @if ($transferTransaction->status?->value === 'approved')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'completeTransfer',
                                title: 'Complete this transfer?',
                                text: 'This will update stock balances and stock ledger.',
                                confirmText: 'Yes, complete'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Complete Transfer
                        </button>
                    @endif
                @endcan

                @can('inventory.stock.transfer.update')
                    @if (in_array($transferTransaction->status?->value, ['draft', 'requested', 'approved'], true))
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'cancelTransfer',
                                title: 'Cancel this transfer?',
                                text: 'Cancelled transfer will not affect stock.',
                                confirmText: 'Yes, cancel'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                            Cancel Transfer
                        </button>
                    @endif
                @endcan
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
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Qty</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Received Qty</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Unit Price</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Total</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transferTransaction->items as $item)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800">{{ $item->product?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $item->product?->sku ?? 'No SKU' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->quantity, 3) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) ($item->received_quantity ?? 0), 3) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->total_price, 2) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $item->remarks ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200 bg-gray-50">
                            <td colspan="4" class="px-5 py-3 text-right text-sm font-semibold text-gray-700">Transfer Total Value</td>
                            <td class="px-5 py-3 text-sm font-semibold text-gray-800">{{ number_format($totalValue, 2) }}</td>
                            <td class="px-5 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
