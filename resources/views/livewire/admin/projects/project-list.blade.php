{{-- ======================== Page Layout Start From Here ======================== --}}
<div x-data x-init="$store.pageName = { name: 'Projects Management', slug: 'projects' }">
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
                <li class="text-sm text-gray-800 dark:text-white/90" x-text="$store.pageName?.name ?? ''"></li>
            </ol>
        </nav>
    </div>
    {{-- ======================== Page Header End Here ======================== --}}

    <div class="flex-1 w-full bg-white rounded-lg min-h-[80vh]">
        {{-- ======================== Content Start From Here ======================== --}}
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <div class="flex items-center justify-between">
                    <div>
                        @can('project.create')
                            {{-- Alpine dispatches open-create-modal instantly — no server round-trip --}}
                            <button type="button"
                                @click="$dispatch('open-create-modal')"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-xs ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                                <svg class="stroke-current" width="20" height="20" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Create New Project
                            </button>
                        @endcan
                    </div>
                    <div>
                        <input type="text" wire:model.live.debounce="search" placeholder="Search projects..."
                            class="dark:bg-dark-900 shadow-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                <!-- ====== Table Start -->
                <div
                    class="overflow-hidden  rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="max-w-full overflow-x-auto min-h-[70vh]">
                        <table class="min-w-full">
                            <!-- table header start -->
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Project
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Type & Location
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Dates
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Status
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Budget
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center justify-end w-full">
                                            <p class="text-xs text-right font-medium text-gray-500 dark:text-gray-400">
                                                Actions
                                            </p>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <!-- table header end -->
                            <!-- table body start -->
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">

                                @forelse ($projects as $project)
                                    <tr>
                                        <td class="px-5 py-4 sm:px-6">
                                            <div class="flex items-center">
                                                <div class="flex items-center gap-3">
                                                    <div>
                                                        <img src="{{ $project->image ? file_path($project->image) : 'https://ui-avatars.com/api/?name=' . urlencode($project->name) . '&background=111827&color=fff&rounded=falese&bold=true' }}"
                                                            class="h-15 w-15 rounded-sm object-cover">
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                                            {{ $project->name }}
                                                        </p>
                                                        @if ($project->code)
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                Code: {{ $project->code }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <div>
                                                <p class="text-sm text-gray-800 dark:text-white/90">
                                                    {{ implode(', ', $project->typeLabels()) ?: '—' }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ Str::limit($project->location, 30) }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <div>
                                                <p class="text-sm text-gray-800 dark:text-white/90">
                                                    {{ $project->start_date->format('M d, Y') }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    to {{ $project->end_date->format('M d, Y') }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <span
                                                class="inline-block px-2 py-1 text-xs font-medium rounded-full {{ $project->status?->badge() }} ">
                                                {{ $project->status?->label() }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <p class="text-sm text-gray-800 dark:text-white/90">
                                                @if ($project->budget)
                                                    ${{ number_format($project->budget, 2) }}
                                                @else
                                                    -
                                                @endif
                                            </p>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <div class="flex items-center justify-end gap-2" x-data="{ open: false }">
                                                {{-- Details button --}}
                                                <a href="{{ route('admin.projects.details', $project) }}"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-[#0d2a4a] px-3 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-[#0a2240]">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg>
                                                    Details
                                                </a>

                                                {{-- More actions dropdown --}}
                                                <div class="relative">
                                                    <button type="button" @click="open = !open"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                                        <span class="sr-only">More actions</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                        </svg>
                                                    </button>
                                                    <div x-show="open" @click.away="open = false"
                                                        x-transition:enter="transition ease-out duration-150"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        x-transition:leave="transition ease-in duration-100"
                                                        x-transition:leave-start="opacity-100 scale-100"
                                                        x-transition:leave-end="opacity-0 scale-95"
                                                        class="absolute right-0 z-50 mt-1 w-40 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                                                        style="display: none;">
                                                        @can('project.edit')
                                                            {{-- Open modal instantly via Alpine, load data via Livewire in background --}}
                                                            <button type="button"
                                                                @click="open = false; $dispatch('open-edit-modal', { id: {{ $project->id }} })"
                                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                                                </svg>
                                                                Edit
                                                            </button>
                                                        @endcan
                                                        @can('project.delete')
                                                            <button x-data
                                                                @click="
                                                                    open = false;
                                                                    Swal.fire({
                                                                        title: 'Are you sure?',
                                                                        text: 'This record & all related data will be permanently deleted!',
                                                                        icon: 'warning',
                                                                        showCancelButton: true,
                                                                        confirmButtonColor: '#d33',
                                                                        confirmButtonText: 'Yes, delete project!'
                                                                    }).then((result) => {
                                                                        if (result.isConfirmed) {
                                                                            $wire.deleteProject({{ $project->id }})
                                                                        }
                                                                    })
                                                                "
                                                                type="button"
                                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50 dark:hover:bg-red-500/10">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                </svg>
                                                                Delete
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6"
                                            class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No projects found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- ====== Table End -->

                {{-- Pagination --}}
                @if ($projects->hasPages())
                    <div class="mt-6">
                        {{ $projects->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== Create Project Modal (Alpine-driven, opens instantly) ===== --}}
    <div
        x-data="{ show: false }"
        @open-create-modal.window="show = true; $wire.resetCreate()"
        @close-create-modal.window="show = false"
        @keydown.escape.window="show = false"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
        style="display:none;">

        {{-- Backdrop --}}
        <div x-show="show" @click="show = false"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500/75"></div>

        {{-- Panel --}}
        <div x-show="show"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="relative bg-white rounded-lg shadow-xl sm:max-w-2xl sm:mx-auto overflow-hidden mb-6">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Create New Project</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Fill in the details to create a new project</p>
                </div>
                <button @click="show = false" type="button"
                    class="rounded-md p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="px-6 py-5 overflow-y-auto max-h-[72vh]">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="sm:col-span-2">
                        <x-label for="create_name" value="Project Name *" />
                        <x-input wire:model="create_name" id="create_name" type="text" class="mt-1 block w-full"
                            placeholder="Enter project name" />
                        <x-input-error for="create_name" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="create_code" value="Project Code" />
                        <x-input wire:model="create_code" id="create_code" type="text" class="mt-1 block w-full"
                            placeholder="e.g. SUDP001" />
                        <x-input-error for="create_code" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="create_status" value="Status *" />
                        <select wire:model="create_status" id="create_status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Select Status</option>
                            @foreach(\App\Enums\Project\Status::cases() as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="create_status" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-label value="Project Type * (select one or more)" />
                        <div class="mt-2 flex flex-wrap gap-3">
                            @foreach(\App\Enums\Project\Type::cases() as $type)
                                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox"
                                        wire:model="create_project_type"
                                        value="{{ $type->value }}"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $type->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error for="create_project_type" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-label for="create_location" value="Location *" />
                        <x-input wire:model="create_location" id="create_location" type="text"
                            class="mt-1 block w-full" placeholder="Full address" />
                        <x-input-error for="create_location" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="create_land_area" value="Land Area (sft)" />
                        <x-input wire:model="create_land_area" id="create_land_area" type="number"
                            step="0.01" class="mt-1 block w-full" placeholder="e.g. 12500" />
                        <x-input-error for="create_land_area" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="create_building_area" value="Building Area (sft)" />
                        <x-input wire:model="create_building_area" id="create_building_area" type="number"
                            step="0.01" class="mt-1 block w-full" placeholder="e.g. 96400" />
                        <x-input-error for="create_building_area" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="create_start_date" value="Start Date *" />
                        <x-input wire:model="create_start_date" id="create_start_date" type="date"
                            class="mt-1 block w-full flatpickr-only-date" />
                        <x-input-error for="create_start_date" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="create_end_date" value="End Date *" />
                        <x-input wire:model="create_end_date" id="create_end_date" type="date"
                            class="mt-1 block w-full flatpickr-only-date" />
                        <x-input-error for="create_end_date" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="create_handover_date" value="Handover Date" />
                        <x-input wire:model="create_handover_date" id="create_handover_date" type="date"
                            class="mt-1 block w-full flatpickr-only-date" />
                        <x-input-error for="create_handover_date" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="create_budget" value="Budget (BDT)" />
                        <x-input wire:model="create_budget" id="create_budget" type="number"
                            step="0.01" class="mt-1 block w-full" placeholder="e.g. 42000000" />
                        <x-input-error for="create_budget" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="create_chief_engineer_id" value="Chief Engineer" />
                        <select wire:model="create_chief_engineer_id" id="create_chief_engineer_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">— None —</option>
                            @foreach($engineers as $eng)
                                <option value="{{ $eng->id }}">{{ $eng->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="create_chief_engineer_id" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="create_site_engineer_id" value="Site Engineer" />
                        <select wire:model="create_site_engineer_id" id="create_site_engineer_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">— None —</option>
                            @foreach($engineers as $eng)
                                <option value="{{ $eng->id }}">{{ $eng->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="create_site_engineer_id" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-label for="create_description" value="Description" />
                        <textarea wire:model="create_description" id="create_description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            placeholder="Project description (optional)"></textarea>
                        <x-input-error for="create_description" class="mt-1" />
                    </div>

                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                <button @click="show = false" type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button wire:click="saveCreate"
                    type="button"
                    wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0d2a4a] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#0a2240] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <span wire:loading.remove wire:target="saveCreate">Create Project</span>
                    <span wire:loading wire:target="saveCreate">Creating…</span>
                </button>
            </div>

        </div>
    </div>

    {{-- ===== Edit Project Modal (Alpine-driven: opens instantly, data loads behind skeleton) ===== --}}
    <div
        x-data="{
            show: false,
            loading: true,
            init() {
                window.addEventListener('open-edit-modal', (e) => {
                    this.show = true;
                    this.loading = true;
                    $wire.loadEditData(e.detail.id);
                });
                window.addEventListener('edit-data-ready', () => {
                    this.loading = false;
                });
                window.addEventListener('close-edit-modal', () => {
                    this.show = false;
                });
            }
        }"
        @keydown.escape.window="show = false"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
        style="display:none;">

        {{-- Backdrop --}}
        <div x-show="show" @click="show = false"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500/75"></div>

        {{-- Panel --}}
        <div x-show="show"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="relative bg-white rounded-lg shadow-xl sm:max-w-2xl sm:mx-auto overflow-hidden mb-6">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Edit Project</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Update project details</p>
                </div>
                <button @click="show = false" type="button"
                    class="rounded-md p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Loading skeleton — shown until Livewire finishes loading data --}}
            <div x-show="loading" class="px-6 py-8 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    @foreach(range(1,8) as $_)
                        <div class="{{ $_ === 1 ? 'col-span-2' : '' }} space-y-2">
                            <div class="h-3 w-24 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-9 w-full bg-gray-100 rounded-md animate-pulse"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Actual form — hidden until data is ready --}}
            <div x-show="!loading" class="px-6 py-5 overflow-y-auto max-h-[72vh]">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="sm:col-span-2">
                        <x-label for="edit_name" value="Project Name *" />
                        <x-input wire:model="edit_name" id="edit_name" type="text" class="mt-1 block w-full"
                            placeholder="Enter project name" />
                        <x-input-error for="edit_name" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_code" value="Project Code" />
                        <x-input wire:model="edit_code" id="edit_code" type="text" class="mt-1 block w-full"
                            placeholder="e.g. SUDP001" />
                        <x-input-error for="edit_code" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_progress_pct" value="Construction Progress (%)" />
                        <x-input wire:model="edit_progress_pct" id="edit_progress_pct" type="number"
                            min="0" max="100" class="mt-1 block w-full" placeholder="0–100" />
                        <x-input-error for="edit_progress_pct" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-label value="Project Type * (select one or more)" />
                        <div class="mt-2 flex flex-wrap gap-3">
                            @foreach(\App\Enums\Project\Type::cases() as $type)
                                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox"
                                        wire:model="edit_project_type"
                                        value="{{ $type->value }}"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $type->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error for="edit_project_type" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_status" value="Status *" />
                        <select wire:model="edit_status" id="edit_status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Select Status</option>
                            @foreach(\App\Enums\Project\Status::cases() as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="edit_status" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-label for="edit_location" value="Location *" />
                        <x-input wire:model="edit_location" id="edit_location" type="text"
                            class="mt-1 block w-full" placeholder="Full address" />
                        <x-input-error for="edit_location" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_land_area" value="Land Area (sft)" />
                        <x-input wire:model="edit_land_area" id="edit_land_area" type="number"
                            step="0.01" class="mt-1 block w-full" placeholder="e.g. 12500" />
                        <x-input-error for="edit_land_area" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_building_area" value="Building Area (sft)" />
                        <x-input wire:model="edit_building_area" id="edit_building_area" type="number"
                            step="0.01" class="mt-1 block w-full" placeholder="e.g. 96400" />
                        <x-input-error for="edit_building_area" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_start_date" value="Start Date *" />
                        <x-input wire:model="edit_start_date" id="edit_start_date" type="date"
                            class="mt-1 block w-full flatpickr-only-date" />
                        <x-input-error for="edit_start_date" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_end_date" value="End Date *" />
                        <x-input wire:model="edit_end_date" id="edit_end_date" type="date"
                            class="mt-1 block w-full flatpickr-only-date" />
                        <x-input-error for="edit_end_date" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_handover_date" value="Handover Date" />
                        <x-input wire:model="edit_handover_date" id="edit_handover_date" type="date"
                            class="mt-1 block w-full flatpickr-only-date" />
                        <x-input-error for="edit_handover_date" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_budget" value="Budget (BDT)" />
                        <x-input wire:model="edit_budget" id="edit_budget" type="number"
                            step="0.01" class="mt-1 block w-full" placeholder="e.g. 42000000" />
                        <x-input-error for="edit_budget" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_chief_engineer_id" value="Chief Engineer" />
                        <select wire:model="edit_chief_engineer_id" id="edit_chief_engineer_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">— None —</option>
                            @foreach($engineers as $eng)
                                <option value="{{ $eng->id }}">{{ $eng->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="edit_chief_engineer_id" class="mt-1" />
                    </div>

                    <div>
                        <x-label for="edit_site_engineer_id" value="Site Engineer" />
                        <select wire:model="edit_site_engineer_id" id="edit_site_engineer_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">— None —</option>
                            @foreach($engineers as $eng)
                                <option value="{{ $eng->id }}">{{ $eng->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="edit_site_engineer_id" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-label for="edit_description" value="Description" />
                        <textarea wire:model="edit_description" id="edit_description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            placeholder="Project description (optional)"></textarea>
                        <x-input-error for="edit_description" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-media-picker-field field="edit_image" :value="$edit_image"
                            placeholder="Click to upload cover image" :multiple="false"
                            type="image" label="Cover Image" required="false" />
                    </div>

                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                <button @click="show = false" type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button wire:click="saveEdit"
                    type="button"
                    :disabled="loading"
                    wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0d2a4a] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#0a2240] transition disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    <span wire:loading.remove wire:target="saveEdit">Save Changes</span>
                    <span wire:loading wire:target="saveEdit">Saving…</span>
                </button>
            </div>

        </div>
    </div>

</div>
