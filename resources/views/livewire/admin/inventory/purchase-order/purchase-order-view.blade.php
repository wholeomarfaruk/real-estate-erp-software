<div x-data x-init="$store.pageName = { name: 'Purchase Order Details', slug: 'purchase-order-view' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold">Purchase Order Details</h1>

        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500"
                        href="{{ route('admin.dashboard') }}">
                        Dashboard
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500"
                        href="{{ route('admin.inventory.purchase-orders.index') }}">
                        Purchase Orders
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
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

        $modeClass =
            $purchaseOrder->purchase_mode?->value === 'credit'
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
                    <p class="text-lg font-semibold text-gray-800">
                        {{ number_format((float) $purchaseOrder->fund_request_amount, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Approved Amount</p>
                    <p class="text-lg font-semibold text-gray-800">
                        {{ number_format((float) $purchaseOrder->approved_amount, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Fund Released</p>
                    <p class="text-lg font-semibold text-gray-800">{{ number_format($fundReleasedTotal, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Actual Purchase</p>
                    <p class="text-lg font-semibold text-gray-800">
                        {{ number_format((float) $purchaseOrder->actual_purchase_amount, 2) }}</p>
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
            <p class="mt-1 text-xs text-gray-500">Approval, fund and settlement actions are available by role and
                status.</p>

            <div class="mt-4 space-y-2">
                <a href="{{ route('admin.inventory.purchase-orders.index') }}"
                    class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Back to List
                </a>

                <a href="{{ route('admin.inventory.purchase-orders.print', $purchaseOrder) }}" target="_blank"
                    class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Print Template
                </a>

                <a href="{{ route('admin.inventory.purchase-orders.pdf', $purchaseOrder) }}"
                    class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Download PDF
                </a>

                @can('inventory.purchase_order.update')
                    @if ($purchaseOrder->status?->value === 'draft')
                        <a href="{{ route('admin.inventory.purchase-orders.edit', $purchaseOrder) }}"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
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
                                Swal.fire({
                                    title: 'Chairman Approval',
                                    html: '<p class=\'text-sm text-gray-500 mb-3\'>PO: <strong>{{ $purchaseOrder->po_no }}</strong></p><p class=\'text-sm text-gray-500\'>Enter remarks (optional)</p>',
                                    input: 'textarea',
                                    inputPlaceholder: 'Remarks...',
                                    inputAttributes: { rows: 3 },
                                    showCancelButton: true,
                                    confirmButtonText: 'Approve',
                                    cancelButtonText: 'Cancel',
                                    confirmButtonColor: '#0891b2',
                                    reverseButtons: true,
                                    focusCancel: true,
                                }).then((result) => {
                                    if (!result.isConfirmed) return;
                                    $wire.chairmanApprove(result.value || null);
                                });
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
                        <a href="{{ route('admin.inventory.purchase-orders.funds', $purchaseOrder) }}"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Fund Release
                        </a>
                    @endif
                @endcan

                @can('inventory.purchase_order.settle')
                    @if (in_array($purchaseOrder->status?->value, ['approved', 'partially_received', 'received'], true))
                        <a href="{{ route('admin.inventory.purchase-orders.settlement', $purchaseOrder) }}"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
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
                    @if (in_array(
                            $purchaseOrder->status?->value,
                            ['draft', 'pending_engineer', 'pending_chairman', 'pending_accounts'],
                            true))
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
                            {{ $approval->approval_stage?->label() ?? 'Stage' }} -
                            {{ $approval->action?->label() ?? 'Action' }}
                        </p>
                        <p class="text-xs text-gray-500">By: {{ $approval->user?->name ?? 'N/A' }} |
                            {{ optional($approval->created_at)->format('d M, Y h:i A') }}</p>
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
                    <p class="text-sm font-semibold text-gray-800">
                        {{ number_format((float) $purchaseOrder->returned_amount, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Due Amount</p>
                    <p class="text-sm font-semibold text-gray-800">
                        {{ number_format((float) $purchaseOrder->due_amount, 2) }}</p>
                </div>
            </div>

            @if ($purchaseOrder->settlement)
                <div class="mt-4 rounded-lg border border-gray-200 px-4 py-3">
                    <p class="text-xs text-gray-500">Settled By</p>
                    <p class="text-sm font-medium text-gray-800">
                        {{ $purchaseOrder->settlement->settler?->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">
                        {{ optional($purchaseOrder->settlement->settled_at)->format('d M, Y h:i A') ?? 'N/A' }}</p>
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

                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Engineer Approval</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Approval</th>
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

                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-800">{{ $item->product?->name ?? 'N/A' }}
                                            </p>

                                        </div>
                                        <button type="button"
                                            wire:click="openLinkedRequestDetails({{ $item->id }})"
                                            class="inline-flex items-center justify-center rounded-lg bg-blue-100 p-2 text-blue-600 transition hover:bg-blue-200"
                                            title="View product quantities">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ $item->supplier?->name ?? ($purchaseOrder->supplier?->name ?? 'N/A') }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $item->supplier?->code ?? ($purchaseOrder->supplier?->code ?? 'No code') }}
                                        |
                                        {{ $item->supplier?->phone ?? ($purchaseOrder->supplier?->phone ?? 'No phone') }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    {{ number_format((float) $item->quantity, 3) }} x
                                    {{ number_format((float) $item->estimated_unit_price, 2) }}
                                    <p class="text-xs text-gray-500">
                                        {{ number_format((float) $item->estimated_total_price, 2) }}</p>
                                </td>

                                <td class="px-5 py-4 text-xs text-gray-700">
                                    @can('inventory.purchase_order.engineer_approve')
                                        @if ($purchaseOrder->status?->value === 'pending_engineer')
                                            <div class="grid grid-cols-1 gap-1">
                                                <input type="number" step="0.001" min="0"
                                                    wire:model.live.debounce.300ms="engineerItemApprovals.{{ $item->id }}.approved_quantity"
                                                    class="w-24 rounded border-gray-300 px-2 py-1 text-xs"
                                                    placeholder="Qty">
                                                <input type="number" step="0.01" min="0"
                                                    wire:model.live.debounce.300ms="engineerItemApprovals.{{ $item->id }}.approved_unit_price"
                                                    class="w-24 rounded border-gray-300 px-2 py-1 text-xs"
                                                    placeholder="Unit">
                                                <input type="number" step="0.01" min="0"
                                                    wire:model.live.debounce.300ms="engineerItemApprovals.{{ $item->id }}.approved_total_price"
                                                    class="w-24 rounded border-gray-300 bg-gray-50 px-2 py-1 text-xs"
                                                    placeholder="Total" readonly>
                                            </div>
                                        @else
                                            {{ number_format((float) ($item->eng_approved_quantity ?? 0.0), 3) }} x
                                            {{ number_format((float) ($item->eng_approved_unit_price ?? 0.0), 2) }}
                                            <p class="text-xs text-gray-500">
                                                {{ number_format((float) ($item->eng_approved_total_price ?? 0.0), 2) }}
                                            </p>
                                        @endif
                                    @else
                                        {{ number_format((float) ($item->eng_approved_quantity ?? 0.0), 3) }} x
                                        {{ number_format((float) ($item->eng_approved_unit_price ?? 0.0), 2) }}
                                        <p class="text-xs text-gray-500">
                                            {{ number_format((float) ($item->eng_approved_total_price ?? 0.0), 2) }}</p>
                                    @endcan
                                </td>
                                <td class="px-5 py-4 text-xs text-gray-700">
                                    @can(['inventory.purchase_order.approvals.update'])
                                     
                                        @if (true)
                                            <div class="grid grid-cols-1 gap-1">
                                                <input type="number" step="0.001" min="0"
                                                    wire:model.live.debounce.300ms="chairmanItemApprovals.{{ $item->id }}.approved_quantity"
                                                    class="w-24 rounded border-gray-300 px-2 py-1 text-xs"
                                                    placeholder="Qty">
                                                <input type="number" step="0.01" min="0"
                                                    wire:model.live.debounce.300ms="chairmanItemApprovals.{{ $item->id }}.approved_unit_price"
                                                    class="w-24 rounded border-gray-300 px-2 py-1 text-xs"
                                                    placeholder="Unit">
                                                <input type="number" step="0.01" min="0"
                                                    wire:model.live.debounce.300ms="chairmanItemApprovals.{{ $item->id }}.approved_total_price"
                                                    class="w-24 rounded border-gray-300 bg-gray-50 px-2 py-1 text-xs"
                                                    placeholder="Total" readonly>
                                            </div>
                                        @else
                                            {{ number_format((float) ($item->approved_quantity ?? 0.0), 3) }} x
                                            {{ number_format((float) ($item->approved_unit_price ?? 0.0), 2) }}
                                            <p class="text-xs text-gray-500">
                                                {{ number_format((float) ($item->approved_total_price ?? 0.0), 2) }}</p>
                                        @endif
                                    @else
                                        {{ number_format((float) ($item->approved_quantity ?? 0.0), 3) }} x
                                        {{ number_format((float) ($item->approved_unit_price ?? 0.0), 2) }}
                                        <p class="text-xs text-gray-500">
                                            {{ number_format((float) ($item->approved_total_price ?? 0.0), 2) }}</p>
                                    @endcan
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    {{ number_format($summary['required_qty'], 3) }}</td>
                                <td class="px-5 py-4 text-sm text-emerald-700">
                                    {{ number_format($summary['received_qty'], 3) }}</td>
                                <td
                                    class="px-5 py-4 text-sm {{ $summary['remaining_qty'] > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                    {{ number_format($summary['remaining_qty'], 3) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-10 text-center text-sm text-gray-500">No items
                                    found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            @can('inventory.purchase_order.engineer_approve')
                @if ($purchaseOrder->status?->value === 'pending_engineer')
                    <button type="button" wire:click="saveEngineerItemApprovals"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                        Save Engineer Item Approvals
                    </button>
                @endif
            @endcan
            @can('inventory.purchase_order.approvals.update')
               
                    <button type="button" wire:click="saveApprovalsItems"
                        class="inline-flex items-center rounded-lg bg-cyan-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-cyan-700">
                        Save Approval Items
                    </button>
            
            @endcan
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
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ optional($fund->release_date)->format('d M, Y') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $fund->release_type?->label() ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ number_format((float) $fund->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $fund->releaser?->name ?? 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No fund
                                        release recorded yet.</td>
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
                                        <a href="{{ route('admin.inventory.stock-receives.view', $receive) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-700">
                                            {{ $receive->receive_no }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ optional($receive->receive_date)->format('d M, Y') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $receive->store?->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $receiveStatusClass }}">
                                            {{ $receive->status?->label() ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ number_format((float) ($receive->grand_total ?? 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No stock
                                        receive linked yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Quantity Details Modal -->
    @if ($quantityDetails)
        @php
            $details = $quantityDetails;
            $itemIndex = $details['itemIndex'] ?? null;
            $stockRequestIds = $details['stockRequestIds'] ?? [];
            $item = $itemIndex !== null ? $items[$itemIndex] ?? null : null;
            $product = $item ? \App\Models\Product::find($item['product_id']) : null;
            $stockRequests = collect();

            if ($product && is_array($stockRequestIds) && count($stockRequestIds)) {
                $stockRequests = \App\Models\StockRequest::with('items.product')
                    ->whereIn('id', $stockRequestIds)
                    ->get();
            }
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            wire:click="closeQuantityModal">
            <div class="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" @click.stop>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Quantity Details</h3>
                    <button type="button" wire:click="closeQuantityModal"
                        class="text-gray-400 transition hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($product && $stockRequests->isNotEmpty())
                    <div class="mt-4 space-y-4">
                        <div>
                            <p class="text-xs font-medium text-gray-500">Product</p>
                            <p class="mt-1 text-sm font-medium text-gray-800">{{ $product->name }}</p>
                        </div>

                        <div class="grid gap-3">
                            @foreach ($stockRequests as $stockRequest)
                                @php
                                    $requestItem = $stockRequest->items->firstWhere('product_id', $product->id);
                                    $requestedQty = $requestItem
                                        ? ($requestItem->approved_quantity ?:
                                        $requestItem->quantity)
                                        : 0;
                                    $fulfilledQty = $requestItem ? $requestItem->fulfilled_quantity : 0;
                                    $remainingQty = $requestedQty - $fulfilledQty;
                                    $currentStock = 0;
                                    $toPurchase = max(0, $remainingQty - $currentStock);
                                @endphp

                                <div class="rounded-2xl border border-gray-200 bg-slate-50 p-4">
                                    <p class="text-xs font-medium text-gray-500">Stock Request</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-800">
                                        {{ $stockRequest->request_no }} - {{ $stockRequest->requesterStore?->name }}
                                    </p>

                                    <div class="mt-4 grid grid-cols-2 gap-3">
                                        <div class="rounded-lg bg-blue-50 px-3 py-2">
                                            <p class="text-xs text-blue-700">Total Requested</p>
                                            <p class="mt-1 text-sm font-semibold text-blue-700">
                                                {{ number_format($requestedQty, 3) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-emerald-50 px-3 py-2">
                                            <p class="text-xs text-emerald-700">Already Fulfilled</p>
                                            <p class="mt-1 text-sm font-semibold text-emerald-700">
                                                {{ number_format($fulfilledQty, 3) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-amber-50 px-3 py-2">
                                            <p class="text-xs text-amber-700">Remaining</p>
                                            <p class="mt-1 text-sm font-semibold text-amber-700">
                                                {{ number_format($remainingQty, 3) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-gray-50 px-3 py-2">
                                            <p class="text-xs text-gray-700">Current Stock</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-700">
                                                {{ number_format($currentStock, 3) }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4 rounded-lg bg-indigo-50 px-3 py-2">
                                        <p class="text-xs text-indigo-700">Quantity to Purchase</p>
                                        <p class="mt-1 text-lg font-semibold text-indigo-700">
                                            {{ number_format($toPurchase, 3) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="closeQuantityModal"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" wire:click="confirmLink"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                        Confirm Link
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($linkedRequestDetails)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            wire:click="closeLinkedRequestDetails">
            <div class="w-full max-w-3xl rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" @click.stop>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Linked Request Details</h3>
                        <p class="text-sm text-gray-500">Product: {{ $linkedRequestDetails['product_name'] ?? 'N/A' }}
                            {{ $linkedRequestDetails['product_unit'] ?? '' }}</p>
                    </div>
                    <button type="button" wire:click="closeLinkedRequestDetails"
                        class="text-gray-400 transition hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg bg-blue-50 px-4 py-3">
                            <p class="text-xs font-medium text-blue-700">Total Requested</p>
                            <p class="mt-1 text-lg font-semibold text-blue-900">
                                {{ number_format($linkedRequestDetails['total_requested'] ?? 0, 3) }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-medium text-emerald-700">Already Fulfilled</p>
                            <p class="mt-1 text-lg font-semibold text-emerald-900">
                                {{ number_format($linkedRequestDetails['total_fulfilled'] ?? 0, 3) }}</p>
                        </div>
                        <div class="rounded-lg bg-amber-50 px-4 py-3">
                            <p class="text-xs font-medium text-amber-700">Remaining Qty</p>
                            <p class="mt-1 text-lg font-semibold text-amber-900">
                                {{ number_format($linkedRequestDetails['total_remaining'] ?? 0, 3) }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-4 py-3">
                            <p class="text-xs font-medium text-slate-700">Office Stock</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">
                                {{ number_format($linkedRequestDetails['office_stock'] ?? 0, 3) }}</p>
                        </div>
                    </div>

                    <div class="rounded-lg bg-indigo-50 px-4 py-3">
                        <p class="text-xs font-medium text-indigo-700">Need to Purchase</p>
                        <p class="mt-1 text-2xl font-semibold text-indigo-900">
                            {{ number_format($linkedRequestDetails['need_to_purchase'] ?? 0, 3) }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <h4 class="text-sm font-semibold text-gray-800">Linked Requests</h4>
                        <div class="mt-3 space-y-3">
                            @foreach ($linkedRequestDetails['requests'] ?? [] as $request)
                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                    <div
                                        class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-700">
                                        <span class="font-medium text-gray-900">{{ $request['request_no'] }}</span>
                                        <span>{{ $request['requester_name'] ?? 'Unknown' }}</span>
                                    </div>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-lg bg-blue-50 px-3 py-2">
                                            <p class="text-xs text-blue-700">Requested</p>
                                            <p class="mt-1 text-sm font-semibold text-blue-900">
                                                {{ number_format($request['requested_quantity'], 3) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-emerald-50 px-3 py-2">
                                            <p class="text-xs text-emerald-700">Fulfilled</p>
                                            <p class="mt-1 text-sm font-semibold text-emerald-900">
                                                {{ number_format($request['fulfilled_quantity'], 3) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-amber-50 px-3 py-2">
                                            <p class="text-xs text-amber-700">Remaining</p>
                                            <p class="mt-1 text-sm font-semibold text-amber-900">
                                                {{ number_format($request['remaining_quantity'], 3) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" wire:click="closeLinkedRequestDetails"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
