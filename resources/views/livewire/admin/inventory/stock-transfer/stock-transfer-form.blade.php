<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Edit Stock Transfer' : 'Create Stock Transfer' }}', slug: 'stock-transfers' }">
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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.stock-transfers.index') }}">
                        Stock Transfer
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
                This transfer is {{ $status }} and cannot be edited.
            </div>
        @endif

        <form wire:submit.prevent="saveDraft">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="transfer_no" class="text-sm font-medium text-gray-700">Transfer No *</label>
                    <input id="transfer_no" type="text" wire:model="transfer_no" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="transfer_no" class="mt-1" />
                </div>

                <div>
                    <label for="transfer_date" class="text-sm font-medium text-gray-700">Transfer Date *</label>
                    <input id="transfer_date" type="date" wire:model="transfer_date" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="transfer_date" class="mt-1" />
                </div>

                <div>
                    <label for="sender_store_id" class="text-sm font-medium text-gray-700">Sender Store *</label>
                    <select id="sender_store_id" wire:model.live="sender_store_id" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select sender store</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }} ({{ $store->code }})</option>
                        @endforeach
                    </select>
                    <x-input-error for="sender_store_id" class="mt-1" />
                </div>

                <div>
                    <label for="receiver_store_id" class="text-sm font-medium text-gray-700">Receiver Store *</label>
                    <select id="receiver_store_id" wire:model="receiver_store_id" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select receiver store</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }} ({{ $store->code }})</option>
                        @endforeach
                    </select>
                    <x-input-error for="receiver_store_id" class="mt-1" />
                </div>

                <div class="md:col-span-2 xl:col-span-4">
                    <label for="remarks" class="text-sm font-medium text-gray-700">Remarks</label>
                    <input id="remarks" type="text" wire:model="remarks" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="remarks" class="mt-1" />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800">Transfer Items</h3>
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Quantity *</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Available</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Transfer Value</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($items as $index => $item)
                                @php
                                    $availableQty = $this->availableQuantityFor($index);
                                    $requiredQty = (float) ($item['quantity'] ?? 0);
                                    $isInsufficient = !empty($item['product_id']) && $availableQty < $requiredQty;
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

                                    <td class="px-4 py-3 min-w-[160px]">
                                        <div class="{{ $isInsufficient ? 'text-red-600' : 'text-gray-700' }} text-sm">
                                            {{ number_format($availableQty, 3) }}
                                        </div>
                                        @if ($isInsufficient)
                                            <p class="text-xs text-red-600">Insufficient stock</p>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 min-w-[160px]">
                                        @if ((float) ($item['unit_price'] ?? 0) > 0)
                                            <p class="text-sm text-gray-700">Rate: {{ number_format((float) $item['unit_price'], 2) }}</p>
                                            <p class="text-xs text-gray-500">Total: {{ number_format((float) $item['total_price'], 2) }}</p>
                                        @else
                                            <p class="text-xs text-gray-500">Auto on completion</p>
                                        @endif
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

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('admin.inventory.stock-transfers.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </a>

                @if (! $isLocked)
                    @can($editMode ? 'inventory.stock.transfer.update' : 'inventory.stock.transfer.create')
                        <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                            Save Draft
                        </button>
                    @endcan

                    @can('inventory.stock.transfer.request')
                        <button type="button" wire:click="requestNow" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                            Save & Request
                        </button>
                    @endcan
                @endif
            </div>
        </form>
    </div>
</div>
