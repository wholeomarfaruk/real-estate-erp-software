<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Edit Purchase Order' : 'Create Purchase Order' }}', slug: 'purchase-orders' }">
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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.purchase-orders.index') }}">
                        Purchase Orders
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
                This purchase order is {{ $status }} and cannot be edited.
            </div>
        @endif

        <form wire:submit.prevent="saveDraft">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="po_no" class="text-sm font-medium text-gray-700">PO No *</label>
                    <input id="po_no" type="text" wire:model="po_no" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="po_no" class="mt-1" />
                </div>

                <div>
                    <label for="order_date" class="text-sm font-medium text-gray-700">Order Date *</label>
                    <input id="order_date" type="date" wire:model="order_date" class="flatpickr-only-date" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="order_date" class="mt-1" />
                </div>

                <div>
                    <label for="store_id" class="text-sm font-medium text-gray-700">Store *</label>
                    <select id="store_id" wire:model="store_id" @disabled($isLocked)
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
                    <label for="supplier_id" class="text-sm font-medium text-gray-700">Supplier</label>
                    <select id="supplier_id" wire:model="supplier_id" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="supplier_id" class="mt-1" />
                </div>

                <div>
                    <label for="purchase_mode" class="text-sm font-medium text-gray-700">Purchase Mode *</label>
                    <select id="purchase_mode" wire:model="purchase_mode" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                        @foreach ($purchaseModes as $mode)
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="purchase_mode" class="mt-1" />
                </div>

                <div>
                    <label for="fund_request_amount" class="text-sm font-medium text-gray-700">Fund Request Amount *</label>
                    <input id="fund_request_amount" type="number" min="0" step="0.01" wire:model="fund_request_amount" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                    <x-input-error for="fund_request_amount" class="mt-1" />
                </div>

                <div class="md:col-span-2 xl:col-span-2">
                    <label for="remarks" class="text-sm font-medium text-gray-700">Remarks</label>
                    <input id="remarks" type="text" wire:model="remarks" @disabled($isLocked)
                        class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500"
                        placeholder="Purpose, urgency, notes">
                    <x-input-error for="remarks" class="mt-1" />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800">PO Items</h3>
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Unit *</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Estimated Unit Price *</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Estimated Total *</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Linked Requests</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($items as $index => $item)
                                <tr>
                                    <td class="px-4 py-3 min-w-[260px]">
                                        <select wire:model.live="items.{{ $index }}.product_id" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                            <option value="">Select product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error for="items.{{ $index }}.product_id" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[140px]">
                                        <input type="number" min="0.001" step="0.001" wire:model.lazy="items.{{ $index }}.quantity" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.quantity" class="mt-1" />
                                    </td>
                                    <td class="px-4 py-3 min-w-[140px]">
                                        <input disabled type="text" wire:model="items.{{ $index }}.unit" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-700">
                                        <x-input-error for="items.{{ $index }}.quantity" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[160px]">
                                        <input type="number" min="0" step="0.01" wire:model.lazy="items.{{ $index }}.estimated_unit_price" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.estimated_unit_price" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[160px]">
                                        <input type="number" min="0" step="0.01" wire:model.lazy="items.{{ $index }}.estimated_total_price" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.estimated_total_price" class="mt-1" />
                                    </td>


                                    <td class="px-4 py-3 min-w-[220px]">
                                        <input type="text" wire:model="items.{{ $index }}.remarks" @disabled($isLocked)
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500">
                                        <x-input-error for="items.{{ $index }}.remarks" class="mt-1" />
                                    </td>
                                    <td class="px-4 py-3 min-w-50">
                                        @php
                                            $item = $items[$index] ?? null;
                                            $linkedRequestIds = $item['stock_request_ids'] ?? [];
                                            $linkedRequests = collect();
                                            if (is_array($linkedRequestIds) && count($linkedRequestIds)) {
                                                $linkedRequests = \App\Models\StockRequest::whereIn('id', $linkedRequestIds)->with('requesterStore')->get();
                                            }
                                        @endphp

                                        <div class="flex flex-col gap-2">
                                            {{-- Badge list --}}
                                            @if ($linkedRequests->isNotEmpty())
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach ($linkedRequests as $req)
                                                        <a href="{{ route('admin.inventory.stock-requests.view', $req) }}"
                                                           target="_blank"
                                                           title="{{ $req->requesterStore?->name }}"
                                                           class="inline-flex items-center gap-1 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 transition">
                                                            <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 0 0-5.656 0l-4 4a4 4 0 1 0 5.656 5.656l1.102-1.101"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.172 13.828a4 4 0 0 0 5.656 0l4-4a4 4 0 0 0-5.656-5.656l-1.1 1.1"/>
                                                            </svg>
                                                            {{ $req->request_no }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-xs text-gray-400 italic">No linked requests</p>
                                            @endif

                                            {{-- Action buttons --}}
                                            <div class="flex items-center gap-1.5">
                                                {{-- Add / Update --}}
                                                <button type="button"
                                                    wire:click="openLinkModal({{ $index }})"
                                                    title="{{ $linkedRequests->isNotEmpty() ? 'Update linked requests' : 'Link a stock request' }}"
                                                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 transition hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 0 0-5.656 0l-4 4a4 4 0 1 0 5.656 5.656l1.102-1.101"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.172 13.828a4 4 0 0 0 5.656 0l4-4a4 4 0 0 0-5.656-5.656l-1.1 1.1"/>
                                                    </svg>
                                                    {{ $linkedRequests->isNotEmpty() ? 'Update' : 'Link' }}
                                                </button>

                                                {{-- View Details (only when linked) --}}
                                                @if ($linkedRequests->isNotEmpty())
                                                    <button type="button"
                                                        wire:click="openLinkedRequestDetails({{ $index }})"
                                                        title="View quantity details"
                                                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 transition hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-700">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                        </svg>
                                                        Details
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        <x-input-error for="" class="mt-1" />
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
                    <p class="text-xs text-gray-500">Estimated Grand Total</p>
                    <p class="text-lg font-semibold text-gray-800">{{ number_format($grandTotal, 2) }}</p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('admin.inventory.purchase-orders.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </a>

                @if (! $isLocked)
                    @can($editMode ? 'inventory.purchase_order.update' : 'inventory.purchase_order.create')
                        <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                            Save Draft
                        </button>
                    @endcan

                    @can('inventory.purchase_order.submit')
                        <button type="button" wire:click="submitNow" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                            Save & Submit
                        </button>
                    @endcan
                @endif
            </div>
        </form>
    </div>

    <!-- Link Stock Request Modal -->
    @php
        $selectedItem    = $selectedItemIndex !== null ? ($items[$selectedItemIndex] ?? null) : null;
        $selectedProduct = $selectedItem ? \App\Models\Product::find($selectedItem['product_id']) : null;
    @endphp
    <x-modal wire:model="showLinkModal" maxWidth="2xl">
        <div class="p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Link Stock Request</h3>
                <button type="button" wire:click="closeLinkModal" class="text-gray-400 transition hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if ($selectedProduct)
                <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3">
                    <p class="text-sm text-gray-600">
                        <span class="font-medium">Product:</span> {{ $selectedProduct->name }}
                        @if ($selectedProduct->sku) ({{ $selectedProduct->sku }}) @endif
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        <span class="font-medium">Quantity:</span> {{ number_format((float) ($selectedItem['quantity'] ?? 0), 3) }}
                    </p>
                </div>
            @endif

            <div class="mt-4">
                <label for="stock_request_select" class="text-sm font-medium text-gray-700">Select Stock Request(s)</label>
                <select id="stock_request_select" wire:model="selectedStockRequestIds" multiple
                    class="mt-1 h-44 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                    @foreach ($availableStockRequests as $request)
                        @php
                            $matchingItems  = $request->items->filter(fn($i) => $i->product_id == ($selectedItem['product_id'] ?? null));
                            $totalRequested = $matchingItems->sum('approved_quantity') ?: $matchingItems->sum('quantity');
                        @endphp
                        <option value="{{ $request->id }}">
                            {{ $request->request_no }} — {{ $request->requesterStore?->name }} ({{ number_format($totalRequested, 3) }} {{ $selectedProduct?->unit }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" wire:click="closeLinkModal"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" wire:click="linkStockRequest"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                    Link Request
                </button>
            </div>
        </div>
    </x-modal>

    <!-- Quantity Details Modal -->
    @php
        $qItemIndex      = $quantityDetails['itemIndex'] ?? null;
        $qStockReqIds    = $quantityDetails['stockRequestIds'] ?? [];
        $qItem           = $qItemIndex !== null ? ($items[$qItemIndex] ?? null) : null;
        $qProduct        = $qItem ? \App\Models\Product::find($qItem['product_id']) : null;
        $qStockRequests  = ($qProduct && count($qStockReqIds))
            ? \App\Models\StockRequest::with(['items', 'requesterStore'])->whereIn('id', $qStockReqIds)->get()
            : collect();

        // Compute totals for the auto-fill preview
        $qTotalRemaining = 0;
        $qOfficeStock    = 0;
        if ($qProduct && $qStockRequests->isNotEmpty()) {
            foreach ($qStockRequests as $sr) {
                $ri = $sr->items->firstWhere('product_id', $qProduct->id);
                if ($ri) {
                    $req = (float) ($ri->approved_quantity ?: $ri->quantity ?: 0);
                    $ful = (float) ($ri->fulfilled_quantity ?: 0);
                    $qTotalRemaining += max(0, $req - $ful);
                }
            }
            $officeStoreIds = \App\Models\Store::query()->office()->pluck('id')->toArray();
            $qOfficeStock   = (float) \App\Models\StockBalance::query()
                ->where('product_id', $qProduct->id)
                ->whereIn('store_id', $officeStoreIds)
                ->sum('quantity');
        }
        $qNeedToPurchase = max(0, $qTotalRemaining - $qOfficeStock);
    @endphp
    <x-modal wire:model="showQuantityModal" maxWidth="2xl">
        <div class="p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Quantity Details</h3>
                <button type="button" wire:click="closeQuantityModal" class="text-gray-400 transition hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if ($qProduct && $qStockRequests->isNotEmpty())
                <div class="mt-4 space-y-4">
                    <div class="rounded-lg bg-gray-50 px-4 py-3">
                        <p class="text-xs font-medium text-gray-500">Product</p>
                        <p class="mt-1 text-sm font-semibold text-gray-800">{{ $qProduct->name }}</p>
                    </div>

                    {{-- Per-request breakdown --}}
                    <div class="grid gap-3">
                        @foreach ($qStockRequests as $stockRequest)
                            @php
                                $requestItem  = $stockRequest->items->firstWhere('product_id', $qProduct->id);
                                $requestedQty = $requestItem ? ($requestItem->approved_quantity ?: $requestItem->quantity) : 0;
                                $fulfilledQty = $requestItem ? $requestItem->fulfilled_quantity : 0;
                                $remainingQty = max(0, $requestedQty - $fulfilledQty);
                            @endphp
                            <div class="rounded-xl border border-gray-200 bg-slate-50 p-4">
                                <p class="text-xs font-medium text-gray-500">Stock Request</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800">
                                    {{ $stockRequest->request_no }} — {{ $stockRequest->requesterStore?->name }}
                                </p>
                                <div class="mt-3 grid grid-cols-3 gap-2">
                                    <div class="rounded-lg bg-blue-50 px-3 py-2">
                                        <p class="text-xs text-blue-700">Requested</p>
                                        <p class="mt-1 text-sm font-semibold text-blue-800">{{ number_format($requestedQty, 3) }}</p>
                                    </div>
                                    <div class="rounded-lg bg-emerald-50 px-3 py-2">
                                        <p class="text-xs text-emerald-700">Fulfilled</p>
                                        <p class="mt-1 text-sm font-semibold text-emerald-800">{{ number_format($fulfilledQty, 3) }}</p>
                                    </div>
                                    <div class="rounded-lg bg-amber-50 px-3 py-2">
                                        <p class="text-xs text-amber-700">Remaining</p>
                                        <p class="mt-1 text-sm font-semibold text-amber-800">{{ number_format($remainingQty, 3) }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Auto-fill summary --}}
                    <div class="rounded-xl border-2 border-indigo-200 bg-indigo-50 p-4">
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div>
                                <p class="text-xs text-gray-500">Total Remaining</p>
                                <p class="mt-1 text-base font-bold text-gray-800">{{ number_format($qTotalRemaining, 3) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Office Stock</p>
                                <p class="mt-1 text-base font-bold text-gray-800">{{ number_format($qOfficeStock, 3) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-indigo-700">Need to Purchase</p>
                                <p class="mt-1 text-xl font-bold text-indigo-800">{{ number_format($qNeedToPurchase, 3) }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-center text-xs text-indigo-600">
                            <svg class="mr-1 inline h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Confirming will auto-fill quantity with
                            <strong>{{ number_format($qNeedToPurchase, 3) }} {{ $qProduct->unit }}</strong>
                            — you can edit it on the form.
                        </p>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" wire:click="closeQuantityModal"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" wire:click="confirmLink"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Confirm &amp; Auto-fill Quantity
                </button>
            </div>
        </div>
    </x-modal>

    <!-- Linked Request Details Modal -->
    <x-modal wire:model="showLinkedDetailsModal" maxWidth="2xl">
        <div class="p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Linked Request Details</h3>
                    @if ($linkedRequestDetails)
                        <p class="text-sm text-gray-500">
                            {{ $linkedRequestDetails['product_name'] ?? '' }}
                            @if ($linkedRequestDetails['product_unit'] ?? null)
                                <span class="text-gray-400">({{ $linkedRequestDetails['product_unit'] }})</span>
                            @endif
                        </p>
                    @endif
                </div>
                <button type="button" wire:click="closeLinkedRequestDetails" class="text-gray-400 transition hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if ($linkedRequestDetails)
                <div class="mt-4 space-y-4">
                    {{-- Summary stats --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg bg-blue-50 px-4 py-3">
                            <p class="text-xs font-medium text-blue-700">Total Requested</p>
                            <p class="mt-1 text-lg font-semibold text-blue-900">{{ number_format($linkedRequestDetails['total_requested'] ?? 0, 3) }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-medium text-emerald-700">Already Fulfilled</p>
                            <p class="mt-1 text-lg font-semibold text-emerald-900">{{ number_format($linkedRequestDetails['total_fulfilled'] ?? 0, 3) }}</p>
                        </div>
                        <div class="rounded-lg bg-amber-50 px-4 py-3">
                            <p class="text-xs font-medium text-amber-700">Remaining Qty</p>
                            <p class="mt-1 text-lg font-semibold text-amber-900">{{ number_format($linkedRequestDetails['total_remaining'] ?? 0, 3) }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-100 px-4 py-3">
                            <p class="text-xs font-medium text-slate-600">Office Stock</p>
                            <p class="mt-1 text-lg font-semibold text-slate-800">{{ number_format($linkedRequestDetails['office_stock'] ?? 0, 3) }}</p>
                        </div>
                    </div>

                    <div class="rounded-lg bg-indigo-50 px-4 py-3">
                        <p class="text-xs font-medium text-indigo-700">Need to Purchase</p>
                        <p class="mt-1 text-2xl font-bold text-indigo-900">{{ number_format($linkedRequestDetails['need_to_purchase'] ?? 0, 3) }}</p>
                    </div>

                    {{-- Per-request breakdown --}}
                    @if (! empty($linkedRequestDetails['requests']))
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <h4 class="text-sm font-semibold text-gray-800">Breakdown by Request</h4>
                            <div class="mt-3 space-y-3">
                                @foreach ($linkedRequestDetails['requests'] as $request)
                                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-sm font-semibold text-gray-900">{{ $request['request_no'] }}</span>
                                            <span class="text-xs text-gray-500">{{ $request['requester_name'] ?? 'Unknown' }}</span>
                                        </div>
                                        <div class="mt-3 grid grid-cols-3 gap-2">
                                            <div class="rounded-lg bg-blue-50 px-3 py-2">
                                                <p class="text-xs text-blue-700">Requested</p>
                                                <p class="mt-0.5 text-sm font-semibold text-blue-900">{{ number_format($request['requested_quantity'], 3) }}</p>
                                            </div>
                                            <div class="rounded-lg bg-emerald-50 px-3 py-2">
                                                <p class="text-xs text-emerald-700">Fulfilled</p>
                                                <p class="mt-0.5 text-sm font-semibold text-emerald-900">{{ number_format($request['fulfilled_quantity'], 3) }}</p>
                                            </div>
                                            <div class="rounded-lg bg-amber-50 px-3 py-2">
                                                <p class="text-xs text-amber-700">Remaining</p>
                                                <p class="mt-0.5 text-sm font-semibold text-amber-900">{{ number_format($request['remaining_quantity'], 3) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <button type="button" wire:click="closeLinkedRequestDetails"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Close
                </button>
            </div>
        </div>
    </x-modal>
</div>
