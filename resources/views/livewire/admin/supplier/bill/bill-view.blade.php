<div x-data x-init="$store.pageName = { name: 'Supplier Bill Details', slug: 'supplier-bills' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">{{ $bill->bill_no }}</h1>
            <p class="text-sm text-gray-500">Supplier bill profile and amount breakdown.</p>
        </div>

        <div class="flex items-center gap-3">
            @can('supplier.bill.edit')
                @if ($bill->canEdit())
                    <a href="{{ route('admin.supplier.bills.edit', $bill) }}" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                        Edit Bill
                    </a>
                @endif
            @endcan
            <a href="{{ route('admin.supplier.bills.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Back to List
            </a>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Supplier Info</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Name:</span> {{ $bill->supplier?->name ?? 'N/A' }}</p>
                <p><span class="text-gray-500">Code:</span> {{ $bill->supplier?->code ?? 'N/A' }}</p>
                <p><span class="text-gray-500">Contact:</span> {{ $bill->supplier?->contact_person ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Phone:</span> {{ $bill->supplier?->phone ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Email:</span> {{ $bill->supplier?->email ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Bill Header</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Bill No:</span> {{ $bill->bill_no }}</p>
                <p><span class="text-gray-500">Bill Date:</span> {{ optional($bill->bill_date)->format('d M, Y') }}</p>
                <p><span class="text-gray-500">Due Date:</span> {{ optional($bill->due_date)->format('d M, Y') ?: 'N/A' }}</p>
                <p class="flex items-center gap-2">
                    <span class="text-gray-500">Status:</span>
                    <x-supplier-bill-status-badge :status="$bill->status?->value" />
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Reference Info</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Reference Type:</span> {{ $bill->reference_type_label }}</p>
                <p><span class="text-gray-500">Reference No:</span> {{ $bill->reference_no }}</p>
                <p><span class="text-gray-500">PO No:</span> {{ $bill->purchaseOrder?->po_no ?? 'N/A' }}</p>
                <p><span class="text-gray-500">Receive No:</span> {{ $bill->stockReceive?->receive_no ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-700">Bill Items</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs text-gray-500">SL</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500">Product</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500">Description</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500">Qty</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500">Unit</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500">Rate</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($bill->items as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <p>{{ $item->product?->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $item->product?->sku ?: 'No SKU' }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $item->description ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $item->qty, 3) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $item->unit?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $item->rate, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-gray-700">{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No item rows available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Amount Summary</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Subtotal:</span> {{ number_format((float) $bill->subtotal, 2) }}</p>
                <p><span class="text-gray-500">Discount:</span> {{ number_format((float) $bill->discount_amount, 2) }}</p>
                <p><span class="text-gray-500">Tax:</span> {{ number_format((float) $bill->tax_amount, 2) }}</p>
                <p><span class="text-gray-500">Other Charge:</span> {{ number_format((float) $bill->other_charge, 2) }}</p>
                <p class="pt-1 font-semibold"><span class="text-gray-500">Total Amount:</span> {{ number_format((float) $bill->total_amount, 2) }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Payment Summary</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Paid Amount:</span> {{ number_format((float) $bill->paid_amount, 2) }}</p>
                <p><span class="text-gray-500">Payment Entries:</span> 0 (placeholder)</p>
                <p class="text-xs text-gray-500">Payment module integration will be added in next steps.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Due Summary</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Due Amount:</span> {{ number_format((float) $bill->due_amount, 2) }}</p>
                <p><span class="text-gray-500">Due Date:</span> {{ optional($bill->due_date)->format('d M, Y') ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Due State:</span> {{ $bill->status?->label() ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700">Notes</h2>
        <p class="mt-2 text-sm text-gray-700">{{ $bill->notes ?: 'No notes provided.' }}</p>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700">Audit Info</h2>
        <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-gray-700 md:grid-cols-2">
            <p><span class="text-gray-500">Created By:</span> {{ $bill->creator?->name ?? 'N/A' }}</p>
            <p><span class="text-gray-500">Updated By:</span> {{ $bill->updater?->name ?? 'N/A' }}</p>
            <p><span class="text-gray-500">Created At:</span> {{ optional($bill->created_at)->format('d M, Y h:i A') }}</p>
            <p><span class="text-gray-500">Updated At:</span> {{ optional($bill->updated_at)->format('d M, Y h:i A') }}</p>
        </div>
    </div>
</div>
