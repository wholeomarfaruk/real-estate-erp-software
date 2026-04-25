<div x-data x-init="$store.pageName = { name: '{{ $isPostedAdjustmentMode ? 'Adjust Stock Receive' : ($editMode ? 'Edit Stock Receive' : 'Create Stock Receive') }}', slug: 'stock-receives' }">
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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.stock-receives.index') }}">
                        Stock Receive
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
                {{ $lockMessage ?: 'This stock receive is '.$status.' and cannot be edited.' }}
            </div>
        @elseif ($isPostedAdjustmentMode)
            <div class="mb-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700">
                Posted stock receive can be adjusted before settlement. Purchase order, supplier, store, and item structure are locked to keep stock history consistent.
            </div>
        @endif

        <form wire:submit.prevent="saveChanges">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="receive_no" class="text-sm font-medium text-gray-700">Receive No *</label>
                    <input id="receive_no" type="text" wire:model="receive_no" @disabled($isLocked || $isPostedAdjustmentMode)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="receive_no" class="mt-1" />
                </div>

                <div>
                    <label for="receive_date" class="text-sm font-medium text-gray-700">Receive Date *</label>
                    <input id="receive_date" type="date" wire:model="receive_date" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="receive_date" class="mt-1" />
                </div>

                <div>
                    <label for="purchase_order_id" class="text-sm font-medium text-gray-700">Linked Purchase Order</label>
                    <select id="purchase_order_id" wire:model.live="purchase_order_id" @disabled($isLocked || $isStructureLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">No purchase order</option>
                        @foreach ($purchaseOrders as $purchaseOrder)
                            <option value="{{ $purchaseOrder->id }}">
                                {{ $purchaseOrder->po_no }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error for="purchase_order_id" class="mt-1" />
                    @if ($poSelectionNotice)
                        <p class="mt-1 text-xs text-amber-600">{{ $poSelectionNotice }}</p>
                    @endif
                    @if ($poLinked)
                        <p class="mt-1 text-xs text-indigo-600">Only pending PO items are allowed in this receive.</p>
                    @endif
                </div>

                <div>
                    <label for="supplier_id" class="text-sm font-medium text-gray-700">Supplier</label>
                    <select id="supplier_id" wire:model="supplier_id" @disabled($isLocked || $isStructureLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="supplier_id" class="mt-1" />
                </div>

                <div>
                    <label for="supplier_voucher" class="text-sm font-medium text-gray-700">Supplier Voucher</label>
                    <input id="supplier_voucher" type="text" wire:model="supplier_voucher" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="supplier_voucher" class="mt-1" />
                </div>
                <div>
                    <label for="store_receive_number" class="text-sm font-medium text-gray-700">Store Receive Number</label>
                    <input id="store_receive_number" type="text" wire:model="store_receive_number" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="store_receive_number" class="mt-1" />
                </div>

                <div>
                    <label for="store_id" class="text-sm font-medium text-gray-700">Office Store *</label>
                    <select id="store_id" wire:model="store_id" @disabled($isLocked || $isStructureLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select office store</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }} ({{ $store->code }})</option>
                        @endforeach
                    </select>
                    <x-input-error for="store_id" class="mt-1" />
                </div>

                <div class="md:col-span-2 xl:col-span-2">
                    <label for="remarks" class="text-sm font-medium text-gray-700">Remarks</label>
                    <input id="remarks" type="text" wire:model="remarks" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="remarks" class="mt-1" />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800">Receive Items</h3>
                @if (! $isLocked && ! $isStructureLocked && ! $poLinked)
                    <button type="button" wire:click="addItem" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Item
                    </button>
                @endif
            </div>

            <div class="mt-3 overflow-hidden rounded-xl border border-gray-200">
                <div class="max-w-full overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Product *</th>
                                @if ($poLinked)
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">{{ $isPostedAdjustmentMode ? 'Max Qty' : 'PO Pending' }}</th>
                                @endif
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Quantity *</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Unit Price *</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Total *</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($items as $index => $item)
                                @php
                                    $pendingQty = $this->pendingQuantityForIndex($index);
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 min-w-[260px]">
                                        <input type="hidden" wire:model="items.{{ $index }}.id">
                                        <input type="hidden" wire:model="items.{{ $index }}.purchase_order_item_id">
                                        <select wire:model="items.{{ $index }}.product_id" @disabled($isLocked || $isStructureLocked || $poLinked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                            <option value="">Select product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error for="items.{{ $index }}.product_id" class="mt-1" />
                                    </td>

                                    @if ($poLinked)
                                        <td class="px-4 py-3 min-w-[130px]">
                                            <p class="text-sm text-gray-700">{{ number_format($pendingQty, 3) }}</p>
                                        </td>
                                    @endif

                                    <td class="px-4 py-3 min-w-[140px]">
                                        <input type="number" min="0.001" step="0.001" wire:model.live="items.{{ $index }}.quantity" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.quantity" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[140px]">
                                        <input type="number" min="0" step="0.01" wire:model.live="items.{{ $index }}.unit_price" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.unit_price" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[140px]">
                                        <input type="number" min="0" step="0.01" wire:model.live="items.{{ $index }}.total_price" @disabled($isLocked || $isPostedAdjustmentMode)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.total_price" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[200px]">
                                        <input type="text" wire:model="items.{{ $index }}.remarks" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.remarks" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        @if (! $isLocked && ! $isStructureLocked)
                                            <button type="button" wire:click="removeItem({{ $index }})"
                                                class="inline-flex items-center rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50">
                                                Remove
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
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
                <a href="{{ route('admin.inventory.stock-receives.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </a>

                @if (! $isLocked)
                    @if ($isPostedAdjustmentMode)
                        @can('inventory.stock.receive.update')
                            <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                                Update Receive
                            </button>
                        @endcan
                    @else
                        @can($editMode ? 'inventory.stock.receive.update' : 'inventory.stock.receive.create')
                            <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                                Save Draft
                            </button>
                        @endcan

                        @can('inventory.stock.receive.post')
                            <button type="button" wire:click="postNow" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                                Save & Post
                            </button>
                        @endcan
                    @endif
                @endif
            </div>
        </form>
    </div>
</div>
