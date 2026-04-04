<div x-data x-init="$store.pageName = { name: 'Purchase Return Details', slug: 'purchase-return-view' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold">Purchase Return Details</h1>

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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.purchase-returns.index') }}">
                        Purchase Return
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">{{ $purchaseReturn->return_no }}</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Return No</p>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $purchaseReturn->return_no }}</h2>
                </div>
                @php
                    $statusClass = match ($purchaseReturn->status?->value) {
                        'posted' => 'bg-emerald-100 text-emerald-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                    {{ $purchaseReturn->status?->label() ?? 'N/A' }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-500">Return Date</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($purchaseReturn->return_date)->format('d M, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Supplier</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseReturn->supplier?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Store</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseReturn->store?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Linked Stock Receive</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseReturn->stockReceive?->receive_no ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Linked Purchase Order</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseReturn->purchaseOrder?->po_no ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Reason</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseReturn->reason ?: 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Created By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseReturn->creator?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Posted By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseReturn->poster?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Posted At</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($purchaseReturn->posted_at)->format('d M, Y h:i A') ?? 'N/A' }}</p>
                </div>
            </div>

            @if ($purchaseReturn->remarks)
                <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Remarks</p>
                    <p class="text-sm text-gray-700">{{ $purchaseReturn->remarks }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Actions</h3>
            <p class="mt-1 text-xs text-gray-500">Posting will reduce stock balance and create return ledger rows.</p>

            <div class="mt-4 space-y-2">
                <a href="{{ route('admin.inventory.purchase-returns.index') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Back to List
                </a>

                @can('inventory.purchase_return.update')
                    @if ($purchaseReturn->status?->value === 'draft')
                        <a href="{{ route('admin.inventory.purchase-returns.edit', $purchaseReturn) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Edit Draft
                        </a>
                    @endif
                @endcan

                @can('inventory.purchase_return.post')
                    @if ($purchaseReturn->status?->value === 'draft')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'postReturn',
                                title: 'Post this purchase return?',
                                text: 'This will reduce stock and update stock movement ledger.',
                                confirmText: 'Yes, post now'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Post Return
                        </button>
                    @endif
                @endcan

                @can('inventory.purchase_return.update')
                    @if ($purchaseReturn->status?->value === 'draft')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'cancelReturn',
                                title: 'Cancel this purchase return?',
                                text: 'Cancelled return will not affect stock.',
                                confirmText: 'Yes, cancel'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                            Cancel Return
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
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Source Receive Item</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Original Received Qty</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Return Qty</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Rate</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Total</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($purchaseReturn->items as $item)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800">{{ $item->product?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $item->product?->sku ?? 'No SKU' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    <p>SR Item #{{ $item->stock_receive_item_id ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">PO Item: {{ $item->purchase_order_item_id ?? 'N/A' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) ($item->stockReceiveItem?->quantity ?? 0), 3) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->quantity, 3) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->total_price, 2) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $item->remarks ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200 bg-gray-50">
                            <td colspan="5" class="px-5 py-3 text-right text-sm font-semibold text-gray-700">Grand Total</td>
                            <td class="px-5 py-3 text-sm font-semibold text-gray-800">{{ number_format($grandTotal, 2) }}</td>
                            <td class="px-5 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
