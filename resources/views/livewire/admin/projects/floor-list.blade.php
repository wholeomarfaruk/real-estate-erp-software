{{-- ======================== Page Layout Start From Here ======================== --}}
<div x-data x-init="$store.pageName = { name: 'Floors Management', slug: 'floors' }">
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
                <div class="flex items-center justify-between">
                    <div class="flex gap-4">
                        @can('project.edit')
                        <a href="{{ route('admin.floors.create') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                            <svg class="stroke-current" width="20" height="20" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Create New Floor
                        </a>
                        @endcan

                        <!-- Project Filter -->
                        <div>
                            <select wire:model.live="selectedProject"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">All Projects</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <input type="text" wire:model.live.debounce="search" placeholder="Search floors..."
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                <!-- ====== Table Start -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="max-w-full overflow-x-auto">
                        <table class="min-w-full">
                            <!-- table header start -->
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                Floor
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                Project
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                Type
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                Status
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                Units
                                            </p>
                                        </div>
                                    </th>
                                    <th class="px-5 py-3 sm:px-6">
                                        <div class="flex items-center">
                                            <p class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                                                Actions
                                            </p>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <!-- table header end -->
                            <!-- table body start -->
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($floors as $floor)
                                    <tr>
                                        <td class="px-5 py-4 sm:px-6">
                                            <div class="flex items-center">
                                                <div class="flex items-center gap-3">
                                                    <div>
                                                        <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">
                                                            {{ $floor->floor_name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <p class="text-theme-sm text-gray-800 dark:text-white/90">
                                                {{ $floor->project->name }}
                                            </p>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <p class="text-theme-sm text-gray-800 dark:text-white/90">
                                                {{ $floor->floor_type }}
                                            </p>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full
                                                @if($floor->status === 'completed') bg-green-100 text-green-800
                                                @elseif($floor->status === 'ongoing') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst(str_replace('_', ' ', $floor->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <p class="text-theme-sm text-gray-800 dark:text-white/90">
                                                {{ $floor->units->count() }}
                                            </p>
                                        </td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <div class="flex items-center gap-2">
                                                <div class="inline-flex">
                                                    @can('project.edit')
                                                        <a href="{{ route('admin.floors.edit', $floor->id) }}"
                                                            class="-ml-px border border-gray-200 px-3 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-50 hover:text-gray-900 focus:z-10 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white focus:outline-none disabled:pointer-events-auto disabled:opacity-50">
                                                            Edit
                                                        </a>
                                                    @endcan
                                                    @can('project.edit')
                                                        <button wire:click="deleteFloor({{ $floor->id }})"
                                                            class="-ml-px rounded-r-sm border border-gray-200 px-3 py-2 font-medium text-red-700 transition-colors hover:bg-red-50 hover:text-red-900 focus:z-10 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-white focus:outline-none disabled:pointer-events-auto disabled:opacity-50 cursor-pointer">
                                                            Delete
                                                        </button>
                                                    @endcan
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                            No floors found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- ====== Table End -->

                {{-- Pagination --}}
                @if($floors->hasPages())
                <div class="mt-6">
                    {{ $floors->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>