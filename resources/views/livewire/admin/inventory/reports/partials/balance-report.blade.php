<div x-data x-init="$store.pageName = { name: '{{ $pageTitle }}', slug: 'inventory-reports' }" class="print:bg-white">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">{{ $pageTitle }}</h1>
            <p class="text-sm text-gray-500">{{ $pageDescription }}</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li>Inventory Reports</li>
                <li>/</li>
                <li class="text-gray-700">{{ $pageTitle }}</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs text-gray-500">Rows</p>
            <p class="mt-1 text-xl font-semibold text-gray-800">{{ number_format($totalRows) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs text-gray-500">Total Qty</p>
            <p class="mt-1 text-xl font-semibold text-emerald-700">{{ number_format($totalQty, 3) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs text-gray-500">Total Value</p>
            <p class="mt-1 text-xl font-semibold text-indigo-700">{{ number_format($totalValue, 2) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-6">
            <div>
                <label class="text-xs text-gray-500">Product</label>
                <select wire:model.live="product_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All Products</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Store</label>
                <select wire:model.live="store_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All Stores</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Project</label>
                <select wire:model.live="project_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All Projects</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Store Type</label>
                <select wire:model.live="type_filter" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All Types</option>
                    @foreach ($storeTypes as $storeType)
                        <option value="{{ $storeType['value'] }}">{{ $storeType['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="text-xs text-gray-500">Search</label>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Product, SKU, store name, store code"
                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                >
            </div>
            <div>
                <label class="text-xs text-gray-500">Per Page</label>
                <select wire:model.live="perPage" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="button" wire:click="resetFilters" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Reset
                </button>
                <button type="button" class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-medium text-indigo-700" data-export="true">
                    Export
                </button>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Store</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Project</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Avg Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Value</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Min Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($balances as $balance)
                        @php
                            $storeType = $balance->store?->type?->value ?? $balance->store?->type;
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-800">{{ $balance->product?->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $balance->product?->sku ?: 'No SKU' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-700">{{ $balance->store?->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $balance->store?->code ?? 'N/A' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $storeType === 'office' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ ucfirst((string) $storeType) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $balance->store?->project?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $balance->quantity, 3) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $balance->avg_unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $balance->total_value, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) ($balance->product?->minimum_stock_level ?? 0), 3) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <p class="text-sm font-medium text-gray-700">No stock balances found.</p>
                                <p class="text-xs text-gray-500">Try adjusting filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($balances->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $balances->links() }}
            </div>
        @endif
    </div>
</div>
