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
                    <input id="order_date" type="date" wire:model="order_date" @disabled($isLocked)
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
                                    <td class="px-4 py-3 min-w-[220px]">
                                        @php
                                        $item = $items[$index] ?? null;
                                        if ($item) {
                                            $linkedRequestIds = $item['stock_request_ids'] ?? [];
                                        } else {
                                            $linkedRequestIds = [];
                                        }

                                        $linkedRequests = collect();
                                        if ($linkedRequestIds && is_array($linkedRequestIds) && count($linkedRequestIds)) {
                                            $linkedRequests = \App\Models\StockRequest::whereIn('id', $linkedRequestIds)->with('requesterStore')->get();
                                        }
                                            // $linkedRequests = $this->purchaseOrderRecord
                                            //     ? $this->purchaseOrderRecord->stockRequests()->wherePivot('product_id', $item['product_id'])->with('requesterStore')->get()
                                            //     : collect();
                                        @endphp
                                        @if ($linkedRequests->isNotEmpty())
                                            <ul class="list-disc pl-5 text-sm text-gray-700">
                                                @foreach ($linkedRequests as $request)
                                                    <li>
                                                        <a href="{{ route('admin.inventory.stock-requests.view', $request) }}" class="text-indigo-600 hover:underline">
                                                            {{ $request->request_no }} ({{ $request->status?->label() }})
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <button class="mt-2 inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50" type="button" wire:click="openLinkedRequestDetails({{ $index }})">
                                                View Details
                                            </button>
                                        @else
                                            <p class="text-sm text-gray-500">No linked requests</p>
                                        @endif
                                        <button class="mt-2 inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50" type="button" wire:click="openLinkModal({{ $index }})">
                                            Add/Update
                                        </button>
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
    @if ($selectedItemIndex !== null)
        @php
            $selectedItem = $items[$selectedItemIndex] ?? null;
            $selectedProduct = $selectedItem ? \App\Models\Product::find($selectedItem['product_id']) : null;
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click="closeLinkModal">
            <div class="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" @click.stop>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Link Stock Request</h3>
                    <button type="button" wire:click="closeLinkModal"
                        class="text-gray-400 transition hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($selectedProduct)
                    <div class="mt-4">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Product:</span> {{ $selectedProduct->name }}
                            @if ($selectedProduct->sku)
                                ({{ $selectedProduct->sku }})
                            @endif
                        </p>
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Requested Quantity:</span> {{ number_format((float) $selectedItem['quantity'], 3) }}
                        </p>
                    </div>
                @endif

                <div class="mt-4">
                    <label for="stock_request_select" class="text-sm font-medium text-gray-700">Select Stock Request(s)</label>
                    <select id="stock_request_select" wire:model="selectedStockRequestIds" multiple
                        class="mt-1 h-44 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        @foreach ($availableStockRequests as $request)
                            @php
                                $matchingItems = $request->items->filter(fn($item) => $item->product_id == ($selectedItem['product_id'] ?? null));
                                $totalRequested = $matchingItems->sum('approved_quantity') ?: $matchingItems->sum('quantity');
                            @endphp
                            <option value="{{ $request->id }}">
                                {{ $request->request_no }} - {{ $request->requesterStore?->name }} ({{ number_format($totalRequested, 3) }} {{ $selectedProduct?->unit }})
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
        </div>
    @endif

    <!-- Quantity Details Modal -->
    @if ($quantityDetails)
        @php
            $details = $quantityDetails;
            $itemIndex = $details['itemIndex'] ?? null;
            $stockRequestIds = $details['stockRequestIds'] ?? [];
            $item = $itemIndex !== null ? ($items[$itemIndex] ?? null) : null;
            $product = $item ? \App\Models\Product::find($item['product_id']) : null;
            $stockRequests = collect();

            if ($product && is_array($stockRequestIds) && count($stockRequestIds)) {
                $stockRequests = \App\Models\StockRequest::with('items.product')
                    ->whereIn('id', $stockRequestIds)
                    ->get();
            }
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click="closeQuantityModal">
            <div class="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" @click.stop>
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Quantity Details</h3>
                    <button type="button" wire:click="closeQuantityModal"
                        class="text-gray-400 transition hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                                    $requestedQty = $requestItem ? ($requestItem->approved_quantity ?: $requestItem->quantity) : 0;
                                    $fulfilledQty = $requestItem ? $requestItem->fulfilled_quantity : 0;
                                    $remainingQty = $requestedQty - $fulfilledQty;
                                    $currentStock = 0;
                                    $toPurchase = max(0, $remainingQty - $currentStock);
                                @endphp

                                <div class="rounded-2xl border border-gray-200 bg-slate-50 p-4">
                                    <p class="text-xs font-medium text-gray-500">Stock Request</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-800">{{ $stockRequest->request_no }} - {{ $stockRequest->requesterStore?->name }}</p>

                                    <div class="mt-4 grid grid-cols-2 gap-3">
                                        <div class="rounded-lg bg-blue-50 px-3 py-2">
                                            <p class="text-xs text-blue-700">Total Requested</p>
                                            <p class="mt-1 text-sm font-semibold text-blue-700">{{ number_format($requestedQty, 3) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-emerald-50 px-3 py-2">
                                            <p class="text-xs text-emerald-700">Already Fulfilled</p>
                                            <p class="mt-1 text-sm font-semibold text-emerald-700">{{ number_format($fulfilledQty, 3) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-amber-50 px-3 py-2">
                                            <p class="text-xs text-amber-700">Remaining</p>
                                            <p class="mt-1 text-sm font-semibold text-amber-700">{{ number_format($remainingQty, 3) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-gray-50 px-3 py-2">
                                            <p class="text-xs text-gray-700">Current Stock</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-700">{{ number_format($currentStock, 3) }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4 rounded-lg bg-indigo-50 px-3 py-2">
                                        <p class="text-xs text-indigo-700">Quantity to Purchase</p>
                                        <p class="mt-1 text-lg font-semibold text-indigo-700">{{ number_format($toPurchase, 3) }}</p>
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
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" wire:click="closeLinkedRequestDetails">
            <div class="w-full max-w-3xl rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" @click.stop>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Linked Request Details</h3>
                        <p class="text-sm text-gray-500">Product: {{ $linkedRequestDetails['product_name'] ?? 'N/A' }} {{ $linkedRequestDetails['product_unit'] ?? '' }}</p>
                    </div>
                    <button type="button" wire:click="closeLinkedRequestDetails"
                        class="text-gray-400 transition hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
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
                        <div class="rounded-lg bg-slate-50 px-4 py-3">
                            <p class="text-xs font-medium text-slate-700">Office Stock</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($linkedRequestDetails['office_stock'] ?? 0, 3) }}</p>
                        </div>
                    </div>

                    <div class="rounded-lg bg-indigo-50 px-4 py-3">
                        <p class="text-xs font-medium text-indigo-700">Need to Purchase</p>
                        <p class="mt-1 text-2xl font-semibold text-indigo-900">{{ number_format($linkedRequestDetails['need_to_purchase'] ?? 0, 3) }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <h4 class="text-sm font-semibold text-gray-800">Linked Requests</h4>
                        <div class="mt-3 space-y-3">
                            @foreach ($linkedRequestDetails['requests'] ?? [] as $request)
                                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-700">
                                        <span class="font-medium text-gray-900">{{ $request['request_no'] }}</span>
                                        <span>{{ $request['requester_name'] ?? 'Unknown' }}</span>
                                    </div>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-lg bg-blue-50 px-3 py-2">
                                            <p class="text-xs text-blue-700">Requested</p>
                                            <p class="mt-1 text-sm font-semibold text-blue-900">{{ number_format($request['requested_quantity'], 3) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-emerald-50 px-3 py-2">
                                            <p class="text-xs text-emerald-700">Fulfilled</p>
                                            <p class="mt-1 text-sm font-semibold text-emerald-900">{{ number_format($request['fulfilled_quantity'], 3) }}</p>
                                        </div>
                                        <div class="rounded-lg bg-amber-50 px-3 py-2">
                                            <p class="text-xs text-amber-700">Remaining</p>
                                            <p class="mt-1 text-sm font-semibold text-amber-900">{{ number_format($request['remaining_quantity'], 3) }}</p>
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
