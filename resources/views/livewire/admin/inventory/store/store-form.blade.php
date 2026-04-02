<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Edit Store' : 'Create Store' }}', slug: 'stores' }">
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
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.stores.index') }}">
                        Store Management
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
            <x-slot name="title">{{ $editMode ? 'Update Store' : 'Create New Store' }}</x-slot>
            <x-slot name="description">Configure office and project stores for inventory control and transfers.</x-slot>

            <x-slot name="form">
                <div class="col-span-6 sm:col-span-3">
                    <x-label for="name" value="Store Name *" />
                    <x-input wire:model="name" id="name" type="text" class="mt-1 block w-full" placeholder="Bashundhara Office Store" />
                    <x-input-error for="name" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="code" value="Store Code *" />
                    <x-input wire:model="code" id="code" type="text" class="mt-1 block w-full" placeholder="ST-BO-001" />
                    <x-input-error for="code" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <x-label for="type" value="Store Type *" />
                    <select wire:model.live="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select type</option>
                        @foreach ($types as $storeType)
                            <option value="{{ $storeType->value }}">{{ $storeType->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="type" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3" x-data="{ selectedType: @entangle('type').live }" x-show="selectedType === '{{ \App\Enums\Inventory\StoreType::PROJECT->value }}'" x-cloak>
                    <x-label for="project_id" value="Project *" />
                    <select wire:model="project_id" id="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->code }})</option>
                        @endforeach
                    </select>
                    <x-input-error for="project_id" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3" x-data="{ selectedType: @entangle('type').live }" x-show="selectedType === '{{ \App\Enums\Inventory\StoreType::OFFICE->value }}'" x-cloak>
                    <x-label value="Project" />
                    <p class="mt-2 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-500">Office stores are not linked to any project.</p>
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
                    <x-input wire:model="address" id="address" type="text" class="mt-1 block w-full" placeholder="Store location address" />
                    <x-input-error for="address" class="mt-2" />
                </div>

                <div class="col-span-6">
                    <x-label for="description" value="Description" />
                    <textarea wire:model="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Optional notes about this store"></textarea>
                    <x-input-error for="description" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="actions">
                <x-button type="submit">{{ $editMode ? 'Update Store' : 'Create Store' }}</x-button>
                <a href="{{ route('admin.inventory.stores.index') }}" class="ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</a>
            </x-slot>
        </x-form-section>
    </div>
</div>