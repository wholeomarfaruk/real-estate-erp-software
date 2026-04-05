<div x-data x-init="$store.pageName = { name: 'Supplier Payment Details', slug: 'supplier-payments' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">{{ $payment->payment_no }}</h1>
            <p class="text-sm text-gray-500">Supplier payment details, allocation breakdown, and audit trail.</p>
        </div>

        <div class="flex items-center gap-3">
            @can('supplier.payment.edit')
                @if ($payment->canEdit())
                    <a href="{{ route('admin.supplier.payments.edit', $payment) }}" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                        Edit Payment
                    </a>
                @endif
            @endcan
            <a href="{{ route('admin.supplier.payments.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Back to List
            </a>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Payment Header</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Payment No:</span> {{ $payment->payment_no }}</p>
                <p><span class="text-gray-500">Payment Date:</span> {{ optional($payment->payment_date)->format('d M, Y') }}</p>
                <p class="flex items-center gap-2">
                    <span class="text-gray-500">Status:</span>
                    <x-supplier-payment-status-badge :status="$payment->status?->value" />
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Supplier Info</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Name:</span> {{ $payment->supplier?->name ?? 'N/A' }}</p>
                <p><span class="text-gray-500">Code:</span> {{ $payment->supplier?->code ?? 'N/A' }}</p>
                <p><span class="text-gray-500">Contact:</span> {{ $payment->supplier?->contact_person ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Phone:</span> {{ $payment->supplier?->phone ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Email:</span> {{ $payment->supplier?->email ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Method & Source</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Payment Method:</span> {{ $payment->payment_method?->label() ?? 'N/A' }}</p>
                <p><span class="text-gray-500">Reference No:</span> {{ $payment->reference_no ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Transaction No:</span> {{ $payment->transaction_no ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Cheque No:</span> {{ $payment->cheque_no ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Account:</span> {{ $payment->account_name ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Account Ref:</span> {{ $payment->account_reference ?: 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Amount Summary</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Total Amount:</span> {{ number_format((float) $payment->total_amount, 2) }}</p>
                <p><span class="text-gray-500">Allocated Amount:</span> {{ number_format((float) $payment->allocated_amount, 2) }}</p>
                <p><span class="text-gray-500">Unallocated Amount:</span> {{ number_format((float) $payment->unallocated_amount, 2) }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Allocation Summary</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Allocated Bills:</span> {{ $payment->allocations->count() }}</p>
                <p><span class="text-gray-500">Allocated Total:</span> {{ number_format((float) $payment->allocations->sum('allocated_amount'), 2) }}</p>
                <p><span class="text-gray-500">Advance Balance:</span> {{ number_format((float) $payment->unallocated_amount, 2) }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Payment Integration</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Ledger Entries:</span> 0 (placeholder)</p>
                <p><span class="text-gray-500">Adjustments:</span> 0 (placeholder)</p>
                <p class="text-xs text-gray-500">Supplier ledger and statement integration will be added in later steps.</p>
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-700">Bill Allocations</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs text-gray-500">SL</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500">Bill No</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500">Bill Date</th>
                        <th class="px-4 py-2 text-left text-xs text-gray-500">Due Date</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500">Bill Total</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500">Bill Paid</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500">Bill Due</th>
                        <th class="px-4 py-2 text-right text-xs text-gray-500">Allocated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($payment->allocations as $allocation)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $allocation->bill?->bill_no ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($allocation->bill?->bill_date)->format('d M, Y') ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($allocation->bill?->due_date)->format('d M, Y') ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) ($allocation->bill?->total_amount ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) ($allocation->bill?->paid_amount ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) ($allocation->bill?->due_amount ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-gray-700">{{ number_format((float) $allocation->allocated_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No bill allocations for this payment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700">Remarks</h2>
        <p class="mt-2 text-sm text-gray-700">{{ $payment->remarks ?: 'No remarks provided.' }}</p>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700">Audit Info</h2>
        <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-gray-700 md:grid-cols-2">
            <p><span class="text-gray-500">Created By:</span> {{ $payment->creator?->name ?? 'N/A' }}</p>
            <p><span class="text-gray-500">Updated By:</span> {{ $payment->updater?->name ?? 'N/A' }}</p>
            <p><span class="text-gray-500">Created At:</span> {{ optional($payment->created_at)->format('d M, Y h:i A') }}</p>
            <p><span class="text-gray-500">Updated At:</span> {{ optional($payment->updated_at)->format('d M, Y h:i A') }}</p>
        </div>
    </div>
</div>
