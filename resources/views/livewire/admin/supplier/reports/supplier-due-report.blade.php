<div x-data x-init="$store.pageName = { name: 'Supplier Due Report', slug: 'supplier-reports' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Supplier Due Report</h1>
            <p class="text-sm text-gray-500">Payable, overdue, unapplied advance, and net supplier position summary.</p>
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
            <p class="text-xs text-gray-500">Total Payable</p>
            <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format((float) $summary['total_payable'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Overdue</p>
            <p class="mt-1 text-2xl font-semibold text-amber-700">{{ number_format((float) $summary['total_overdue'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Suppliers With Due</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format((int) $summary['suppliers_with_due']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Suppliers With Advance</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format((int) $summary['suppliers_with_advance']) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-6">
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

        <div class="mt-3 flex flex-wrap items-center gap-5">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model.live="due_only" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Due only
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model.live="overdue_only" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Overdue only
            </label>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="max-w-full overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Bill Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Paid Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Due Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Overdue Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Unapplied Advance</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Net Payable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        @php
                            $netPayable = (float) $row->net_payable;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <p class="font-medium text-gray-800">{{ $row->name }}</p>
                                <p class="text-xs text-gray-500">{{ $row->code ?: 'N/A' }}</p>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->total_bill_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->total_paid_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-rose-700">{{ number_format((float) $row->total_due_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-amber-700">{{ number_format((float) $row->overdue_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-emerald-700">{{ number_format((float) $row->unapplied_advance, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $netPayable >= 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ number_format($netPayable, 2) }} {{ $netPayable >= 0 ? 'Payable' : 'Advance' }}
                                </span>
                            </td>
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
