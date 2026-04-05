<div x-data x-init="$store.pageName = { name: 'Supplier Wise Report', slug: 'supplier-reports' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Supplier Wise Report</h1>
            <p class="text-sm text-gray-500">Supplier-level billing, payment, due, and ledger position summary.</p>
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
            <p class="text-xs text-gray-500">Total Billed</p>
            <p class="mt-1 text-2xl font-semibold text-indigo-700">{{ number_format((float) $summary['total_billed'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Paid</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format((float) $summary['total_paid'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Due</p>
            <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format((float) $summary['total_due'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Suppliers Count</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format((int) $summary['suppliers_count']) }}</p>
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
                <label class="text-xs text-gray-500">Status</label>
                <select wire:model.live="status" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Per Page</label>
                <select wire:model.live="perPage" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="flex items-end gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model.live="due_only" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Due only
                </label>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Supplier Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Supplier Name</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Bills</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Billed</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Paid</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Due</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total Advance</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Transactions</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Last Bill</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Last Payment</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Ledger Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        @php
                            $ledgerBalance = (float) ($row->current_ledger_balance ?? 0);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->code ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $row->name }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((int) $row->total_bills) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->total_billed_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $row->total_paid_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-rose-700">{{ number_format((float) $row->total_due_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-emerald-700">{{ number_format((float) $row->total_advance_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((int) $row->total_transactions) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->last_bill_date ? \Illuminate\Support\Carbon::parse($row->last_bill_date)->format('d M, Y') : 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row->last_payment_date ? \Illuminate\Support\Carbon::parse($row->last_payment_date)->format('d M, Y') : 'N/A' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $ledgerBalance >= 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ number_format($ledgerBalance, 2) }} {{ $ledgerBalance >= 0 ? 'Payable' : 'Advance' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center">
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
