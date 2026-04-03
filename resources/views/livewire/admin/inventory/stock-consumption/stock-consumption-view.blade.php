<div x-data x-init="$store.pageName = { name: 'Consumption Details', slug: 'stock-consumption-view' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold">Consumption Details</h1>

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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.stock-consumptions.index') }}">
                        Stock Consumption
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">{{ $stockConsumption->consumption_no }}</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Consumption No</p>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $stockConsumption->consumption_no }}</h2>
                </div>
                @php
                    $statusClass = match ($stockConsumption->status?->value) {
                        'posted' => 'bg-emerald-100 text-emerald-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                    {{ $stockConsumption->status?->label() ?? 'N/A' }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-500">Date</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($stockConsumption->consumption_date)->format('d M, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Store</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockConsumption->store?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Project</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockConsumption->project?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Created By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockConsumption->creator?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Posted By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $stockConsumption->poster?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Posted At</p>
                    <p class="text-sm font-medium text-gray-800">{{ optional($stockConsumption->posted_at)->format('d M, Y h:i A') ?? 'N/A' }}</p>
                </div>
            </div>

            @if ($stockConsumption->remarks)
                <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Remarks</p>
                    <p class="text-sm text-gray-700">{{ $stockConsumption->remarks }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Actions</h3>
            <p class="mt-1 text-xs text-gray-500">Posting will reduce stock and record ledger entries.</p>

            <div class="mt-4 space-y-2">
                <a href="{{ route('admin.inventory.stock-consumptions.index') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Back to List
                </a>

                @can('inventory.stock.consumption.update')
                    @if ($stockConsumption->status?->value === 'draft')
                        <a href="{{ route('admin.inventory.stock-consumptions.edit', $stockConsumption) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Edit Draft
                        </a>
                    @endif
                @endcan

                @can('inventory.stock.consumption.post')
                    @if ($stockConsumption->status?->value === 'draft')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'postConsumption',
                                title: 'Post this consumption?',
                                text: 'This will permanently update stock balances and movements.',
                                confirmText: 'Yes, post now'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Post Consumption
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
                        @forelse ($stockConsumption->items as $item)
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
                </table>
            </div>
        </div>
    </div>
</div>
