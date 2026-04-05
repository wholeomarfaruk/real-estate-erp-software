<div x-data x-init="$store.pageName = { name: 'Supplier Aging Report', slug: 'supplier-reports' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Supplier Aging Report</h1>
            <p class="text-sm text-gray-500">Ageing buckets for supplier dues based on bill due dates.</p>
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
            <p class="text-xs text-gray-500">Total Current</p>
            <p class="mt-1 text-2xl font-semibold text-blue-700">{{ number_format((float) $summary['total_current'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Overdue</p>
            <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format((float) $summary['total_overdue'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">90+ Due Total</p>
            <p class="mt-1 text-2xl font-semibold text-amber-700">{{ number_format((float) $summary['due_90_plus_total'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Supplier Count</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format((int) $summary['supplier_count']) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
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
                <label class="text-xs text-gray-500">As On Date</label>
                <input type="date" wire:model.live="as_on_date" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Current Due</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">1-30 Days</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">31-60 Days</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">61-90 Days</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">90+ Days</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Due</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <p class="font-medium text-gray-800">{{ $row->name }}</p>
                                <p class="text-xs text-gray-500">{{ $row->code ?: 'N/A' }}</p>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-blue-700">{{ number_format((float) $row->current_due, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-amber-700">{{ number_format((float) $row->bucket_1_30, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-amber-700">{{ number_format((float) $row->bucket_31_60, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-rose-700">{{ number_format((float) $row->bucket_61_90, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-rose-700">{{ number_format((float) $row->bucket_90_plus, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold text-rose-700">{{ number_format((float) $row->total_due, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
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
