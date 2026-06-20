<div x-data x-init="$store.pageName = { name: 'Purchase Invoice', slug: 'purchase-invoices' }">

    {{-- Breadcrumb --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-800">Dashboard</a></li>
                <li>/</li>
                <li><a href="{{ route('admin.inventory.purchase-invoices.index') }}" class="hover:text-gray-800">Purchase Invoices</a></li>
                <li>/</li>
                <li class="text-gray-800">{{ $invoice->invoice_no }}</li>
            </ol>
        </nav>
        <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $invoice->status?->badgeClass() }}">
            {{ $invoice->status?->label() }}
        </span>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ================================================================
             LEFT / MAIN COLUMN
             ================================================================ --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Invoice header (read-only) --}}
            <div class="rounded-2xl border border-gray-200 bg-white px-6 py-5">
                <h2 class="text-sm font-semibold text-gray-700">Invoice Details</h2>
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm md:grid-cols-3">
                    <div>
                        <p class="text-xs text-gray-500">Invoice No</p>
                        <p class="font-medium text-gray-800">{{ $invoice->invoice_no }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Invoice Date</p>
                        <p class="font-medium text-gray-800">{{ optional($invoice->invoice_date)->format('d M, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Supplier</p>
                        <p class="font-medium text-gray-800">{{ $invoice->supplier?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Stock Receive</p>
                        <p class="font-medium text-gray-800">{{ $invoice->stockReceive?->receive_no ?? '—' }}</p>
                    </div>
                    @if ($invoice->purchaseOrder)
                    <div>
                        <p class="text-xs text-gray-500">Purchase Order</p>
                        <p class="font-medium text-gray-800">{{ $invoice->purchaseOrder->po_no }}</p>
                    </div>
                    @endif
                    @if ($isPosted && $invoice->approver)
                    <div>
                        <p class="text-xs text-gray-500">Approved By</p>
                        <p class="font-medium text-gray-800">{{ $invoice->approver->name }}</p>
                        <p class="text-xs text-gray-400">{{ optional($invoice->confirmed_at)->format('d M, Y H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Line items --}}
            <div class="rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-700">Items</h2>
                    @if ($isEditable)
                        <p class="mt-0.5 text-xs text-gray-400">You can adjust unit price and item-level discount before approving.</p>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Product</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Qty</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 w-32">Unit Price</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 w-32">Item Disc.</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($items as $index => $item)
                                <tr>
                                    <td class="px-5 py-3 text-sm text-gray-800">
                                        {{ $item['product_name'] }}
                                        @if ($item['product_unit'])
                                            <span class="text-xs text-gray-400">({{ $item['product_unit'] }})</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right text-sm text-gray-700">
                                        {{ number_format($item['qty'], 3) }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        @if ($isEditable)
                                            <input type="number" step="0.001" min="0"
                                                wire:model.lazy="items.{{ $index }}.unit_price"
                                                class="h-8 w-28 rounded border border-gray-300 px-2 text-right text-sm focus:border-indigo-500 focus:outline-none">
                                        @else
                                            <span class="text-sm text-gray-700">{{ number_format($item['unit_price'], 3) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        @if ($isEditable)
                                            <input type="number" step="0.001" min="0"
                                                wire:model.lazy="items.{{ $index }}.discount_amount"
                                                class="h-8 w-28 rounded border border-gray-300 px-2 text-right text-sm focus:border-indigo-500 focus:outline-none">
                                        @else
                                            <span class="text-sm text-gray-700">{{ number_format($item['discount_amount'], 3) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right text-sm font-medium text-gray-800">
                                        {{ number_format($item['total_amount'], 3) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- ================================================================
             RIGHT COLUMN — totals + accounting + actions
             ================================================================ --}}
        <div class="space-y-6">

            {{-- Totals card --}}
            <div class="rounded-2xl border border-gray-200 bg-white px-6 py-5">
                <h2 class="text-sm font-semibold text-gray-700">Summary</h2>

                @if ($isEditable)
                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Invoice Discount</label>
                            <input type="number" step="0.001" min="0"
                                wire:model.lazy="discount_amount"
                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-right text-sm focus:border-indigo-500 focus:outline-none">
                        </div>
                    </div>
                @endif

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="font-medium text-gray-800">{{ number_format($subtotal, 2) }}</dd>
                    </div>
                    @if ($discount_amount > 0)
                    <div class="flex justify-between text-red-600">
                        <dt>Discount</dt>
                        <dd>- {{ number_format($discount_amount, 2) }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-semibold text-gray-800">
                        <dt>Total</dt>
                        <dd>{{ number_format($total_amount, 2) }}</dd>
                    </div>
                    @if ($isPosted && $paid_amount > 0)
                    <div class="flex justify-between text-emerald-600">
                        <dt>Paid (historical)</dt>
                        <dd>- {{ number_format($paid_amount, 2) }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between {{ $due_amount > 0 ? 'text-red-600' : 'text-gray-400' }} font-semibold">
                        <dt>Payable (AP)</dt>
                        <dd>{{ number_format($due_amount, 2) }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Additional details --}}
            <div class="rounded-2xl border border-gray-200 bg-white px-6 py-5">
                <h2 class="text-sm font-semibold text-gray-700">Details</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Supplier Invoice No</label>
                        @if ($isEditable)
                            <input type="text" wire:model.lazy="supplier_invoice_no"
                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none"
                                placeholder="Supplier's ref number">
                        @else
                            <p class="mt-1 text-sm text-gray-800">{{ $supplier_invoice_no ?: '—' }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Payment Due Date</label>
                        @if ($isEditable)
                            <input type="date" wire:model.lazy="due_date"
                                class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none flatpickr-only-date" />
                        @else
                            <p class="mt-1 text-sm text-gray-800">{{ $due_date ? \Carbon\Carbon::parse($due_date)->format('d M, Y') : '—' }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Remarks</label>
                        @if ($isEditable)
                            <textarea wire:model.lazy="remarks" rows="2"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                                placeholder="Optional notes…"></textarea>
                        @else
                            <p class="mt-1 text-sm text-gray-800">{{ $remarks ?: '—' }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action buttons --}}
            @if ($isEditable)
                @can('inventory.purchase_invoice.approve')
                    <div class="space-y-3">
                        <button type="button" wire:click="approve"
                            wire:confirm="Post accounting entries and approve this invoice?"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Approve &amp; Post
                        </button>

                        <button type="button" wire:click="saveDraft"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Save Draft
                        </button>
                    </div>
                @endcan
            @endif

            @if ($isPosted)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    Invoice approved. Additional payments must be recorded from the <strong>Payment Module</strong>.
                </div>
            @endif
        </div>
    </div>
</div>
