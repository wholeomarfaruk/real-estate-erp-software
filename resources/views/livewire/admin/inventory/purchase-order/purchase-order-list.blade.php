<div x-data x-init="$store.pageName = { name: 'Purchase Orders', slug: 'purchase-orders' }">
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
                <li class="text-sm text-gray-800">Purchase Orders</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Total Orders</p>
            <p class="mt-2 text-2xl font-semibold text-gray-800">{{ number_format($totalOrders) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Draft</p>
            <p class="mt-2 text-2xl font-semibold text-amber-700">{{ number_format($draftOrders) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Pending Approval</p>
            <p class="mt-2 text-2xl font-semibold text-blue-700">{{ number_format($pendingOrders) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs text-gray-500">Approved / Receiving / Completed</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ number_format($approvedOrders) }}</p>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-7">
                <div class="md:col-span-2">
                    <label for="search" class="sr-only">Search</label>
                    <input
                        id="search"
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="PO no, supplier, remarks"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="statusFilter" class="sr-only">Status</label>
                    <select id="statusFilter" wire:model.live="statusFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="storeFilter" class="sr-only">Store</label>
                    <select id="storeFilter" wire:model.live="storeFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Stores</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="supplierFilter" class="sr-only">Supplier</label>
                    <select id="supplierFilter" wire:model.live="supplierFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Suppliers</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="purchaseModeFilter" class="sr-only">Purchase Mode</label>
                    <select id="purchaseModeFilter" wire:model.live="purchaseModeFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Modes</option>
                        @foreach ($purchaseModes as $mode)
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <input type="date" wire:model.live="dateFrom" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <input type="date" wire:model.live="dateTo" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-xs text-gray-800 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>

            @can('inventory.purchase_order.create')
                <div class="mt-4">
                    <a href="{{ route('admin.inventory.purchase-orders.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New Purchase Order
                    </a>
                </div>
            @endcan
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">PO</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Store / Supplier</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Mode</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Amounts</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($purchaseOrders as $order)
                                @php
                                    $statusClass = match ($order->status?->value) {
                                        'approved', 'received', 'completed' => 'bg-emerald-100 text-emerald-700',
                                        'partially_received' => 'bg-blue-100 text-blue-700',
                                        'rejected', 'cancelled' => 'bg-red-100 text-red-700',
                                        'pending_engineer', 'pending_chairman', 'pending_accounts' => 'bg-indigo-100 text-indigo-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    };

                                    $modeClass = $order->purchase_mode?->value === 'credit'
                                        ? 'bg-sky-100 text-sky-700'
                                        : 'bg-zinc-100 text-zinc-700';
                                @endphp
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-medium text-gray-800">{{ $order->po_no }}</p>
                                        <p class="text-xs text-gray-500">{{ optional($order->order_date)->format('d M, Y') }}</p>
                                        <p class="text-xs text-gray-500">Items: {{ $order->items_count }}</p>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p class="font-medium">{{ $order->store?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $order->supplier?->name ?? 'No supplier selected' }}</p>
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $modeClass }}">
                                            {{ $order->purchase_mode?->label() ?? 'N/A' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        <p>Request: {{ number_format((float) $order->fund_request_amount, 2) }}</p>
                                        <p class="text-xs text-gray-500">Approved: {{ number_format((float) $order->approved_amount, 2) }}</p>
                                        <p class="text-xs text-gray-500">Actual: {{ number_format((float) $order->actual_purchase_amount, 2) }}</p>
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                            {{ $order->status?->label() ?? 'N/A' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <span class="sr-only">Open actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-56 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('inventory.purchase_order.view')
                                                    <a href="{{ route('admin.inventory.purchase-orders.view', $order) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        View
                                                    </a>
                                                @endcan

                                                @can('inventory.purchase_order.update')
                                                    @if ($order->status?->value === 'draft')
                                                        <a href="{{ route('admin.inventory.purchase-orders.edit', $order) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Edit Draft
                                                        </a>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_order.submit')
                                                    @if ($order->status?->value === 'draft')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $order->id }},
                                                                method: 'submitOrder',
                                                                title: 'Submit purchase order?',
                                                                text: 'This will send it to engineer approval stage.',
                                                                confirmText: 'Yes, submit'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Submit
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_order.engineer_approve')
                                                    @if ($order->status?->value === 'pending_engineer')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $order->id }},
                                                                method: 'engineerApproveOrder',
                                                                title: 'Engineer approve this PO?',
                                                                text: 'This will forward it to chairman stage.',
                                                                confirmText: 'Yes, approve'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Engineer Approve
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_order.chairman_approve')
                                                    @if ($order->status?->value === 'pending_chairman')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $order->id }},
                                                                method: 'chairmanApproveOrder',
                                                                title: 'Chairman approve this PO?',
                                                                text: 'This will forward it to accounts stage.',
                                                                confirmText: 'Yes, approve'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Chairman Approve
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_order.accounts_approve')
                                                    @if ($order->status?->value === 'pending_accounts')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $order->id }},
                                                                method: 'accountsApproveOrder',
                                                                title: 'Accounts approve this PO?',
                                                                text: 'This will mark PO as approved for procurement.',
                                                                confirmText: 'Yes, approve'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Accounts Approve
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_order.fund_release')
                                                    @if (in_array($order->status?->value, ['approved', 'partially_received', 'received'], true))
                                                        <a href="{{ route('admin.inventory.purchase-orders.funds', $order) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Fund Release
                                                        </a>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_order.settle')
                                                    @if (in_array($order->status?->value, ['approved', 'partially_received', 'received'], true))
                                                        <a href="{{ route('admin.inventory.purchase-orders.settlement', $order) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Settlement
                                                        </a>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_order.complete')
                                                    @if ($order->status?->value === 'received')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $order->id }},
                                                                method: 'completeOrder',
                                                                title: 'Complete this purchase order?',
                                                                text: 'PO will be closed after settlement checks.',
                                                                confirmText: 'Yes, complete'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Complete
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_order.update')
                                                    @if (in_array($order->status?->value, ['draft', 'pending_engineer', 'pending_chairman', 'pending_accounts'], true))
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $order->id }},
                                                                method: 'cancelOrder',
                                                                title: 'Cancel this purchase order?',
                                                                text: 'Cancelled purchase order cannot be processed further.',
                                                                confirmText: 'Yes, cancel'
                                                            })"
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                            Cancel
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('inventory.purchase_order.delete')
                                                    @if ($order->status?->value === 'draft')
                                                        <button type="button" x-data="livewireConfirm"
                                                            @click="confirmAction({
                                                                id: {{ $order->id }},
                                                                method: 'deleteOrder',
                                                                title: 'Delete this draft PO?',
                                                                text: 'This draft purchase order will be deleted permanently.',
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
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <div class="mx-auto max-w-sm">
                                            <p class="text-sm font-medium text-gray-700">No purchase order found</p>
                                            <p class="mt-1 text-xs text-gray-500">Create a new purchase order or adjust the filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($purchaseOrders->hasPages())
                <div class="mt-6">
                    {{ $purchaseOrders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
