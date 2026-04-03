<div x-data x-init="$store.pageName = { name: 'Stock Adjustment Details', slug: 'stock-adjustment-view' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold">Stock Adjustment Details</h1>

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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.stock-adjustments.index') }}">
                        Stock Adjustment
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">{{ $stockAdjustment->adjustment_no }}</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Adjustment No</p>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $stockAdjustment->adjustment_no }}</h2>
                </div>
                @php
                    $statusClass = match ($stockAdjustment->status?->value) {
                        'posted' => 'bg-emerald-100 text-emerald-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                        default => 'bg-amber-100 text-amber-700',
                    };

                    $typeClass = match ($stockAdjustment->adjustment_type?->value) {
                        'out' => 'bg-rose-100 text-rose-700',
                        default => 'bg-blue-100 text-blue-700',
                    };
                @endphp
                <div class="space-y-2 text-right">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $typeClass }}">
                        {{ $stockAdjustment->adjustment_type?->label() ?? 'N/A' }}
                    </span>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                        {{ $stockAdjustment->status?->label() ?? 'N/A' }}
                    </span>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-500">Adjustment Date</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($stockAdjustment->adjustment_date)->format('d M, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Store</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockAdjustment->store?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Reason</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockAdjustment->reason ?: 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Created By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockAdjustment->creator?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Posted By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockAdjustment->poster?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Posted At</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($stockAdjustment->posted_at)->format('d M, Y h:i A') ?? 'N/A' }}</p>
                </div>
            </div>

            @if ($stockAdjustment->remarks)
                <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Remarks</p>
                    <p class="text-sm text-gray-700">{{ $stockAdjustment->remarks }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Actions</h3>
            <p class="mt-1 text-xs text-gray-500">Posting updates stock balances and stock movement ledger.</p>

            <div class="mt-4 space-y-2">
                <a href="{{ route('admin.inventory.stock-adjustments.index') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Back to List
                </a>

                @can('inventory.stock.adjustment.update')
                    @if ($stockAdjustment->status?->value === 'draft')
                        <a href="{{ route('admin.inventory.stock-adjustments.edit', $stockAdjustment) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Edit Draft
                        </a>
                    @endif
                @endcan

                @can('inventory.stock.adjustment.post')
                    @if ($stockAdjustment->status?->value === 'draft')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'postAdjustment',
                                title: 'Post this adjustment?',
                                text: 'This will update stock balances and stock movements.',
                                confirmText: 'Yes, post now'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Post Adjustment
                        </button>
                    @endif
                @endcan

                @can('inventory.stock.adjustment.update')
                    @if ($stockAdjustment->status?->value === 'draft')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'cancelAdjustment',
                                title: 'Cancel this adjustment?',
                                text: 'Cancelled adjustment will not affect stock.',
                                confirmText: 'Yes, cancel'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                            Cancel Adjustment
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
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Unit Price</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Total</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($stockAdjustment->items as $item)
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
