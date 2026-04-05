<div x-data x-init="$store.pageName = { name: 'Supplier Details', slug: 'suppliers' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">{{ $supplier->name }}</h1>
            <p class="text-sm text-gray-500">Code: {{ $supplier->code ?: 'N/A' }}</p>
        </div>

        <div class="flex items-center gap-3">
            @can('supplier.edit')
                <a href="{{ route('admin.supplier.suppliers.edit', $supplier) }}" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                    Edit Supplier
                </a>
            @endcan
            <a href="{{ route('admin.supplier.suppliers.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Back to List
            </a>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Basic Info</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Name:</span> {{ $supplier->name }}</p>
                <p><span class="text-gray-500">Code:</span> {{ $supplier->code ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Company:</span> {{ $supplier->company_name ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Contact Person:</span> {{ $supplier->contact_person ?: 'N/A' }}</p>
                <p class="flex items-center gap-2">
                    <span class="text-gray-500">Status:</span>
                    <x-supplier-status-badge :status="$supplier->status_label" />
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Contact Info</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Phone:</span> {{ $supplier->phone ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Alternate Phone:</span> {{ $supplier->alternate_phone ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Email:</span> {{ $supplier->email ?: 'N/A' }}</p>
                <p><span class="text-gray-500">Address:</span> {{ $supplier->address ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Financial Info</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-700">
                <p><span class="text-gray-500">Opening Balance:</span> {{ number_format((float) ($supplier->opening_balance ?? 0), 2) }}</p>
                <p><span class="text-gray-500">Balance Type:</span> {{ ucfirst($supplier->opening_balance_type ?: 'payable') }}</p>
                <p><span class="text-gray-500">Current Due:</span> {{ number_format((float) ($supplier->current_due ?? 0), 2) }}</p>
                <p><span class="text-gray-500">Payment Terms:</span> {{ (int) ($supplier->payment_terms_days ?? 0) }} days</p>
                <p><span class="text-gray-500">Credit Limit:</span> {{ number_format((float) ($supplier->credit_limit ?? 0), 2) }}</p>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Current Due</p>
            <p class="mt-1 text-2xl font-semibold text-indigo-700">{{ number_format((float) ($supplier->current_due ?? 0), 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Purchase Orders</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format((int) ($supplier->purchase_orders_count ?? 0)) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Stock Receives</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format((int) ($supplier->stock_receives_count ?? 0)) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Purchase Returns</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format((int) ($supplier->purchase_returns_count ?? 0)) }}</p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Latest Purchases</h2>
                <p class="mt-1 text-xs text-gray-500">Placeholder for purchase timeline integration.</p>
            </div>
            <div class="p-4">
                @if ($latestPurchases->isEmpty())
                    <p class="text-sm text-gray-500">No purchase records available yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($latestPurchases as $purchase)
                            <div class="rounded-lg border border-gray-100 px-3 py-2">
                                <p class="text-sm font-medium text-gray-700">{{ $purchase->po_no }}</p>
                                <p class="text-xs text-gray-500">{{ optional($purchase->order_date)->format('d M, Y') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Pending Bills</h2>
                <p class="mt-1 text-xs text-gray-500">Placeholder section for bill aging workflow.</p>
            </div>
            <div class="p-4">
                @if ($pendingBills->isEmpty())
                    <p class="text-sm text-gray-500">No pending bill data available now.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($pendingBills as $bill)
                            <div class="rounded-lg border border-gray-100 px-3 py-2">
                                <p class="text-sm font-medium text-gray-700">{{ $bill->bill_no }}</p>
                                <p class="text-xs text-gray-500">Due: {{ number_format((float) ($bill->due_amount ?? 0), 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700">Payment History</h2>
                <p class="mt-1 text-xs text-gray-500">Placeholder section for payment/ledger integration.</p>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-500">Payment history will appear here after billing module rollout.</p>
            </div>
        </div>
    </div>
</div>
