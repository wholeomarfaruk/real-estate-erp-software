<div x-data x-init="$store.pageName = { name: 'Purchase Invoices', slug: 'purchase-invoices' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.dashboard') }}">
                        Dashboard
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">Purchase Invoices</li>
            </ol>
        </nav>
    </div>

    {{-- Stats --}}
    <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Total</p>
            <p class="mt-2 text-2xl font-semibold text-gray-800">{{ number_format($totalCount) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Pending</p>
            <p class="mt-2 text-2xl font-semibold text-amber-600">{{ number_format($pendingCount) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Approved / Partial</p>
            <p class="mt-2 text-2xl font-semibold text-blue-700">{{ number_format($approvedCount) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Paid</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ number_format($paidCount) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        {{-- Filters --}}
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div class="md:col-span-2">
                    <input type="text" wire:model.live.debounce.400ms="search"
                        placeholder="Invoice no, supplier…"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <select wire:model.live="statusFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select wire:model.live="supplierFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Suppliers</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" wire:model.live="dateFrom" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <input type="date" wire:model.live="dateTo"   class="h-11 w-full rounded-lg border border-gray-300 px-3 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Invoice</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Stock Receive</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Total</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Paid</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Due</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-medium text-gray-800">{{ $invoice->invoice_no }}</p>
                                        <p class="text-xs text-gray-500">{{ optional($invoice->invoice_date)->format('d M, Y') }}</p>
                                        @if ($invoice->supplier_invoice_no)
                                            <p class="text-xs text-gray-400">Supp#: {{ $invoice->supplier_invoice_no }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $invoice->supplier?->name ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        @if ($invoice->stockReceive)
                                            <p class="font-medium">{{ $invoice->stockReceive->receive_no }}</p>
                                            <p class="text-xs text-gray-500">{{ optional($invoice->stockReceive->receive_date)->format('d M, Y') }}</p>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm font-medium text-gray-800">
                                        {{ number_format((float) $invoice->total_amount, 2) }}
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm text-emerald-700">
                                        {{ number_format((float) $invoice->paid_amount, 2) }}
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm {{ (float) $invoice->due_amount > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                        {{ number_format((float) $invoice->due_amount, 2) }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $invoice->status?->badgeClass() }}">
                                            {{ $invoice->status?->label() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" style="display:none;" x-transition
                                                class="absolute right-0 z-40 mt-10 w-52 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">

                                                @can('inventory.purchase_invoice.view')
                                                    <a href="{{ route('admin.inventory.purchase-invoices.view', $invoice) }}"
                                                        class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        View
                                                    </a>
                                                @endcan

                                                @can('inventory.purchase_invoice.approve')
                                                    @if ($invoice->status?->value === 'pending')
                                                        <a href="{{ route('admin.inventory.purchase-invoices.approve', $invoice) }}"
                                                            class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Review &amp; Approve
                                                        </a>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_invoice.cancel')
                                                    @if ($invoice->status?->value === 'pending')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $invoice->id }},
                                                                method: 'cancelInvoice',
                                                                title: 'Cancel this invoice?',
                                                                text: 'No accounting entries will be created. This cannot be undone.',
                                                                confirmText: 'Yes, cancel'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Cancel
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_invoice.delete')
                                                    @if ($invoice->status?->value === 'pending' && ! $invoice->transaction_id)
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $invoice->id }},
                                                                method: 'deleteInvoice',
                                                                title: 'Delete this invoice?',
                                                                text: 'Invoice will be permanently deleted.',
                                                                confirmText: 'Yes, delete'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">
                                                            Delete
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No invoices found</p>
                                        <p class="mt-1 text-xs text-gray-500">Invoices are created automatically when stock is received.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($invoices->hasPages())
                <div class="mt-6">{{ $invoices->links() }}</div>
            @endif
        </div>
    </div>
</div>
