<div x-data x-init="$store.pageName = { name: 'Supplier Return Details', slug: 'supplier-returns' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">{{ $supplierReturn->return_no }}</h1>
            <p class="text-sm text-gray-500">Supplier return/debit note details and payable impact snapshot.</p>
        </div>

        <div class="flex items-center gap-3">
            @can('supplier.return.edit')
                @if ($supplierReturn->canEdit())
                    <a href="{{ route('admin.supplier.returns.edit', $supplierReturn) }}" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                        Edit Draft
                    </a>
                @endif
            @endcan
            <a href="{{ route('admin.supplier.returns.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Back to List
            </a>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Supplier Info</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Name:</span> {{ $supplierReturn->supplier?->name ?? 'N/A' }}</p>
                <p><span class="text-gray-500">Code:</span> {{ $supplierReturn->supplier?->code ?? 'N/A' }}</p>
                <p><span class="text-gray-500">Contact:</span> {{ $supplierReturn->supplier?->contact_person ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Phone:</span> {{ $supplierReturn->supplier?->phone ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Email:</span> {{ $supplierReturn->supplier?->email ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Return Header</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Return No:</span> {{ $supplierReturn->return_no }}</p>
                <p><span class="text-gray-500">Return Date:</span> {{ optional($supplierReturn->return_date)->format('d M, Y') }}</p>
                <p><span class="text-gray-500">Reason:</span> {{ $supplierReturn->reason ?: 'N/A' }}</p>
                <p class="flex items-center gap-2">
                    <span class="text-gray-500">Status:</span>
                    <x-supplier-return-status-badge :status="$supplierReturn->status?->value" />
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Reference Info</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Reference Type:</span> {{ $supplierReturn->reference_type_label }}</p>
                <p><span class="text-gray-500">Reference No:</span> {{ $supplierReturn->reference_no }}</p>
                <p><span class="text-gray-500">Bill No:</span> {{ $supplierReturn->supplierBill?->bill_no ?? 'N/A' }}</p>
                <p><span class="text-gray-500">Receive No:</span> {{ $supplierReturn->stockReceive?->receive_no ?? 'N/A' }}</p>
                <p><span class="text-gray-500">PO No:</span> {{ $supplierReturn->purchaseOrder?->po_no ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-700">Return Items</h2>
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
                    @forelse ($supplierReturn->items as $item)
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
                <p><span class="text-gray-500">Subtotal:</span> {{ number_format((float) $supplierReturn->subtotal, 2) }}</p>
                <p class="pt-1 font-semibold"><span class="text-gray-500">Total Return Amount:</span> {{ number_format((float) $supplierReturn->total_amount, 2) }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Payable Adjustment Summary</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Adjustment Type:</span> Debit (Payable Decrease)</p>
                <p><span class="text-gray-500">Adjustment Amount:</span> {{ number_format((float) $supplierReturn->total_amount, 2) }}</p>
                <p><span class="text-gray-500">Ledger Impact:</span> {{ $supplierReturn->status?->value === 'approved' ? 'Posted' : 'Pending Approval' }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Stock Effect</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Current Step:</span> Financial adjustment only</p>
                <p><span class="text-gray-500">Stock Reverse:</span> Placeholder</p>
                <p class="text-xs text-gray-500">Stock reverse integration will be added in a future module step.</p>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700">Notes</h2>
        <p class="mt-2 text-sm text-gray-700">{{ $supplierReturn->notes ?: 'No notes provided.' }}</p>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700">Audit Info</h2>
        <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-gray-700 md:grid-cols-2">
            <p><span class="text-gray-500">Created By:</span> {{ $supplierReturn->creator?->name ?? 'N/A' }}</p>
            <p><span class="text-gray-500">Updated By:</span> {{ $supplierReturn->updater?->name ?? 'N/A' }}</p>
            <p><span class="text-gray-500">Approved By:</span> {{ $supplierReturn->approver?->name ?? 'N/A' }}</p>
            <p><span class="text-gray-500">Approved At:</span> {{ optional($supplierReturn->approved_at)->format('d M, Y h:i A') ?: 'N/A' }}</p>
            <p><span class="text-gray-500">Created At:</span> {{ optional($supplierReturn->created_at)->format('d M, Y h:i A') }}</p>
            <p><span class="text-gray-500">Updated At:</span> {{ optional($supplierReturn->updated_at)->format('d M, Y h:i A') }}</p>
        </div>
    </div>
</div>
