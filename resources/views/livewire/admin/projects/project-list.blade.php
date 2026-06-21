{{-- ======================== Page Layout Start From Here ======================== --}}
<div x-data x-init="$store.pageName = { name: 'Projects Management', slug: 'projects' }">
    {{-- ======================== Page Header Start From Here ======================== --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Manage and track all your real estate projects</p>
            </div>
            {{-- Breadcrumb  --}}
            <nav class="text-sm">
                <ol class="flex items-center gap-2">
                    <li>
                        <a class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition"
                            href="{{ route('admin.dashboard') }}">
                            Dashboard
                        </a>
                    </li>
                    <li class="text-gray-400">/</li>
                    <li class="text-gray-900 dark:text-white font-medium" x-text="$store.pageName?.name ?? ''"></li>
                </ol>
            </nav>
        </div>
    </div>
    {{-- ======================== Page Header End Here ======================== --}}

    {{-- ======================== Content Start From Here ======================== --}}
    <div class="grid grid-cols-1 gap-6">
        {{-- Search & Actions Bar --}}
        <div class="flex flex-col sm:flex-row gap-4 items-stretch sm:items-center bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 p-4 sm:p-6">
            <div class="flex-1 min-w-0">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.5 5.5a7.5 7.5 0 0 0 10.5 10.5Z" />
                    </svg>
                    <input type="text" wire:model.live.debounce="search" placeholder="Search by name, code, or location..."
                        class="w-full pl-11 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" />
                </div>
            </div>
            @can('project.create')
                <button type="button"
                    @click="window.dispatchEvent(new CustomEvent('open-create-modal')); $wire.resetCreate()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 hover:bg-blue-700 px-6 py-2.5 text-sm font-semibold text-white shadow-md transition whitespace-nowrap">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Project
                </button>
            @endcan
        </div>

        {{-- Projects Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @forelse ($projects as $project)
                <div class="group bg-white dark:bg-white/[0.03] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                    {{-- Image Section --}}
                    <div class="relative h-48 overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900">
                        <img src="{{ $project->image ? file_path($project->image) : 'https://ui-avatars.com/api/?name=' . urlencode($project->name) . '&background=3b82f6&color=fff&size=400&bold=true' }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

                        {{-- Status Badge --}}
                        <div class="absolute top-3 right-3">
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full {{ $project->status?->badge() }}">
                                {{ $project->status?->label() }}
                            </span>
                        </div>

                        {{-- Progress Indicator --}}
                        @if ($project->progress_pct)
                            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-300 dark:bg-gray-700">
                                <div class="h-full bg-green-500" style="width: {{ $project->progress_pct }}%"></div>
                            </div>
                        @endif
                    </div>

                    {{-- Content Section --}}
                    <div class="p-5 sm:p-6">
                        {{-- Project Name & Code --}}
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white line-clamp-2 mb-1">
                                {{ $project->name }}
                            </h3>
                            @if ($project->code)
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Code: {{ $project->code }}</p>
                            @endif
                        </div>

                        {{-- Type Tags --}}
                        <div class="mb-4">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($project->typeLabels() as $type)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300">
                                        {{ $type }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Location --}}
                        <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657 13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <span class="line-clamp-2">{{ $project->location ?: '—' }}</span>
                            </p>
                        </div>

                        {{-- Timeline --}}
                        <div class="mb-4 space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Start:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $project->start_date?->format('M d, Y') ?: '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">End:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $project->end_date?->format('M d, Y') ?: '—' }}</span>
                            </div>
                        </div>

                        {{-- Budget --}}
                        <div class="mb-5 pb-5 border-b border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Budget</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                @if ($project->budget)
                                    ৳{{ number_format($project->budget, 0) }}
                                @else
                                    —
                                @endif
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2" x-data="{ open: false }">
                            <a href="{{ route('admin.projects.details', $project) }}"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/20 px-4 py-2.5 text-sm font-semibold transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                View
                            </a>

                            {{-- More actions dropdown --}}
                            <div class="relative">
                                <button type="button" @click="open = !open"
                                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 px-3 py-2.5 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
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
                                    class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg"
                                    style="display: none;">
                                    @can('project.edit')
                                        <button type="button"
                                            @click="open = false; window.dispatchEvent(new CustomEvent('open-edit-modal')); $wire.loadEditData({{ $project->id }})"
                                            class="flex w-full items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition first:rounded-t-lg">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                            </svg>
                                            Edit
                                        </button>
                                    @endcan
                                    @can('project.delete')
                                        <button type="button"
                                            @click="
                                                open = false;
                                                Swal.fire({
                                                    title: 'Delete Project?',
                                                    text: 'This action cannot be undone. All related data will be permanently deleted.',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#dc2626',
                                                    confirmButtonText: 'Delete',
                                                    cancelButtonText: 'Cancel'
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        $wire.deleteProject({{ $project->id }})
                                                    }
                                                })
                                            "
                                            class="flex w-full items-center gap-3 px-4 py-3 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition last:rounded-b-lg">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                            Delete
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                {{-- Empty State --}}
                <div class="col-span-full">
                    <div class="rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">No projects found</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Get started by creating your first project or try adjusting your search filters.</p>
                        @can('project.create')
                            <button type="button"
                                @click="window.dispatchEvent(new CustomEvent('open-create-modal')); $wire.resetCreate()"
                                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 hover:bg-blue-700 px-6 py-2.5 text-sm font-semibold text-white shadow-md transition">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Create New Project
                            </button>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($projects->hasPages())
            <div class="mt-8">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
    {{-- ======================== Content End Here ======================== --}}

    @can('project.create')
        <x-project-form-modal mode="create" :engineers="$engineers" />
    @endcan

    @can('project.edit')
        <x-project-form-modal mode="edit" :engineers="$engineers" />
    @endcan

</div>
