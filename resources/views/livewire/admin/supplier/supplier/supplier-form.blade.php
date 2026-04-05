<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Edit Supplier' : 'Create Supplier' }}', slug: 'suppliers' }">
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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.supplier.suppliers.index') }}">
                        Suppliers
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
            <x-slot name="title">{{ $editMode ? 'Update Supplier' : 'Create New Supplier' }}</x-slot>
            <x-slot name="description">Manage supplier profile, compliance details, and opening financial setup.</x-slot>

            <x-slot name="form">
                <div class="col-span-6 sm:col-span-3">
                    <x-label for="name" value="Name *" />
                    <x-input wire:model="name" id="name" type="text" class="mt-1 block w-full" placeholder="ABC Traders Ltd." />
                    <x-input-error for="name" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="code" value="Code *" />
                    <x-input wire:model="code" id="code" type="text" class="mt-1 block w-full" placeholder="SUP-000001" />
                    <x-input-error for="code" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="company_name" value="Company Name" />
                    <x-input wire:model="company_name" id="company_name" type="text" class="mt-1 block w-full" placeholder="ABC Holdings" />
                    <x-input-error for="company_name" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="contact_person" value="Contact Person" />
                    <x-input wire:model="contact_person" id="contact_person" type="text" class="mt-1 block w-full" placeholder="Mr. Rahman" />
                    <x-input-error for="contact_person" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="phone" value="Phone *" />
                    <x-input wire:model="phone" id="phone" type="text" class="mt-1 block w-full" placeholder="+8801XXXXXXXXX" />
                    <x-input-error for="phone" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="alternate_phone" value="Alternate Phone" />
                    <x-input wire:model="alternate_phone" id="alternate_phone" type="text" class="mt-1 block w-full" placeholder="+8801XXXXXXXXX" />
                    <x-input-error for="alternate_phone" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="email" value="Email" />
                    <x-input wire:model="email" id="email" type="email" class="mt-1 block w-full" placeholder="supplier@example.com" />
                    <x-input-error for="email" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="status" value="Status" />
                    <select wire:model="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <x-input-error for="status" class="mt-2" />
                </div>

                <div class="col-span-6">
                    <x-label for="address" value="Address" />
                    <textarea wire:model="address" id="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Supplier office address"></textarea>
                    <x-input-error for="address" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="trade_license_no" value="Trade License No" />
                    <x-input wire:model="trade_license_no" id="trade_license_no" type="text" class="mt-1 block w-full" />
                    <x-input-error for="trade_license_no" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="tin_no" value="TIN No" />
                    <x-input wire:model="tin_no" id="tin_no" type="text" class="mt-1 block w-full" />
                    <x-input-error for="tin_no" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="bin_no" value="BIN No" />
                    <x-input wire:model="bin_no" id="bin_no" type="text" class="mt-1 block w-full" />
                    <x-input-error for="bin_no" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="opening_balance" value="Opening Balance" />
                    <x-input wire:model="opening_balance" id="opening_balance" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                    <x-input-error for="opening_balance" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="opening_balance_type" value="Opening Balance Type" />
                    <select wire:model="opening_balance_type" id="opening_balance_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($openingBalanceTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="opening_balance_type" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-2">
                    <x-label for="payment_terms_days" value="Payment Terms (Days)" />
                    <x-input wire:model="payment_terms_days" id="payment_terms_days" type="number" min="0" class="mt-1 block w-full" />
                    <x-input-error for="payment_terms_days" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="credit_limit" value="Credit Limit" />
                    <x-input wire:model="credit_limit" id="credit_limit" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                    <x-input-error for="credit_limit" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="notes" value="Notes" />
                    <textarea wire:model="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <x-input-error for="notes" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="actions">
                @can($editMode ? 'supplier.edit' : 'supplier.create')
                    <x-button type="submit">{{ $editMode ? 'Update Supplier' : 'Create Supplier' }}</x-button>
                @endcan
                <a href="{{ route('admin.supplier.suppliers.index') }}" class="ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</a>
            </x-slot>
        </x-form-section>
    </div>
</div>
