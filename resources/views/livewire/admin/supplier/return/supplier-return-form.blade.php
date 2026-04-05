<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Edit Supplier Return' : 'Create Supplier Return' }}', slug: 'supplier-returns' }">
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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.supplier.returns.index') }}">
                        Returns
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
        <x-form-section submit="save">
            <x-slot name="title">{{ $editMode ? 'Update Supplier Return' : 'Create Supplier Return' }}</x-slot>
            <x-slot name="description">Create supplier return/debit note drafts with optional reference linking.</x-slot>

            <x-slot name="form">
                <div class="col-span-6 sm:col-span-3">
                    <x-label for="supplier_id" value="Supplier *" />
                    <select wire:model.live="supplier_id" id="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->code ?: 'N/A' }})</option>
                        @endforeach
                    </select>
                    <x-input-error for="supplier_id" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="return_no" value="Return No *" />
                    <x-input wire:model="return_no" id="return_no" type="text" class="mt-1 block w-full" />
                    <x-input-error for="return_no" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="return_date" value="Return Date *" />
                    <x-input wire:model="return_date" id="return_date" type="date" class="mt-1 block w-full" />
                    <x-input-error for="return_date" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="reference_type" value="Reference Type *" />
                    <select wire:model.live="reference_type" id="reference_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($referenceTypes as $referenceType)
                            <option value="{{ $referenceType->value }}">{{ $referenceType->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="reference_type" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="status" value="Status" />
                    <select wire:model="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" disabled>
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="status" class="mt-2" />
                </div>

                @if ($reference_type === \App\Enums\Supplier\SupplierReturnReferenceType::SUPPLIER_BILL->value)
                    <div class="col-span-6">
                        <x-label for="supplier_bill_id" value="Supplier Bill *" />
                        <select wire:model.live="supplier_bill_id" id="supplier_bill_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Supplier Bill</option>
                            @foreach ($bills as $bill)
                                <option value="{{ $bill->id }}">
                                    {{ $bill->bill_no }} | {{ optional($bill->bill_date)->format('d M, Y') }} | {{ $bill->supplier?->name ?: 'N/A' }} | Amount: {{ number_format((float) $bill->total_amount, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error for="supplier_bill_id" class="mt-2" />
                    </div>
                @endif

                @if ($reference_type === \App\Enums\Supplier\SupplierReturnReferenceType::STOCK_RECEIVE->value)
                    <div class="col-span-6">
                        <x-label for="stock_receive_id" value="Stock Receive *" />
                        <select wire:model.live="stock_receive_id" id="stock_receive_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Stock Receive</option>
                            @foreach ($stockReceives as $stockReceive)
                                <option value="{{ $stockReceive->id }}">
                                    {{ $stockReceive->receive_no }} | {{ optional($stockReceive->receive_date)->format('d M, Y') }} | {{ $stockReceive->supplier?->name ?: 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error for="stock_receive_id" class="mt-2" />
                    </div>
                @endif

                @if ($reference_type === \App\Enums\Supplier\SupplierReturnReferenceType::PURCHASE_ORDER->value)
                    <div class="col-span-6">
                        <x-label for="purchase_order_id" value="Purchase Order *" />
                        <select wire:model.live="purchase_order_id" id="purchase_order_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Purchase Order</option>
                            @foreach ($purchaseOrders as $purchaseOrder)
                                <option value="{{ $purchaseOrder->id }}">
                                    {{ $purchaseOrder->po_no }} | {{ optional($purchaseOrder->order_date)->format('d M, Y') }} | {{ $purchaseOrder->supplier?->name ?: 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error for="purchase_order_id" class="mt-2" />
                    </div>
                @endif

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="reason" value="Reason" />
                    <x-input wire:model="reason" id="reason" type="text" class="mt-1 block w-full" />
                    <x-input-error for="reason" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="notes" value="Notes" />
                    <textarea wire:model="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <x-input-error for="notes" class="mt-2" />
                </div>

                <div class="col-span-6">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700">Return Items</h3>
                                <p class="mt-1 text-xs text-gray-500">For linked references, source items load automatically and qty can be adjusted safely.</p>
                            </div>
                            @if ($manualMode)
                                <button
                                    type="button"
                                    wire:click="addItem"
                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Add Item
                                </button>
                            @endif
                        </div>

                        <div class="max-w-full overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs text-gray-500">Product</th>
                                        <th class="px-3 py-2 text-left text-xs text-gray-500">Description</th>
                                        <th class="px-3 py-2 text-right text-xs text-gray-500">Qty</th>
                                        <th class="px-3 py-2 text-left text-xs text-gray-500">Unit</th>
                                        <th class="px-3 py-2 text-right text-xs text-gray-500">Rate</th>
                                        <th class="px-3 py-2 text-right text-xs text-gray-500">Line Total</th>
                                        <th class="px-3 py-2 text-right text-xs text-gray-500">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($items as $index => $item)
                                        <tr>
                                            <td class="px-3 py-2 align-top">
                                                <select wire:model.live="items.{{ $index }}.product_id" class="w-52 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Select Product</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}">
                                                            {{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <x-input-error for="items.{{ $index }}.product_id" class="mt-1" />
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <input
                                                    type="text"
                                                    wire:model.live="items.{{ $index }}.description"
                                                    class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    placeholder="Item description"
                                                >
                                                <x-input-error for="items.{{ $index }}.description" class="mt-1" />
                                            </td>
                                            <td class="px-3 py-2 align-top text-right">
                                                <input
                                                    type="number"
                                                    step="0.001"
                                                    min="0.001"
                                                    wire:model.live="items.{{ $index }}.qty"
                                                    class="w-28 rounded-md border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                <x-input-error for="items.{{ $index }}.qty" class="mt-1" />
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                <select wire:model.live="items.{{ $index }}.unit_id" class="w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Select Unit</option>
                                                    @foreach ($units as $unit)
                                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                                    @endforeach
                                                </select>
                                                <x-input-error for="items.{{ $index }}.unit_id" class="mt-1" />
                                            </td>
                                            <td class="px-3 py-2 align-top text-right">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    wire:model.live="items.{{ $index }}.rate"
                                                    class="w-28 rounded-md border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                <x-input-error for="items.{{ $index }}.rate" class="mt-1" />
                                            </td>
                                            <td class="px-3 py-2 align-top text-right text-sm font-medium text-gray-700">
                                                {{ number_format((float) ($item['line_total'] ?? 0), 2) }}
                                            </td>
                                            <td class="px-3 py-2 align-top text-right">
                                                @if ($manualMode)
                                                    <button
                                                        type="button"
                                                        wire:click="removeItem({{ $index }})"
                                                        class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-100"
                                                    >
                                                        Remove
                                                    </button>
                                                @else
                                                    <span class="text-xs text-gray-400">Linked</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <x-input-error for="items" class="px-4 py-2" />
                    </div>
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="subtotal" value="Subtotal" />
                    <x-input wire:model="subtotal" id="subtotal" type="number" step="0.01" class="mt-1 block w-full bg-gray-50" readonly />
                    <x-input-error for="subtotal" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="total_amount" value="Total Amount" />
                    <x-input wire:model="total_amount" id="total_amount" type="number" step="0.01" class="mt-1 block w-full bg-gray-50" readonly />
                    <x-input-error for="total_amount" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="actions">
                @can($editMode ? 'supplier.return.edit' : 'supplier.return.create')
                    <x-button type="submit">{{ $editMode ? 'Update Return Draft' : 'Create Return Draft' }}</x-button>
                @endcan
                <a href="{{ route('admin.supplier.returns.index') }}" class="ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Cancel
                </a>
            </x-slot>
        </x-form-section>
    </div>
</div>
