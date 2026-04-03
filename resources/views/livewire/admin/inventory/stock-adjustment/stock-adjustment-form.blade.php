<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Edit Stock Adjustment' : 'Create Stock Adjustment' }}', slug: 'stock-adjustments' }">
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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.stock-adjustments.index') }}">
                        Stock Adjustment
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
                This stock adjustment is {{ $status }} and cannot be edited.
            </div>
        @endif

        <form wire:submit.prevent="saveDraft">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="adjustment_no" class="text-sm font-medium text-gray-700">Adjustment No *</label>
                    <input id="adjustment_no" type="text" wire:model="adjustment_no" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="adjustment_no" class="mt-1" />
                </div>

                <div>
                    <label for="adjustment_date" class="text-sm font-medium text-gray-700">Adjustment Date *</label>
                    <input id="adjustment_date" type="date" wire:model="adjustment_date" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="adjustment_date" class="mt-1" />
                </div>

                <div>
                    <label for="store_id" class="text-sm font-medium text-gray-700">Store *</label>
                    <select id="store_id" wire:model.live="store_id" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select store</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">
                                {{ $store->name }} ({{ strtoupper($store->type?->value ?? (string) $store->type) }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error for="store_id" class="mt-1" />
                </div>

                <div>
                    <label for="adjustment_type" class="text-sm font-medium text-gray-700">Adjustment Type *</label>
                    <select id="adjustment_type" wire:model.live="adjustment_type" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="in">Adjustment In</option>
                        <option value="out">Adjustment Out</option>
                    </select>
                    <x-input-error for="adjustment_type" class="mt-1" />
                </div>

                <div>
                    <label for="reason" class="text-sm font-medium text-gray-700">Reason</label>
                    <input id="reason" type="text" wire:model="reason" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500"
                        placeholder="Damaged / found / audit mismatch">
                    <x-input-error for="reason" class="mt-1" />
                </div>

                <div class="md:col-span-2 xl:col-span-3">
                    <label for="remarks" class="text-sm font-medium text-gray-700">Remarks</label>
                    <input id="remarks" type="text" wire:model="remarks" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="remarks" class="mt-1" />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Adjustment Items</h3>
                    <p class="text-xs text-gray-500">
                        @if ($isAdjustmentOut)
                            Adjustment Out uses average rate from stock balance at posting time.
                        @else
                            Adjustment In uses entered unit price and updates moving weighted average.
                        @endif
                    </p>
                </div>
                @if (! $isLocked)
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Qty *</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Available</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Unit Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($items as $index => $item)
                                @php
                                    $availableQty = $this->availableQuantityFor($index);
                                    $requiredQty = (float) ($item['quantity'] ?? 0);
                                    $isInsufficient = $isAdjustmentOut && !empty($item['product_id']) && $availableQty < $requiredQty;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 min-w-[260px]">
                                        <select wire:model="items.{{ $index }}.product_id" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                            <option value="">Select product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error for="items.{{ $index }}.product_id" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[140px]">
                                        <input type="number" min="0.001" step="0.001" wire:model.live="items.{{ $index }}.quantity" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.quantity" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[150px]">
                                        <div class="{{ $isInsufficient ? 'text-red-600' : 'text-gray-700' }} text-sm">
                                            {{ number_format($availableQty, 3) }}
                                        </div>
                                        @if ($isInsufficient)
                                            <p class="text-xs text-red-600">Insufficient stock</p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 min-w-[140px]">
                                        <input type="number" min="0" step="0.01" wire:model.live="items.{{ $index }}.unit_price"
                                            @disabled($isLocked || $isAdjustmentOut)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.unit_price" class="mt-1" />
                                        @if ($isAdjustmentOut)
                                            <p class="mt-1 text-xs text-gray-500">Auto on post</p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 min-w-[140px]">
                                        <input type="number" min="0" step="0.01" wire:model.live="items.{{ $index }}.total_price"
                                            @disabled($isLocked || $isAdjustmentOut)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.total_price" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[220px]">
                                        <input type="text" wire:model="items.{{ $index }}.remarks" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.remarks" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        @if (! $isLocked)
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
                <a href="{{ route('admin.inventory.stock-adjustments.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </a>

                @if (! $isLocked)
                    @can($editMode ? 'inventory.stock.adjustment.update' : 'inventory.stock.adjustment.create')
                        <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                            Save Draft
                        </button>
                    @endcan

                    @can('inventory.stock.adjustment.post')
                        <button type="button" wire:click="postNow" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                            Save & Post
                        </button>
                    @endcan
                @endif
            </div>
        </form>
    </div>
</div>
