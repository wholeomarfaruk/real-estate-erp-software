{{-- ======================== Page Layout Start From Here ======================== --}}
<div x-data x-init="$store.pageName = { name: 'Create New Project', slug: 'project-create' }">
    {{-- ======================== Page Header Start From Here ======================== --}}
    <div class="flex flex-wrap justify-between gap-6 ">
        {{-- Page Name  --}}
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''">
        </h1>
        {{-- Breadcrumb  --}}
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="{{ route('admin.dashboard') }}">
                        Dashboard
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="{{ route('admin.projects.list') }}">
                        Projects
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90" x-text="$store.pageName?.name ?? ''"></li>
            </ol>
        </nav>
    </div>
    {{-- ======================== Page Header End Here ======================== --}}

    <div class="flex-1 w-full bg-white rounded-lg min-h-[80vh]">
        {{-- ======================== Content Start From Here ======================== --}}
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <x-form-section submit="save">
                    <x-slot name="title">Create New Project</x-slot>
                    <x-slot name="description">Fill in the details below to create a new project.</x-slot>

                    <x-slot name="form">
                        <!-- Project Name -->
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="name" value="Project Name *" />
                            <x-input wire:model="name" id="name" type="text" class="mt-1 block w-full"
                                placeholder="Enter project name" required />
                            <x-input-error for="name" class="mt-2" />
                        </div>

                        <!-- Project Code -->
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="code" value="Project Code *" />
                            <div class="flex items-center gap-2">
                            <x-input wire:model="code" id="code" type="text" class="mt-1 flex-1 block w-full"
                                placeholder="Enter project code" />
                            <span class="w-[20%]"><button wire:click="generateCode" class="text-indego-500 hover:text-indego-600">Generate Code</button></span>
                            </div>
                            <x-input-error for="code" class="mt-2" />
                        </div>

                        <!-- Project Type -->
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="project_type" value="Project Type *" />
                              <select wire:model="project_type" id="project_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">Select Type</option>
                                @foreach (\App\Enums\Project\Type::cases() as $type )
                                    <option value="{{ $type->value }}">{{ $type?->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="project_type" class="mt-2" />
                        </div>

                        <!-- Status -->
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="status" value="Status *" />
                            <select wire:model="status" id="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">Select Status</option>
                                @foreach (\App\Enums\Project\Status::cases() as $status )
                                    <option value="{{ $status->value }}">{{ $status?->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="status" class="mt-2" />
                        </div>

                        <!-- Location -->
                        <div class="col-span-6">
                            <x-label for="location" value="Location *" />
                            <x-input wire:model="location" id="location" placeholder="Enter project address" type="text" class="mt-1 block w-full" required />

                            <x-input-error for="location" class="mt-2" />
                        </div>

                        <!-- Start Date -->
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="start_date" value="Start Date *" />
                            <x-input wire:model="start_date" id="start_date" type="date" class="mt-1 block w-full" required />
                            <x-input-error for="start_date" class="mt-2" />
                        </div>

                        <!-- End Date -->
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="end_date" value="End Date *" />
                            <x-input wire:model="end_date" id="end_date" type="date" class="mt-1 block w-full" required />
                            <x-input-error for="end_date" class="mt-2" />
                        </div>

                        <!-- Budget -->
                        <div class="col-span-6 sm:col-span-3">
                            <x-label for="budget" value="Budget" />
                            <x-input wire:model="budget" id="budget" type="number" step="0.01" class="mt-1 block w-full"
                                placeholder="Enter budget amount (optional)" />
                            <x-input-error for="budget" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div class="col-span-6">
                            <x-label for="description" value="Description" />
                            <textarea wire:model="description" id="description" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                placeholder="Enter project description (optional)"></textarea>
                            <x-input-error for="description" class="mt-2" />
                        </div>
                            <!-- Description -->
                        <div class="col-span-6">
                            <x-media-picker-field field="image" :value="$image" placeholder="Click to Upload Image" multiple="false" type="image" label="Project Image" required="true" />
                        </div>
                    </x-slot>

                    <x-slot name="actions">
                        <x-button type="submit" class="ml-3">
                            Create Project
                        </x-button>
                        <a href="{{ route('admin.projects.list') }}"
                            class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                    </x-slot>
                </x-form-section>
            </div>
        </div>
    </div>
</div>
