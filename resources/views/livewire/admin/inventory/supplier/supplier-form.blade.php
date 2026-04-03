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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.suppliers.index') }}">
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
            <x-slot name="description">Manage supplier contacts used for purchase and stock receive workflows.</x-slot>

            <x-slot name="form">
                <div class="col-span-6 sm:col-span-3">
                    <x-label for="name" value="Supplier Name *" />
                    <x-input wire:model="name" id="name" type="text" class="mt-1 block w-full" placeholder="ABC Traders Ltd." />
                    <x-input-error for="name" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="contact_person" value="Contact Person" />
                    <x-input wire:model="contact_person" id="contact_person" type="text" class="mt-1 block w-full" placeholder="Mr. Rahman" />
                    <x-input-error for="contact_person" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="phone" value="Phone" />
                    <x-input wire:model="phone" id="phone" type="text" class="mt-1 block w-full" placeholder="+8801XXXXXXXXX" />
                    <x-input-error for="phone" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="secondary_phone" value="Secondary Phone" />
                    <x-input wire:model="secondary_phone" id="secondary_phone" type="text" class="mt-1 block w-full" placeholder="+8801XXXXXXXXX" />
                    <x-input-error for="secondary_phone" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="email" value="Email" />
                    <x-input wire:model="email" id="email" type="email" class="mt-1 block w-full" placeholder="supplier@example.com" />
                    <x-input-error for="email" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="status" value="Status" />
                    <label for="status" class="mt-2 inline-flex cursor-pointer items-center gap-2">
                        <input id="status" type="checkbox" wire:model="status" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <x-input-error for="status" class="mt-2" />
                </div>

                <div class="col-span-6">
                    <x-label for="address" value="Address" />
                    <x-input wire:model="address" id="address" type="text" class="mt-1 block w-full" placeholder="Supplier address" />
                    <x-input-error for="address" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="actions">
                @can($editMode ? 'inventory.supplier.update' : 'inventory.supplier.create')
                    <x-button type="submit">{{ $editMode ? 'Update Supplier' : 'Create Supplier' }}</x-button>
                @endcan
                <a href="{{ route('admin.inventory.suppliers.index') }}" class="ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</a>
            </x-slot>
        </x-form-section>
    </div>
</div>
