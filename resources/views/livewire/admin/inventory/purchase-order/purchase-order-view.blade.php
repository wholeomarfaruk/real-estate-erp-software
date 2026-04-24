<div x-data x-init="$store.pageName = { name: 'Purchase Order Details', slug: 'purchase-order-view' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold">Purchase Order Details</h1>

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
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.purchase-orders.index') }}">
                        Purchase Orders
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">{{ $purchaseOrder->po_no }}</li>
            </ol>
        </nav>
    </div>

    @php
        $statusClass = match ($purchaseOrder->status?->value) {
            'approved', 'received', 'completed' => 'bg-emerald-100 text-emerald-700',
            'partially_received' => 'bg-blue-100 text-blue-700',
            'rejected', 'cancelled' => 'bg-red-100 text-red-700',
            'pending_engineer', 'pending_chairman', 'pending_accounts' => 'bg-indigo-100 text-indigo-700',
            default => 'bg-amber-100 text-amber-700',
        };

        $modeClass = $purchaseOrder->purchase_mode?->value === 'credit'
            ? 'bg-sky-100 text-sky-700'
            : 'bg-zinc-100 text-zinc-700';
    @endphp

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 xl:col-span-3">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">PO No</p>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $purchaseOrder->po_no }}</h2>
                    <p class="text-xs text-gray-500">{{ optional($purchaseOrder->order_date)->format('d M, Y') }}</p>
                </div>
                <div class="space-y-2 text-right">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $modeClass }}">
                        {{ $purchaseOrder->purchase_mode?->label() ?? 'N/A' }}
                    </span>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                        {{ $purchaseOrder->status?->label() ?? 'N/A' }}
                    </span>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-4">
                <div>
                    <p class="text-xs text-gray-500">Requester</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseOrder->requester?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Store</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseOrder->store?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Supplier</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseOrder->supplier?->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Supplier Phone</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseOrder->supplier?->phone ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Requested Amount</p>
                    <p class="text-lg font-semibold text-gray-800">{{ number_format((float) $purchaseOrder->fund_request_amount, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Approved Amount</p>
                    <p class="text-lg font-semibold text-gray-800">{{ number_format((float) $purchaseOrder->approved_amount, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Fund Released</p>
                    <p class="text-lg font-semibold text-gray-800">{{ number_format($fundReleasedTotal, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Actual Purchase</p>
                    <p class="text-lg font-semibold text-gray-800">{{ number_format((float) $purchaseOrder->actual_purchase_amount, 2) }}</p>
                </div>
            </div>

            @if ($purchaseOrder->remarks)
                <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Remarks</p>
                    <p class="text-sm text-gray-700">{{ $purchaseOrder->remarks }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Actions</h3>
            <p class="mt-1 text-xs text-gray-500">Approval, fund and settlement actions are available by role and status.</p>

            <div class="mt-4 space-y-2">
                <a href="{{ route('admin.inventory.purchase-orders.index') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Back to List
                </a>

                <a href="{{ route('admin.inventory.purchase-orders.print', $purchaseOrder) }}" target="_blank" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Print Template
                </a>

                <a href="{{ route('admin.inventory.purchase-orders.pdf', $purchaseOrder) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Download PDF
                </a>

                @can('inventory.purchase_order.update')
                    @if ($purchaseOrder->status?->value === 'draft')
                        <a href="{{ route('admin.inventory.purchase-orders.edit', $purchaseOrder) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Edit Draft
                        </a>
                    @endif
                @endcan

                @can('inventory.purchase_order.submit')
                    @if ($purchaseOrder->status?->value === 'draft')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'submitOrder',
                                title: 'Submit purchase order?',
                                text: 'This will move PO to engineer approval stage.',
                                confirmText: 'Yes, submit'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                            Submit
                        </button>
                    @endif
                @endcan

                @can('inventory.purchase_order.engineer_approve')
                    @if ($purchaseOrder->status?->value === 'pending_engineer')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'engineerApprove',
                                title: 'Engineer approve this PO?',
                                text: 'This will move PO to chairman approval stage.',
                                confirmText: 'Yes, approve'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                            Engineer Approve
                        </button>
                    @endif
                @endcan

                @can('inventory.purchase_order.chairman_approve')
                    @if ($purchaseOrder->status?->value === 'pending_chairman')
                        <button type="button"
                            @click="
                                const amount = prompt('Enter approved amount', '{{ number_format((float) $purchaseOrder->fund_request_amount, 2, '.', '') }}');
                                if (amount === null) return;
                                $wire.chairmanApprove(amount);
                            "
                            class="inline-flex w-full items-center justify-center rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-cyan-700">
                            Chairman Approve
                        </button>
                    @endif
                @endcan

                @can('inventory.purchase_order.accounts_approve')
                    @if ($purchaseOrder->status?->value === 'pending_accounts')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'accountsApprove',
                                title: 'Accounts approve this PO?',
                                text: 'PO will become approved and ready for purchase workflow.',
                                confirmText: 'Yes, approve'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Accounts Approve
                        </button>
                    @endif
                @endcan

                @can('inventory.purchase_order.fund_release')
                    @if (in_array($purchaseOrder->status?->value, ['approved', 'partially_received', 'received'], true))
                        <a href="{{ route('admin.inventory.purchase-orders.funds', $purchaseOrder) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Fund Release
                        </a>
                    @endif
                @endcan

                @can('inventory.purchase_order.settle')
                    @if (in_array($purchaseOrder->status?->value, ['approved', 'partially_received', 'received'], true))
                        <a href="{{ route('admin.inventory.purchase-orders.settlement', $purchaseOrder) }}" class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Settlement
                        </a>
                    @endif
                @endcan

                @can('inventory.purchase_order.complete')
                    @if ($purchaseOrder->status?->value === 'received')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'completeOrder',
                                title: 'Complete this purchase order?',
                                text: 'PO will be closed and moved to completed status.',
                                confirmText: 'Yes, complete'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-800">
                            Complete PO
                        </button>
                    @endif
                @endcan

                @can('inventory.purchase_order.update')
                    @if (in_array($purchaseOrder->status?->value, ['draft', 'pending_engineer', 'pending_chairman', 'pending_accounts'], true))
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'cancelOrder',
                                title: 'Cancel this purchase order?',
                                text: 'Cancelled purchase order cannot be processed further.',
                                confirmText: 'Yes, cancel'
                            })"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                            Cancel PO
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Approval Timeline</h3>
            <div class="mt-4 space-y-3">
                @forelse ($purchaseOrder->approvals as $approval)
                    <div class="rounded-lg border border-gray-200 px-4 py-3">
                        <p class="text-sm font-medium text-gray-800">
                            {{ $approval->approval_stage?->label() ?? 'Stage' }} - {{ $approval->action?->label() ?? 'Action' }}
                        </p>
                        <p class="text-xs text-gray-500">By: {{ $approval->user?->name ?? 'N/A' }} | {{ optional($approval->created_at)->format('d M, Y h:i A') }}</p>
                        @if ($approval->remarks)
                            <p class="mt-1 text-xs text-gray-600">{{ $approval->remarks }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No approval action yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Settlement Snapshot</h3>
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Fund Released</p>
                    <p class="text-sm font-semibold text-gray-800">{{ number_format($fundReleasedTotal, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Received Value</p>
                    <p class="text-sm font-semibold text-gray-800">{{ number_format($receivedValueTotal, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Returned Amount</p>
                    <p class="text-sm font-semibold text-gray-800">{{ number_format((float) $purchaseOrder->returned_amount, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Due Amount</p>
                    <p class="text-sm font-semibold text-gray-800">{{ number_format((float) $purchaseOrder->due_amount, 2) }}</p>
                </div>
            </div>

            @if ($purchaseOrder->settlement)
                <div class="mt-4 rounded-lg border border-gray-200 px-4 py-3">
                    <p class="text-xs text-gray-500">Settled By</p>
                    <p class="text-sm font-medium text-gray-800">{{ $purchaseOrder->settlement->settler?->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">{{ optional($purchaseOrder->settlement->settled_at)->format('d M, Y h:i A') ?? 'N/A' }}</p>
                    @if ($purchaseOrder->settlement->remarks)
                        <p class="mt-1 text-xs text-gray-600">{{ $purchaseOrder->settlement->remarks }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h3 class="text-base font-semibold text-gray-800">PO Items and Receive Progress</h3>
        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
            <div class="max-w-full overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Product</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Supplier</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Estimated</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Approved</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Required Qty</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Received Qty</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Remaining Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($itemSummaries as $summary)
                            @php
                                $item = $summary['item'];
                            @endphp
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800">{{ $item->product?->name ?? 'N/A' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800">{{ $item->supplier?->name ?? $purchaseOrder->supplier?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $item->supplier?->code ?? $purchaseOrder->supplier?->code ?? 'No code' }}
                                        | {{ $item->supplier?->phone ?? $purchaseOrder->supplier?->phone ?? 'No phone' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    {{ number_format((float) $item->quantity, 3) }} x {{ number_format((float) $item->estimated_unit_price, 2) }}
                                    <p class="text-xs text-gray-500">{{ number_format((float) $item->estimated_total_price, 2) }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    {{ number_format((float) ($item->approved_quantity ?? $item->quantity), 3) }} x {{ number_format((float) ($item->approved_unit_price ?? $item->estimated_unit_price), 2) }}
                                    <p class="text-xs text-gray-500">{{ number_format((float) ($item->approved_total_price ?? $item->estimated_total_price), 2) }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ number_format($summary['required_qty'], 3) }}</td>
                                <td class="px-5 py-4 text-sm text-emerald-700">{{ number_format($summary['received_qty'], 3) }}</td>
                                <td class="px-5 py-4 text-sm {{ $summary['remaining_qty'] > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                    {{ number_format($summary['remaining_qty'], 3) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Fund Release History</h3>
            <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
                <div class="max-w-full overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Released By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($purchaseOrder->funds as $fund)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ optional($fund->release_date)->format('d M, Y') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $fund->release_type?->label() ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $fund->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $fund->releaser?->name ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No fund release recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">Linked Stock Receives</h3>
            <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
                <div class="max-w-full overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Receive No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Store</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($purchaseOrder->stockReceives as $receive)
                                @php
                                    $receiveStatusClass = match ($receive->status?->value) {
                                        'posted' => 'bg-emerald-100 text-emerald-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <a href="{{ route('admin.inventory.stock-receives.view', $receive) }}" class="font-medium text-indigo-600 hover:text-indigo-700">
                                            {{ $receive->receive_no }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ optional($receive->receive_date)->format('d M, Y') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $receive->store?->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $receiveStatusClass }}">
                                            {{ $receive->status?->label() ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) ($receive->grand_total ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No stock receive linked yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
