<div x-data x-init="$store.pageName = { name: 'Inventory Dashboard', slug: 'inventory-dashboard' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Inventory Dashboard</h1>
            <p class="text-sm text-gray-500">Operational inventory snapshot from stock balances and stock movements.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li>Inventory</li>
                <li>/</li>
                <li class="text-gray-700">Dashboard</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Products</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format($totalProducts) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Stores</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format($totalStores) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Office Stores</p>
            <p class="mt-1 text-2xl font-semibold text-blue-700">{{ number_format($totalOfficeStores) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Project Stores</p>
            <p class="mt-1 text-2xl font-semibold text-amber-700">{{ number_format($totalProjectStores) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Stock Quantity</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format($totalStockQty, 3) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Stock Value</p>
            <p class="mt-1 text-2xl font-semibold text-indigo-700">{{ number_format($totalStockValue, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Low Stock Items</p>
            <p class="mt-1 text-2xl font-semibold text-orange-700">{{ number_format($lowStockItemsCount) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Out Of Stock Items</p>
            <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format($outOfStockItemsCount) }}</p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Recent Stock Receives</h2>
                <a href="{{ route('admin.inventory.stock-receives.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">No</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Store</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentReceives as $receive)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-700">{{ $receive->receive_no }}</p>
                                    <p class="text-xs text-gray-500">{{ optional($receive->receive_date)->format('d M, Y') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-700">{{ $receive->store?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $receive->supplier?->name ?? 'No Supplier' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                        {{ $receive->status?->label() ?? 'N/A' }}
                                    </span>
                                    <p class="mt-1 text-sm text-gray-700">{{ number_format((float) ($receive->total_amount ?? 0), 2) }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No receive records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Recent Stock Transfers</h2>
                <a href="{{ route('admin.inventory.stock-transfers.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">No</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Route</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentTransfers as $transfer)
                            @php
                                $statusValue = $transfer->status?->value ?? $transfer->status;
                                $statusClass = match ($statusValue) {
                                    'requested' => 'bg-blue-100 text-blue-700',
                                    'approved' => 'bg-indigo-100 text-indigo-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'cancelled' => 'bg-rose-100 text-rose-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-700">{{ $transfer->transfer_no }}</p>
                                    <p class="text-xs text-gray-500">{{ optional($transfer->transfer_date)->format('d M, Y') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-700">{{ $transfer->senderStore?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">to {{ $transfer->receiverStore?->name ?? 'N/A' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ $transfer->status?->label() ?? 'N/A' }}
                                    </span>
                                    <p class="mt-1 text-sm text-gray-700">{{ number_format((float) ($transfer->total_amount ?? 0), 2) }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No transfer records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Recent Stock Consumptions</h2>
                <a href="{{ route('admin.inventory.stock-consumptions.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">No</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Store</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentConsumptions as $consumption)
                            @php
                                $statusValue = $consumption->status?->value ?? $consumption->status;
                                $statusClass = match ($statusValue) {
                                    'posted' => 'bg-emerald-100 text-emerald-700',
                                    'cancelled' => 'bg-rose-100 text-rose-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-700">{{ $consumption->consumption_no }}</p>
                                    <p class="text-xs text-gray-500">{{ optional($consumption->consumption_date)->format('d M, Y') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-700">{{ $consumption->store?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $consumption->project?->name ?? 'No Project' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ $consumption->status?->label() ?? 'N/A' }}
                                    </span>
                                    <p class="mt-1 text-sm text-gray-700">{{ number_format((float) ($consumption->total_amount ?? 0), 2) }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No consumption records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Low Stock Items</h2>
                <a href="{{ route('admin.inventory.reports.low-stock') }}" class="text-xs text-indigo-600 hover:text-indigo-700">Low stock report</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Product</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Store</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Qty</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Min</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Avg Rate</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($lowStockItems as $row)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-700">{{ $row->product?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $row->product?->sku ?: 'No SKU' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-700">{{ $row->store?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $row->store?->code ?? 'N/A' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-orange-700">{{ number_format((float) $row->quantity, 3) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) ($row->product?->minimum_stock_level ?? 0), 3) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->avg_unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->total_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No low stock items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Top Consumed Products</h2>
                <a href="{{ route('admin.inventory.reports.stock-movement') }}" class="text-xs text-indigo-600 hover:text-indigo-700">Movement report</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Product</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Consumed Qty</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Consumed Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($topConsumedProducts as $row)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-700">{{ $row->product?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $row->product?->sku ?: 'No SKU' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-rose-700">{{ number_format((float) ($row->consumed_qty ?? 0), 3) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) ($row->consumed_value ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No consumption movement found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Office Store Summary</h2>
                <a href="{{ route('admin.inventory.reports.office-store-summary') }}" class="text-xs text-indigo-600 hover:text-indigo-700">Office summary</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Store</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Qty</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($officeStoreSummaries as $row)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-700">{{ $row->store_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $row->store_code ?: 'N/A' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) ($row->total_qty ?? 0), 3) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) ($row->total_value ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No office store summary found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Project Store Summary</h2>
                <a href="{{ route('admin.inventory.reports.project-store-summary') }}" class="text-xs text-indigo-600 hover:text-indigo-700">Project summary</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Store</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500">Project</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Qty</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($projectStoreSummaries as $row)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-700">{{ $row->store_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $row->store_code ?: 'N/A' }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row->project_name ?: 'N/A' }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) ($row->total_qty ?? 0), 3) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) ($row->total_value ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No project store summary found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/60 px-4 py-3">
        <p class="text-sm font-medium text-indigo-700">Dashboard trend widgets are ready for next phase.</p>
        <p class="mt-1 text-xs text-indigo-600">Planned: movement trend chart, monthly receive vs consumption, and supplier/project usage trends.</p>
    </div>
</div>
