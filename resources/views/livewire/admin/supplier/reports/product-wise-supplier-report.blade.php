<div x-data x-init="$store.pageName = { name: 'Product Wise Supplier Report', slug: 'supplier-reports' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Product Wise Supplier Report</h1>
            <p class="text-sm text-gray-500">Product-supplier purchase rate and quantity trend from supplier bills.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="printPlaceholder" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Print
            </button>
            <button type="button" wire:click="exportPlaceholder" class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                Export
            </button>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Products</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format((int) $summary['total_products']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Suppliers</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format((int) $summary['total_suppliers']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Qty</p>
            <p class="mt-1 text-2xl font-semibold text-blue-700">{{ number_format((float) $summary['total_qty'], 3) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Purchase Value</p>
            <p class="mt-1 text-2xl font-semibold text-indigo-700">{{ number_format((float) $summary['total_purchase_value'], 2) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label class="text-xs text-gray-500">Product</label>
                <select wire:model.live="product_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All Products</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku ?: 'N/A' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="text-xs text-gray-500">Supplier</label>
                <select wire:model.live="supplier_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All Suppliers</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->code ?: 'N/A' }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">From Date</label>
                <input type="date" wire:model.live="from_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500">To Date</label>
                <input type="date" wire:model.live="to_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500">Per Page</label>
                <select wire:model.live="perPage" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" wire:click="resetFilters" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Billed Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Average Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Last Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Min Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Max Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Purchase Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Last Purchase Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $row->product_name ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->supplier_name ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->total_billed_qty, 3) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-indigo-700">{{ number_format((float) $row->average_rate, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-blue-700">{{ number_format((float) $row->last_rate, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->min_rate, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->max_rate, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-gray-700">{{ number_format((float) $row->total_purchase_amount, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->last_purchase_date ? \Illuminate\Support\Carbon::parse($row->last_purchase_date)->format('d M, Y') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <p class="text-sm font-medium text-gray-700">No report rows found.</p>
                                <p class="text-xs text-gray-500">Adjust filters and try again.</p>
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
</div>
