<div x-data x-init="$store.pageName = { name: 'Purchase Settlement', slug: 'purchase-orders-settlement' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold">Purchase Order Settlement</h1>

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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.purchase-orders.view', $purchaseOrder) }}">
                        {{ $purchaseOrder->po_no }}
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800">Settlement</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 xl:col-span-2">
            <h3 class="text-base font-semibold text-gray-800">Settle Purchase Order</h3>
            <p class="mt-1 text-xs text-gray-500">Accounts team should settle released fund against actual purchase and close the PO.</p>

            <form wire:submit.prevent="save" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Total Fund Released</label>
                    <input type="text" value="{{ number_format($totalFundReleased, 2) }}" disabled
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-800">
                </div>

                <div>
                    <label for="actual_purchase_amount" class="text-sm font-medium text-gray-700">Actual Purchase Amount *</label>
                    <input id="actual_purchase_amount" type="number" min="0" step="0.01" wire:model.live="actual_purchase_amount"
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <x-input-error for="actual_purchase_amount" class="mt-1" />
                </div>

                <div>
                    <label for="returned_cash_amount" class="text-sm font-medium text-gray-700">Returned Cash Amount</label>
                    <input id="returned_cash_amount" type="number" min="0" step="0.01" wire:model="returned_cash_amount"
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <x-input-error for="returned_cash_amount" class="mt-1" />
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Calculated Due</label>
                    <input type="text" value="{{ number_format($calculatedDue, 2) }}" disabled
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-800">
                </div>

                <div class="md:col-span-2">
                    <label for="remarks" class="text-sm font-medium text-gray-700">Remarks</label>
                    <input id="remarks" type="text" wire:model="remarks"
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                        placeholder="Settlement note">
                    <x-input-error for="remarks" class="mt-1" />
                </div>

                <div class="md:col-span-2 flex flex-wrap items-center justify-end gap-3">
                    <a href="{{ route('admin.inventory.purchase-orders.view', $purchaseOrder) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </a>

                    <button type="submit" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                        Save Settlement
                    </button>

                    @if ($canCompleteNow && auth()->user()?->can('inventory.purchase_order.complete'))
                        <button type="button" wire:click="saveAndComplete" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Save & Complete
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h3 class="text-base font-semibold text-gray-800">PO Snapshot</h3>
            <div class="mt-4 space-y-3 text-sm text-gray-700">
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">PO No</p>
                    <p class="font-medium text-gray-800">{{ $purchaseOrder->po_no }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Status</p>
                    <p class="font-medium text-gray-800">{{ $purchaseOrder->status?->label() ?? 'N/A' }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Approved Amount</p>
                    <p class="font-medium text-gray-800">{{ number_format((float) $purchaseOrder->approved_amount, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Actual Purchase</p>
                    <p class="font-medium text-gray-800">{{ number_format((float) $purchaseOrder->actual_purchase_amount, 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Current Due</p>
                    <p class="font-medium text-gray-800">{{ number_format((float) $purchaseOrder->due_amount, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
