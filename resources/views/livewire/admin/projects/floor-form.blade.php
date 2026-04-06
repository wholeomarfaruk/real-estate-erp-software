<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Update Floor' : 'Create New Floor' }}' }">
    <div class="flex flex-wrap justify-between gap-6 ">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
    </div>

    <div class="flex-1 w-full bg-white rounded-lg min-h-[60vh]">
        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <x-form-section submit="save">
                    <x-slot name="title">{{ $editMode ? 'Update Floor' : 'Create New Floor' }}</x-slot>
                    <x-slot name="description">Provide floor details below.</x-slot>

                    <x-slot name="form">
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="project_id" value="Project *" />
                            <select wire:model="project_id" id="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Select Project</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="project_id" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="floor_name" value="Floor Name *" />
                            <x-input wire:model="floor_name" id="floor_name" type="text" class="mt-1 block w-full" placeholder="Enter floor name" />
                            <x-input-error for="floor_name" class="mt-2" />
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="floor_type" value="Floor Type *" />
                            <x-input wire:model="floor_type" id="floor_type" type="text" class="mt-1 block w-full" placeholder="e.g. residential, commercial" />
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
                    </x-slot>

                    <x-slot name="actions">
                        <x-button type="submit" class="ml-3">{{ $editMode ? 'Update Floor' : 'Create Floor' }}</x-button>
                        <a href="{{ route('admin.floors.list') }}" class="ml-3 inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                    </x-slot>
                </x-form-section>
            </div>
        </div>
    </div>
</div>
