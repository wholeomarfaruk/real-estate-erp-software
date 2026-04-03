<div x-data x-init="$store.pageName = { name: 'Stock Receive Details', slug: 'stock-receive-view' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold">Stock Receive Details</h1>

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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.stock-receives.index') }}">
                        Stock Receive
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">{{ $stockReceive->receive_no }}</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Receive No</p>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $stockReceive->receive_no }}</h2>
                </div>
                @php
                    $statusClass = match ($stockReceive->status?->value) {
                        'posted' => 'bg-green-100 text-green-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                    {{ $stockReceive->status?->label() ?? 'N/A' }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-500">Receive Date</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($stockReceive->receive_date)->format('d M, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Store</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockReceive->store?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Supplier</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockReceive->supplier?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Supplier Voucher</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockReceive->supplier_voucher ?: 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Created By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockReceive->creator?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Posted By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockReceive->poster?->name ?? 'N/A' }}</p>
                </div>
            </div>

            @if ($stockReceive->remarks)
                <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Remarks</p>
                    <p class="text-sm text-gray-700">{{ $stockReceive->remarks }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Actions</h3>
            <p class="mt-1 text-xs text-gray-500">Posting updates stock ledger and stock balance.</p>

            <div class="mt-4 space-y-2">
                <a href="{{ route('admin.inventory.stock-receives.index') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Back to List
                </a>

                @can('inventory.stock.receive.update')
                    @if ($stockReceive->status?->value === 'draft')
                        <a href="{{ route('admin.inventory.stock-receives.edit', $stockReceive) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Edit Draft
                        </a>
                    @endif
                @endcan

                @can('inventory.stock.receive.post')
                    @if ($stockReceive->status?->value === 'draft')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'postReceive',
                                title: 'Post this receive?',
                                text: 'This will update stock balance and stock ledger.',
                                confirmText: 'Yes, post now'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Post Receive
                        </button>
                    @endif
                @endcan

                @can('inventory.stock.receive.update')
                    @if ($stockReceive->status?->value === 'draft')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'cancelReceive',
                                title: 'Cancel this receive?',
                                text: 'Cancelled receive will not affect stock.',
                                confirmText: 'Yes, cancel'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                            Cancel Receive
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
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Quantity</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Rate</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Total</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($stockReceive->items as $item)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800">{{ $item->product?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $item->product?->sku ?? 'No SKU' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->quantity, 3) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format((float) $item->total_price, 2) }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $item->remarks ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200 bg-gray-50">
                            <td colspan="3" class="px-5 py-3 text-right text-sm font-semibold text-gray-700">Grand Total</td>
                            <td class="px-5 py-3 text-sm font-semibold text-gray-800">{{ number_format($grandTotal, 2) }}</td>
                            <td class="px-5 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
