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
                            <a href="{{ route('admin.projects.create') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-xs ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                                <svg class="stroke-current" width="20" height="20" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Create New Project
                            </a>
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

                                                    {{ $project->project_type?->label() }}
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
                                            <div class="relative px-4 py-3 text-right" x-data="{ open: false }">
                                                <!-- Trigger -->
                                                <button type="button" @click="open = !open"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white">
                                                    <span class="sr-only">Open actions</span>

                                                    <!-- Three dots icon -->
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                        viewBox="0 0 20 20" fill="currentColor">
                                                        <path
                                                            d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                    </svg>
                                                </button>

                                                <!-- Dropdown -->
                                                <div x-show="open" @click.away="open = false"
                                                    x-transition:enter="transition ease-out duration-150"
                                                    x-transition:enter-start="opacity-0 scale-95"
                                                    x-transition:enter-end="opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-100"
                                                    x-transition:leave-start="opacity-100 scale-100"
                                                    x-transition:leave-end="opacity-0 scale-95"
                                                    class="absolute right-0 z-50 mt-2 w-44 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                                                    style="display: none;">
                                                    <a href="{{ route('admin.projects.details', $project) }}"
                                                        class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700">
                                                        View
                                                    </a>

                                                    <a href="{{ route('admin.projects.create', ['project_id' => $project->id]) }}"
                                                        class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700">
                                                        Edit
                                                    </a>
                                                    @can('project.delete')
                                                        <button x-data
                                                            @click="
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
                                                            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50 dark:hover:bg-red-500/10">
                                                            Delete
                                                        </button>
                                                    @endcan
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

    {{-- View Modal --}}
    @if ($viewModal && $selectedProject)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Project Details: {{ $selectedProject->name }}
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">Basic Information</h4>
                                        <div class="space-y-2 text-sm">
                                            <p><strong>Name:</strong> {{ $selectedProject->name }}</p>
                                            <p><strong>Code:</strong> {{ $selectedProject->code ?? 'N/A' }}</p>
                                            <p><strong>Type:</strong> {{ $selectedProject->project_type?->label() }}
                                            </p>
                                            <p><strong>Status:</strong>
                                                <span
                                                    class="inline-block px-2 py-1 text-xs font-medium rounded-full
                                                @if ($selectedProject->status === 'completed') bg-green-100 text-green-800
                                                @elseif($selectedProject->status === 'ongoing') bg-blue-100 text-blue-800
                                                @elseif($selectedProject->status === 'on_hold') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                    {{ $selectedProject->status?->label() }}
                                                </span>
                                            </p>
                                            <p><strong>Budget:</strong>
                                                {{ $selectedProject->budget ? '$' . number_format($selectedProject->budget, 2) : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">Timeline & Location</h4>
                                        <div class="space-y-2 text-sm">
                                            <p><strong>Start Date:</strong>
                                                {{ $selectedProject->start_date->format('M d, Y') }}</p>
                                            <p><strong>End Date:</strong>
                                                {{ $selectedProject->end_date->format('M d, Y') }}</p>
                                            <p><strong>Location:</strong> {{ $selectedProject->location }}</p>
                                            <p><strong>Description:</strong>
                                                {{ $selectedProject->description ?? 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <h4 class="font-medium text-gray-900 mb-2">Project Statistics</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <div class="text-2xl font-bold text-blue-600">
                                                {{ $selectedProject->floors->count() }}</div>
                                            <div class="text-sm text-gray-600">Floors</div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <div class="text-2xl font-bold text-green-600">
                                                {{ $selectedProject->units->count() }}</div>
                                            <div class="text-sm text-gray-600">Units</div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <div class="text-2xl font-bold text-purple-600">
                                                {{ $selectedProject->units->where('availability_status', 'available')->count() }}
                                            </div>
                                            <div class="text-sm text-gray-600">Available Units</div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <div class="text-2xl font-bold text-orange-600">
                                                {{ $selectedProject->units->where('availability_status', 'sold')->count() }}
                                            </div>
                                            <div class="text-sm text-gray-600">Sold Units</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="closeViewModal" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
