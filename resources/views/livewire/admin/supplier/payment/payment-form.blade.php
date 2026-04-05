<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Edit Supplier Payment' : 'Create Supplier Payment' }}', slug: 'supplier-payments' }">
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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.supplier.payments.index') }}">
                        Payments
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
            <x-slot name="title">{{ $editMode ? 'Update Supplier Payment' : 'Create Supplier Payment' }}</x-slot>
            <x-slot name="description">Record advance or bill-wise supplier payments with safe allocation controls.</x-slot>

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
                    <x-label for="payment_no" value="Payment No *" />
                    <x-input wire:model="payment_no" id="payment_no" type="text" class="mt-1 block w-full" />
                    <x-input-error for="payment_no" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="payment_date" value="Payment Date *" />
                    <x-input wire:model="payment_date" id="payment_date" type="date" class="mt-1 block w-full" />
                    <x-input-error for="payment_date" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="payment_method" value="Payment Method *" />
                    <select wire:model="payment_method" id="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="payment_method" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="status" value="Status *" />
                    <select wire:model="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="status" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="total_amount" value="Total Amount *" />
                    <x-input wire:model.live="total_amount" id="total_amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" />
                    <x-input-error for="total_amount" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="allocated_amount" value="Allocated Amount" />
                    <x-input wire:model="allocated_amount" id="allocated_amount" type="number" step="0.01" class="mt-1 block w-full bg-gray-50" readonly />
                    <x-input-error for="allocated_amount" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="unallocated_amount" value="Unallocated Amount" />
                    <x-input wire:model="unallocated_amount" id="unallocated_amount" type="number" step="0.01" class="mt-1 block w-full bg-gray-50" readonly />
                    <x-input-error for="unallocated_amount" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="account_name" value="Account Name" />
                    <x-input wire:model="account_name" id="account_name" type="text" class="mt-1 block w-full" />
                    <x-input-error for="account_name" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="account_reference" value="Account Reference" />
                    <x-input wire:model="account_reference" id="account_reference" type="text" class="mt-1 block w-full" />
                    <x-input-error for="account_reference" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="reference_no" value="Reference No" />
                    <x-input wire:model="reference_no" id="reference_no" type="text" class="mt-1 block w-full" />
                    <x-input-error for="reference_no" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="transaction_no" value="Transaction No" />
                    <x-input wire:model="transaction_no" id="transaction_no" type="text" class="mt-1 block w-full" />
                    <x-input-error for="transaction_no" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="cheque_no" value="Cheque No" />
                    <x-input wire:model="cheque_no" id="cheque_no" type="text" class="mt-1 block w-full" />
                    <x-input-error for="cheque_no" class="mt-2" />
                </div>

                <div class="col-span-6">
                    <x-label for="remarks" value="Remarks" />
                    <textarea wire:model="remarks" id="remarks" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <x-input-error for="remarks" class="mt-2" />
                </div>

                <div class="col-span-6">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <h3 class="text-sm font-semibold text-gray-700">Bill Allocation (Optional)</h3>
                            <p class="mt-1 text-xs text-gray-500">You can keep this empty for advance payment. Allocations apply only to pending bills of selected supplier.</p>
                        </div>

                        @if (! $supplier_id)
                            <div class="px-4 py-6 text-sm text-gray-500">Select a supplier to load pending bills for allocation.</div>
                        @elseif (! $canAllocateBills)
                            <div class="px-4 py-6 text-sm text-gray-500">You can create payment entries, but bill allocation requires `supplier.payment.allocate` permission.</div>
                        @else
                            <div class="max-w-full overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs text-gray-500">Bill No</th>
                                            <th class="px-3 py-2 text-left text-xs text-gray-500">Bill Date</th>
                                            <th class="px-3 py-2 text-left text-xs text-gray-500">Due Date</th>
                                            <th class="px-3 py-2 text-right text-xs text-gray-500">Total</th>
                                            <th class="px-3 py-2 text-right text-xs text-gray-500">Paid</th>
                                            <th class="px-3 py-2 text-right text-xs text-gray-500">Due</th>
                                            <th class="px-3 py-2 text-right text-xs text-gray-500">Allocate Now</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse ($allocations as $index => $row)
                                            <tr>
                                                <td class="px-3 py-2 text-sm text-gray-700">{{ $row['bill_no'] }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-700">{{ $row['bill_date'] ? \Illuminate\Support\Carbon::parse($row['bill_date'])->format('d M, Y') : 'N/A' }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-700">{{ $row['due_date'] ? \Illuminate\Support\Carbon::parse($row['due_date'])->format('d M, Y') : 'N/A' }}</td>
                                                <td class="px-3 py-2 text-right text-sm text-gray-700">{{ number_format((float) $row['total_amount'], 2) }}</td>
                                                <td class="px-3 py-2 text-right text-sm text-gray-700">{{ number_format((float) $row['paid_amount'], 2) }}</td>
                                                <td class="px-3 py-2 text-right text-sm font-medium text-gray-700">{{ number_format((float) $row['due_amount'], 2) }}</td>
                                                <td class="px-3 py-2 text-right">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="{{ (float) $row['due_amount'] }}"
                                                        wire:model.live="allocations.{{ $index }}.allocate_now"
                                                        class="w-32 rounded-md border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                    <x-input-error for="allocations.{{ $index }}.allocate_now" class="mt-1" />
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">No pending bills found for selected supplier.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <x-input-error for="allocations" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="actions">
                @can($editMode ? 'supplier.payment.edit' : 'supplier.payment.create')
                    <x-button type="submit">{{ $editMode ? 'Update Payment' : 'Create Payment' }}</x-button>
                @endcan
                <a href="{{ route('admin.supplier.payments.index') }}" class="ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Cancel
                </a>
            </x-slot>
        </x-form-section>
    </div>
</div>
