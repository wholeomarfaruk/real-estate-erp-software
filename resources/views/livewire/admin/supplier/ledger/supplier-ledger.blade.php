<div x-data x-init="$store.pageName = { name: 'Supplier Ledger', slug: 'supplier-ledger' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Supplier Ledger</h1>
            <p class="text-sm text-gray-500">System-generated supplier payable ledger with running balance trail.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li>Supplier</li>
                <li>/</li>
                <li class="text-gray-700">Ledger</li>
            </ol>
        </nav>
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

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-6">
            <div>
                <label class="text-xs text-gray-500">Supplier</label>
                <select wire:model.live="supplierFilter" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
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
                <label class="text-xs text-gray-500">Transaction Type</label>
                <select wire:model.live="transaction_type" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                    <option value="">All Types</option>
                    @foreach ($transactionTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
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
            <div class="flex items-end gap-2">
                <button type="button" wire:click="resetFilters" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Reset
                </button>
                <button type="button" wire:click="rebuildBalances" class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                    Rebuild
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
                        @if (! $supplierFilter)
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Voucher / Ref No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Transaction Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Credit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Running Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ledgerEntries as $entry)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($entry->transaction_date)->format('d M, Y') }}</td>
                            @if (! $supplierFilter)
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <p>{{ $entry->supplier?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $entry->supplier?->code ?? 'N/A' }}</p>
                                </td>
                            @endif
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
                            <td colspan="{{ $supplierFilter ? 7 : 8 }}" class="px-4 py-12 text-center">
                                <p class="text-sm font-medium text-gray-700">No ledger entries found.</p>
                                <p class="text-xs text-gray-500">Try selecting a supplier or adjusting filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ledgerEntries->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $ledgerEntries->links() }}
            </div>
        @endif
    </div>
</div>
