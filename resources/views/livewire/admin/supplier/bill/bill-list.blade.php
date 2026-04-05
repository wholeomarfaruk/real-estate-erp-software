<div x-data x-init="$store.pageName = { name: 'Supplier Bills', slug: 'supplier-bills' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Supplier Bills</h1>
            <p class="text-sm text-gray-500">Manage supplier invoices, due status, and bill lifecycle.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li>Supplier</li>
                <li>/</li>
                <li class="text-gray-700">Bills</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Bills</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format($totalBills) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Open Bills</p>
            <p class="mt-1 text-2xl font-semibold text-blue-700">{{ number_format($openBills) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Overdue Bills</p>
            <p class="mt-1 text-2xl font-semibold text-rose-700">{{ number_format($overdueBills) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total Due Amount</p>
            <p class="mt-1 text-2xl font-semibold text-indigo-700">{{ number_format((float) $totalDueAmount, 2) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-3">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search bill no, supplier, ref..."
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div class="lg:col-span-2">
                    <select wire:model.live="supplierFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Suppliers</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <select wire:model.live="statusFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <input
                        type="date"
                        wire:model.live="dateFrom"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div class="lg:col-span-2">
                    <input
                        type="date"
                        wire:model.live="dateTo"
                        class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div class="lg:col-span-1">
                    @can('supplier.bill.create')
                        <a href="{{ route('admin.supplier.bills.create') }}" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-gray-900 px-3 text-sm font-medium text-white transition hover:bg-gray-800">
                            Create
                        </a>
                    @endcan
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-5">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model.live="overdueOnly" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Overdue only
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model.live="unpaidOnly" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Unpaid only
                </label>
                @can('supplier.bill.pending.view')
                    <a href="{{ route('admin.supplier.bills.pending') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        View pending bill list
                    </a>
                @endcan
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
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Reference Type</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Reference No</th>
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
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $bill->reference_type_label }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $bill->reference_no }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ optional($bill->bill_date)->format('d M, Y') }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ optional($bill->due_date)->format('d M, Y') ?: 'N/A' }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format((float) $bill->total_amount, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ number_format((float) $bill->paid_amount, 2) }}</td>
                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-700">{{ number_format((float) $bill->due_amount, 2) }}</td>
                                    <td class="px-5 py-4">
                                        <x-supplier-bill-status-badge :status="$bill->status?->value" />
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $bill->creator?->name ?? 'N/A' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <span class="sr-only">Open actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-48 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('supplier.bill.view')
                                                    <a href="{{ route('admin.supplier.bills.view', $bill) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        View
                                                    </a>
                                                @endcan

                                                @can('supplier.bill.edit')
                                                    @if ($bill->canEdit())
                                                        <a href="{{ route('admin.supplier.bills.edit', $bill) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Edit
                                                        </a>
                                                    @endif
                                                @endcan

                                                @can('supplier.bill.cancel')
                                                    @if ($bill->canCancel())
                                                        <button
                                                            type="button"
                                                            x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $bill->id }},
                                                                method: 'cancelBill',
                                                                title: 'Cancel this bill?',
                                                                text: 'Cancelled bills cannot be edited or paid later.',
                                                                confirmText: 'Yes, cancel bill'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50"
                                                        >
                                                            Cancel
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No bills found.</p>
                                        <p class="mt-1 text-xs text-gray-500">Try changing filters or create a new bill.</p>
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
