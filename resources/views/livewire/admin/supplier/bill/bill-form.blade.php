<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Edit Supplier Bill' : 'Create Supplier Bill' }}', slug: 'supplier-bills' }">
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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.supplier.bills.index') }}">
                        Bills
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
            <x-slot name="title">{{ $editMode ? 'Update Supplier Bill' : 'Create Supplier Bill' }}</x-slot>
            <x-slot name="description">Create manual bills or safely link bill amounts from purchase order / stock receive.</x-slot>

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

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="bill_no" value="Bill No *" />
                    <x-input wire:model="bill_no" id="bill_no" type="text" class="mt-1 block w-full" />
                    <x-input-error for="bill_no" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="bill_date" value="Bill Date *" />
                    <x-input wire:model="bill_date" id="bill_date" type="date" class="mt-1 block w-full" />
                    <x-input-error for="bill_date" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="due_date" value="Due Date" />
                    <x-input wire:model="due_date" id="due_date" type="date" class="mt-1 block w-full" />
                    <x-input-error for="due_date" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="reference_type" value="Source Mode *" />
                    <select wire:model.live="reference_type" id="reference_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($referenceTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="reference_type" class="mt-2" />
                </div>

                <div class="col-span-6">
                    @if ($reference_type === \App\Enums\Supplier\SupplierBillReferenceType::LINKED_PURCHASE_ORDER->value)
                        <x-label for="purchase_order_id" value="Purchase Order *" />
                        <select wire:model.live="purchase_order_id" id="purchase_order_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Purchase Order</option>
                            @foreach ($purchaseOrders as $purchaseOrder)
                                <option value="{{ $purchaseOrder->id }}">
                                    {{ $purchaseOrder->po_no }} | {{ $purchaseOrder->supplier?->name ?? 'No supplier' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error for="purchase_order_id" class="mt-2" />
                    @elseif ($reference_type === \App\Enums\Supplier\SupplierBillReferenceType::LINKED_STOCK_RECEIVE->value)
                        <x-label for="stock_receive_id" value="Stock Receive *" />
                        <select wire:model.live="stock_receive_id" id="stock_receive_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Stock Receive</option>
                            @foreach ($stockReceives as $stockReceive)
                                <option value="{{ $stockReceive->id }}">
                                    {{ $stockReceive->receive_no }} | {{ $stockReceive->supplier?->name ?? 'No supplier' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error for="stock_receive_id" class="mt-2" />
                    @else
                        <p class="text-xs text-gray-500">Manual mode enabled. Add custom bill items below.</p>
                    @endif
                </div>

                <div class="col-span-6">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                            <h3 class="text-sm font-semibold text-gray-700">Bill Items</h3>
                            @if ($manualMode)
                                <button type="button" wire:click="addItem" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
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
                                    @forelse ($items as $index => $item)
                                        <tr>
                                            <td class="px-3 py-2">
                                                <select wire:model.live="items.{{ $index }}.product_id" class="w-44 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @disabled(! $manualMode)>
                                                    <option value="">Select</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" wire:model.live="items.{{ $index }}.description" class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @readonly(! $manualMode)>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" step="0.001" min="0" wire:model.live="items.{{ $index }}.qty" class="w-28 rounded-md border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @readonly(! $manualMode)>
                                            </td>
                                            <td class="px-3 py-2">
                                                <select wire:model.live="items.{{ $index }}.unit_id" class="w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @disabled(! $manualMode)>
                                                    <option value="">N/A</option>
                                                    @foreach ($units as $unit)
                                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" step="0.01" min="0" wire:model.live="items.{{ $index }}.rate" class="w-28 rounded-md border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @readonly(! $manualMode)>
                                            </td>
                                            <td class="px-3 py-2 text-right text-sm text-gray-700">
                                                {{ number_format((float) ($item['line_total'] ?? 0), 2) }}
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                @if ($manualMode)
                                                    <button type="button" wire:click="removeItem({{ $index }})" class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                                        Remove
                                                    </button>
                                                @else
                                                    <span class="text-xs text-gray-400">Linked</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">No items added yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <x-input-error for="items" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="subtotal" value="Subtotal" />
                    <x-input wire:model="subtotal" id="subtotal" type="number" step="0.01" class="mt-1 block w-full bg-gray-50" readonly />
                    <x-input-error for="subtotal" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="discount_amount" value="Discount" />
                    <x-input wire:model.live="discount_amount" id="discount_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                    <x-input-error for="discount_amount" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="tax_amount" value="Tax" />
                    <x-input wire:model.live="tax_amount" id="tax_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                    <x-input-error for="tax_amount" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="other_charge" value="Other Charge" />
                    <x-input wire:model.live="other_charge" id="other_charge" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                    <x-input-error for="other_charge" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="total_amount" value="Total Amount" />
                    <x-input wire:model="total_amount" id="total_amount" type="number" step="0.01" class="mt-1 block w-full bg-gray-50" readonly />
                    <x-input-error for="total_amount" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="paid_amount" value="Paid Amount" />
                    <x-input wire:model.live="paid_amount" id="paid_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                    <x-input-error for="paid_amount" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="due_amount" value="Due Amount" />
                    <x-input wire:model="due_amount" id="due_amount" type="number" step="0.01" class="mt-1 block w-full bg-gray-50" readonly />
                    <x-input-error for="due_amount" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="status" value="Status" />
                    <select wire:model="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="status" class="mt-2" />
                </div>

                <div class="col-span-6">
                    <x-label for="notes" value="Notes" />
                    <textarea wire:model="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <x-input-error for="notes" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="actions">
                @can($editMode ? 'supplier.bill.edit' : 'supplier.bill.create')
                    <x-button type="submit">{{ $editMode ? 'Update Bill' : 'Create Bill' }}</x-button>
                @endcan
                <a href="{{ route('admin.supplier.bills.index') }}" class="ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Cancel
                </a>
            </x-slot>
        </x-form-section>
    </div>
</div>
