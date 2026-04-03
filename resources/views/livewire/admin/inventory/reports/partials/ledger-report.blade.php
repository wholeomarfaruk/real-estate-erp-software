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

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs text-gray-500">Rows</p>
            <p class="mt-1 text-xl font-semibold text-gray-800">{{ number_format($movements->total()) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs text-gray-500">Total In Qty</p>
            <p class="mt-1 text-xl font-semibold text-emerald-700">{{ number_format($totalInQty, 3) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs text-gray-500">Total Out Qty</p>
            <p class="mt-1 text-xl font-semibold text-rose-700">{{ number_format($totalOutQty, 3) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs text-gray-500">Total Value</p>
            <p class="mt-1 text-xl font-semibold text-indigo-700">{{ number_format($totalValue, 2) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-6">
            <div>
                <label class="text-xs text-gray-500">Date From</label>
                <input type="date" wire:model.live="date_from" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500">Date To</label>
                <input type="date" wire:model.live="date_to" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500">Movement Type</label>
                <select wire:model.live="movement_type" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All Types</option>
                    @foreach ($movementTypes as $movement)
                        <option value="{{ $movement['value'] }}">{{ $movement['label'] }}</option>
                    @endforeach
                </select>
            </div>
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
                <label class="text-xs text-gray-500">Supplier</label>
                <select wire:model.live="supplier_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All Suppliers</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="text-xs text-gray-500">Search</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Ref no, remarks, product, store" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500">Order</label>
                <select wire:model.live="sort_direction" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="desc">Newest First</option>
                    <option value="asc">Oldest First</option>
                </select>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Store</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Movement</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">In Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Out Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($movements as $movement)
                        @php
                            $direction = $movement->direction?->value ?? $movement->direction;
                            $typeValue = $movement->movement_type?->value ?? $movement->movement_type;
                            $typeLabel = $movement->movement_type?->label() ?? str($typeValue)->replace('_', ' ')->title();
                            $typeBadge = match ($typeValue) {
                                'purchase', 'receive', 'transfer_in', 'adjustment_in', 'return' => 'bg-emerald-100 text-emerald-700',
                                'transfer_out', 'consumption', 'adjustment_out' => 'bg-rose-100 text-rose-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                            $storeType = $movement->store?->type?->value ?? null;
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($movement->movement_date)->format('d M, Y h:i A') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $movement->reference_no ?: 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-gray-800">{{ $movement->product?->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $movement->product?->sku ?: 'No SKU' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-700">{{ $movement->store?->name ?? 'N/A' }}</p>
                                @if ($storeType)
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $storeType === 'office' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($storeType) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $movement->project?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $movement->supplier?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $typeBadge }}">{{ $typeLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-emerald-700">
                                {{ $direction === 'in' ? number_format((float) $movement->quantity, 3) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-rose-700">
                                {{ $direction === 'out' ? number_format((float) $movement->quantity, 3) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $movement->unit_price, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $movement->total_price, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $movement->remarks ?: 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-12 text-center">
                                <p class="text-sm font-medium text-gray-700">No movement records found.</p>
                                <p class="text-xs text-gray-500">Try adjusting filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
