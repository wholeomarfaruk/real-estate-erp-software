<div x-data x-init="$store.pageName = { name: 'Product Wise Cost', slug: 'inventory-product-wise-cost' }">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-gray-500">{{ $companyName }}</p>
            <h1 class="mt-1 text-xl font-bold text-gray-900">Product Wise Cost</h1>
            <p class="text-sm text-gray-500">Inventory module product cost summary.</p>
        </div>

        <button
            type="button"
            wire:click="exportCsv"
            class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100"
        >
            Export CSV
        </button>
    </div>

    <form wire:submit.prevent="applyFilters" class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
            <div>
                <label class="text-xs text-gray-500">From Date</label>
                <input type="date" wire:model.defer="from_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">To Date</label>
                <input type="date" wire:model.defer="to_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>

            <div class="lg:col-span-2">
                <label class="text-xs text-gray-500">Product</label>
                <select wire:model.defer="product_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All Products</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">
                    Filter
                </button>
                <button type="button" wire:click="resetFilters" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Reset
                </button>
            </div>
        </div>
    </form>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium text-gray-500">Product Name</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-right text-xs font-medium text-gray-500">Total Quantity</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-right text-xs font-medium text-gray-500">Total Cost</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-right text-xs font-medium text-gray-500">Average Cost</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">
                                {{ $row->product_name }}
                                @if ($row->product_sku)
                                    <p class="text-xs text-gray-500">{{ $row->product_sku }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->total_quantity, 3) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->total_cost, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->average_cost, 2) }}</td>
                            <td class="px-4 py-3">
                                <div class="relative flex justify-end" x-data="{ open: false }">
                                    <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <span class="sr-only">Open actions</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                        </svg>
                                    </button>

                                    <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-40 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                        <button
                                            type="button"
                                            wire:click="loadDetails({{ $row->product_id }})"
                                            @click="open = false"
                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100"
                                        >
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <p class="text-sm font-medium text-gray-700">No product cost data found.</p>
                                <p class="text-xs text-gray-500">Try changing the filter values.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $rows->links() }}
            </div>
        @endif
    </div>

    <div x-cloak x-data="{ open: @entangle('showDetailsModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-5xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Product Cost Details</h2>
                    <p class="text-sm text-gray-500">{{ $selectedProductName ?: 'Selected product' }}</p>
                </div>
                <button type="button" @click="open = false; $wire.closeDetailsModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                            <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium text-gray-500">Reference Type</th>
                            <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium text-gray-500">Reference No / ID</th>
                            <th class="border-b border-gray-200 px-4 py-3 text-right text-xs font-medium text-gray-500">Quantity</th>
                            <th class="border-b border-gray-200 px-4 py-3 text-right text-xs font-medium text-gray-500">Rate</th>
                            <th class="border-b border-gray-200 px-4 py-3 text-right text-xs font-medium text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($detailRows as $detail)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $detail->entry_date ? \Illuminate\Support\Carbon::parse($detail->entry_date)->format('d M, Y') : 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $detail->reference_type_label }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $detail->reference_no }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $detail->quantity, 3) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $detail->rate, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $detail->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">No detail rows found for this product.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
