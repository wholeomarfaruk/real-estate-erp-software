<div x-data x-init="$store.pageName = { name: 'Supplier Statement', slug: 'supplier-statement' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Supplier Statement</h1>
            <p class="text-sm text-gray-500">Supplier-wise statement with ledger, pending bills, and unapplied advance summary.</p>
        </div>

        <div class="flex items-center gap-2">
            @can('supplier.statement.print')
                <button type="button" wire:click="printPlaceholder" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Print
                </button>
                <button type="button" wire:click="exportPlaceholder" class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                    Export
                </button>
            @endcan
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label class="text-xs text-gray-500">Supplier *</label>
                <select wire:model.live="supplier_id" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">Select Supplier</option>
                    @foreach ($suppliers as $supplierOption)
                        <option value="{{ $supplierOption->id }}">{{ $supplierOption->name }} ({{ $supplierOption->code ?: 'N/A' }})</option>
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
        </div>
    </div>

    @if ($supplier)
        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Supplier Info</h2>
            <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-gray-700 md:grid-cols-2 xl:grid-cols-3">
                <p><span class="text-gray-500">Name:</span> {{ $supplier->name }}</p>
                <p><span class="text-gray-500">Code:</span> {{ $supplier->code ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Company:</span> {{ $supplier->company_name ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Contact:</span> {{ $supplier->contact_person ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Phone:</span> {{ $supplier->phone ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Email:</span> {{ $supplier->email ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs text-gray-500">Opening Balance</p>
                <p class="mt-1 text-2xl font-semibold {{ (float) $summary['opening_balance'] >= 0 ? 'text-indigo-700' : 'text-emerald-700' }}">
                    {{ number_format((float) $summary['opening_balance'], 2) }}
                </p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs text-gray-500">Total Debit</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format((float) $summary['total_debit'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs text-gray-500">Total Credit</p>
                <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format((float) $summary['total_credit'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs text-gray-500">Closing Balance</p>
                <p class="mt-1 text-2xl font-semibold {{ (float) $summary['closing_balance'] >= 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                    {{ number_format((float) $summary['closing_balance'], 2) }}
                </p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Pending Bills Summary</h3>
                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-500">Pending Bills</p>
                        <p class="text-lg font-semibold text-gray-800">{{ number_format((int) $pendingSummary['pending_count']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Pending Amount</p>
                        <p class="text-lg font-semibold text-rose-700">{{ number_format((float) $pendingSummary['pending_amount'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Overdue Bills</p>
                        <p class="text-lg font-semibold text-amber-700">{{ number_format((int) $pendingSummary['overdue_count']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Overdue Amount</p>
                        <p class="text-lg font-semibold text-amber-700">{{ number_format((float) $pendingSummary['overdue_amount'], 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700">Unapplied Advance Summary</h3>
                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-500">Payments With Unallocated</p>
                        <p class="text-lg font-semibold text-gray-800">{{ number_format((int) $unallocatedSummary['unallocated_count']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Unallocated Amount</p>
                        <p class="text-lg font-semibold text-emerald-700">{{ number_format((float) $unallocatedSummary['unallocated_amount'], 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mt-4 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-700">
            Select a supplier to view statement summary and transactions.
        </div>
    @endif

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Voucher / Ref No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Transaction Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Credit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Running Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($transactions as $entry)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($entry->transaction_date)->format('d M, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $entry->reference_no ?: 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <x-supplier-ledger-transaction-type-badge :type="$entry->transaction_type?->value ?? $entry->transaction_type" />
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $entry->description ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-right text-sm text-emerald-700">{{ number_format((float) $entry->debit, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-rose-700">{{ number_format((float) $entry->credit, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-semibold {{ (float) $entry->balance >= 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                {{ number_format((float) $entry->balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <p class="text-sm font-medium text-gray-700">No statement rows found.</p>
                                <p class="text-xs text-gray-500">Choose a supplier and date range to load statement data.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
