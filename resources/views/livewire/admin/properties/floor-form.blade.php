<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Update Floor' : 'Create New Floor' }}' }">
    <div class="flex flex-wrap justify-between gap-6 ">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="{{ route('admin.dashboard') }}">
                        Dashboard
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="{{ route('admin.projects.properties') }}">
                        Properties
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="{{ route('admin.projects.properties.details', $property) }}">
                        {{ $property->name }}
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90" x-text="$store.pageName?.name ?? ''"></li>
            </ol>
        </nav>
    </div>

    <div class="flex-1 w-full bg-white rounded-lg min-h-[60vh]">
        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <x-form-section submit="save">
                    <x-slot name="title">{{ $editMode ? 'Update Floor' : 'Create New Floor' }}</x-slot>
                    <x-slot name="description">Provide floor details for {{ $property->name }}.</x-slot>

                    <x-slot name="form">
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="floor_number" value="Floor Number" />
                            <x-input wire:model="floor_number" id="floor_number" type="number" class="mt-1 block w-full" placeholder="e.g. 1" />
                            <x-input-error for="floor_number" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="floor_name" value="Floor Name *" />
                            <x-input wire:model="floor_name" id="floor_name" type="text" class="mt-1 block w-full" placeholder="Enter floor name" />
                            <x-input-error for="floor_name" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="floor_type" value="Floor Type" />
                            <x-input wire:model="floor_type" id="floor_type" type="text" class="mt-1 block w-full" placeholder="e.g. residential" />
                            <x-input-error for="floor_type" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="status" value="Status *" />
                            <select wire:model="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="planned">Planned</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                            </select>
                            <x-input-error for="status" class="mt-2" />
                        </div>

                        <div class="col-span-6">
                            <x-label for="notes" value="Notes" />
                            <textarea wire:model="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                            <x-input-error for="notes" class="mt-2" />
                        </div>
                    </x-slot>

                    <x-slot name="actions">
                        <x-button type="submit" class="ml-3">{{ $editMode ? 'Update Floor' : 'Create Floor' }}</x-button>
                        <a href="{{ route('admin.projects.properties.floors', $property->id) }}" class="ml-3 inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                    </x-slot>
                </x-form-section>
            </div>
        </div>
    </div>
</div>
