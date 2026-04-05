<div x-data x-init="$store.pageName = { name: 'Pending Supplier Bills', slug: 'supplier-pending-bills' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Pending Supplier Bills</h1>
            <p class="text-sm text-gray-500">Track unpaid, partial, and overdue supplier bills.</p>
        </div>

        <div class="flex items-center gap-3">
            @can('supplier.bill.list')
                <a href="{{ route('admin.supplier.bills.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    All Bills
                </a>
            @endcan
            @can('supplier.bill.create')
                <a href="{{ route('admin.supplier.bills.create') }}" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                    Create Bill
                </a>
            @endcan
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Pending Amount</p>
            <p class="mt-1 text-2xl font-semibold text-indigo-700">{{ number_format((float) $totalPendingAmount, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Overdue Amount</p>
            <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format((float) $overdueAmount, 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Unpaid Bill Count</p>
            <p class="mt-1 text-2xl font-semibold text-blue-700">{{ number_format($unpaidBillCount) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Overdue Bill Count</p>
            <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format($overdueBillCount) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search bill no or supplier"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div class="lg:col-span-3">
                    <select wire:model.live="supplierFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Suppliers</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <input type="date" wire:model.live="dateFrom" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>

                <div class="lg:col-span-2">
                    <input type="date" wire:model.live="dateTo" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>

                <div class="lg:col-span-1">
                    <label class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 px-3 text-sm text-gray-700">
                        <input type="checkbox" wire:model.live="overdueOnly" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        OD
                    </label>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Bill No</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Bill Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Due Date</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Total</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Paid</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Due</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Created By</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($bills as $bill)
                                <tr>
                                    <td class="px-5 py-4 text-sm font-medium text-gray-800">{{ $bill->bill_no }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>{{ $bill->supplier?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $bill->supplier?->code ?? 'N/A' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ optional($bill->bill_date)->format('d M, Y') }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ optional($bill->due_date)->format('d M, Y') ?: 'N/A' }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format((float) $bill->total_amount, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format((float) $bill->paid_amount, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">{{ number_format((float) $bill->due_amount, 2) }}</td>
                                    <td class="px-5 py-4">
                                        <x-supplier-bill-status-badge :status="$bill->status?->value" />
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $bill->creator?->name ?? 'N/A' }}</td>
                                    <td class="px-5 py-4 text-right">
                                        @can('supplier.bill.view')
                                            <a href="{{ route('admin.supplier.bills.view', $bill) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                                View
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No pending bills found.</p>
                                        <p class="mt-1 text-xs text-gray-500">All clear for now or adjust your filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($bills->hasPages())
                <div class="mt-6">
                    {{ $bills->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
