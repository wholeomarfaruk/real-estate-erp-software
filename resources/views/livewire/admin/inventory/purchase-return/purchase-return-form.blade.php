<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Edit Purchase Return' : 'Create Purchase Return' }}', slug: 'purchase-returns' }">
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
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.purchase-returns.index') }}">
                        Purchase Return
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800" x-cloak x-text="$store.pageName?.name ?? ''"></li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white px-5 py-4 sm:px-6 sm:py-5">
        @if ($isLocked)
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                This purchase return is {{ $status }} and cannot be edited.
            </div>
        @endif

        <form wire:submit.prevent="saveDraft">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="return_no" class="text-sm font-medium text-gray-700">Return No *</label>
                    <input id="return_no" type="text" wire:model="return_no" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="return_no" class="mt-1" />
                </div>

                <div>
                    <label for="return_date" class="text-sm font-medium text-gray-700">Return Date *</label>
                    <input id="return_date" type="date" wire:model="return_date" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="return_date" class="mt-1" />
                </div>

                <div>
                    <label for="supplier_id" class="text-sm font-medium text-gray-700">Supplier *</label>
                    <select id="supplier_id" wire:model.live="supplier_id" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="supplier_id" class="mt-1" />
                </div>

                <div>
                    <label for="store_id" class="text-sm font-medium text-gray-700">Office Store *</label>
                    <select id="store_id" wire:model.live="store_id" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select office store</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }} ({{ $store->code }})</option>
                        @endforeach
                    </select>
                    <x-input-error for="store_id" class="mt-1" />
                </div>

                <div>
                    <label for="purchase_order_id" class="text-sm font-medium text-gray-700">Linked Purchase Order</label>
                    <select id="purchase_order_id" wire:model="purchase_order_id" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Auto from stock receive / optional</option>
                        @foreach ($purchaseOrders as $purchaseOrder)
                            <option value="{{ $purchaseOrder->id }}">
                                {{ $purchaseOrder->po_no }} - {{ $purchaseOrder->supplier?->name ?? 'No Supplier' }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error for="purchase_order_id" class="mt-1" />
                </div>

                <div>
                    <label for="stock_receive_id" class="text-sm font-medium text-gray-700">Linked Stock Receive *</label>
                    <select id="stock_receive_id" wire:model.live="stock_receive_id" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select stock receive</option>
                        @foreach ($stockReceives as $stockReceive)
                            <option value="{{ $stockReceive->id }}">
                                {{ $stockReceive->receive_no }} - {{ $stockReceive->supplier?->name ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error for="stock_receive_id" class="mt-1" />
                    <p class="mt-1 text-xs text-indigo-600">Rates and items are loaded from this source receive.</p>
                </div>

                <div>
                    <label for="reason" class="text-sm font-medium text-gray-700">Reason</label>
                    <input id="reason" type="text" wire:model="reason" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500"
                        placeholder="Damaged / wrong quality / over-supplied">
                    <x-input-error for="reason" class="mt-1" />
                </div>

                <div class="md:col-span-2 xl:col-span-2">
                    <label for="remarks" class="text-sm font-medium text-gray-700">Remarks</label>
                    <input id="remarks" type="text" wire:model="remarks" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="remarks" class="mt-1" />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Return Items</h3>
                    <p class="text-xs text-gray-500">Unit price is readonly and comes from the original stock receive item.</p>
                </div>
            </div>

            <div class="mt-3 overflow-hidden rounded-xl border border-gray-200">
                <div class="max-w-full overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Original Received</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Already Returned</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Available Stock</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Max Return Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Return Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Unit Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($items as $index => $item)
                                @php
                                    $maxQty = (float) ($item['max_return_quantity'] ?? 0);
                                    $qty = (float) ($item['quantity'] ?? 0);
                                    $exceeds = $qty > ($maxQty + 0.0001);
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 min-w-[250px]">
                                        <input type="hidden" wire:model="items.{{ $index }}.stock_receive_item_id">
                                        <input type="hidden" wire:model="items.{{ $index }}.purchase_order_item_id">
                                        <input type="hidden" wire:model="items.{{ $index }}.product_id">
                                        <p class="text-sm font-medium text-gray-800">{{ $item['product_name'] ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $item['product_sku'] ?: 'No SKU' }}</p>
                                    </td>

                                    <td class="px-4 py-3 min-w-[130px] text-sm text-gray-700">{{ number_format((float) ($item['original_quantity'] ?? 0), 3) }}</td>
                                    <td class="px-4 py-3 min-w-[130px] text-sm text-gray-700">{{ number_format((float) ($item['already_returned_quantity'] ?? 0), 3) }}</td>
                                    <td class="px-4 py-3 min-w-[130px] text-sm text-gray-700">{{ number_format((float) ($item['available_quantity'] ?? 0), 3) }}</td>
                                    <td class="px-4 py-3 min-w-[130px] text-sm {{ $maxQty <= 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ number_format($maxQty, 3) }}</td>

                                    <td class="px-4 py-3 min-w-[150px]">
                                        <input type="number" min="0" step="0.001" wire:model.live="items.{{ $index }}.quantity" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border {{ $exceeds ? 'border-red-300' : 'border-gray-300' }} px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.quantity" class="mt-1" />
                                        @if ($exceeds)
                                            <p class="mt-1 text-xs text-red-600">Entered qty exceeds max return quantity.</p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 min-w-[130px]">
                                        <input type="number" wire:model="items.{{ $index }}.unit_price" disabled
                                            class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-700">
                                    </td>

                                    <td class="px-4 py-3 min-w-[130px]">
                                        <input type="number" wire:model="items.{{ $index }}.total_price" disabled
                                            class="h-10 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 text-sm text-gray-700">
                                    </td>

                                    <td class="px-4 py-3 min-w-[220px]">
                                        <input type="text" wire:model="items.{{ $index }}.remarks" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.remarks" class="mt-1" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-10 text-center text-sm text-gray-500">
                                        Select supplier, office store, and linked stock receive to load returnable items.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <div class="rounded-lg bg-gray-50 px-4 py-3 text-right">
                    <p class="text-xs text-gray-500">Grand Total</p>
                    <p class="text-lg font-semibold text-gray-800">{{ number_format($grandTotal, 2) }}</p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('admin.inventory.purchase-returns.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </a>

                @if (! $isLocked)
                    @can($editMode ? 'inventory.purchase_return.update' : 'inventory.purchase_return.create')
                        <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                            Save Draft
                        </button>
                    @endcan

                    @can('inventory.purchase_return.post')
                        <button type="button" x-data="livewireConfirm"
                            @click="confirmAction({
                                method: 'postNow',
                                title: 'Post this purchase return?',
                                text: 'This will reduce stock and create return movement entries.',
                                confirmText: 'Yes, post now'
                            })"
                            class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Save & Post
                        </button>
                    @endcan
                @endif
            </div>
        </form>
    </div>
</div>
