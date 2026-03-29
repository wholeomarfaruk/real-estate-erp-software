{{-- ======================== Page Layout Start From Here ======================== --}}
<div x-data x-init="$store.pageName = { name: 'Project Calendar', slug: 'project-calendar' }">
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
                    <div>
                        <!-- Project Selector -->
                        <select wire:model.live="selectedProject"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">Select a Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                @if($selectedProject)
                    @php
                        $project = \App\Models\Project::with(['timelinePhases.tasks', 'planning', 'estimates.items'])->find($selectedProject);
                    @endphp

                    @if($project)
                        <!-- Project Overview -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Project Overview</h3>
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Duration</p>
                                        <p class="font-medium">{{ $project->start_date->format('M d, Y') }} - {{ $project->end_date->format('M d, Y') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                                        <span class="inline-block px-2 py-1 text-xs font-medium rounded-full
                                            @if($project->status === 'completed') bg-green-100 text-green-800
                                            @elseif($project->status === 'ongoing') bg-blue-100 text-blue-800
                                            @elseif($project->status === 'on_hold') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Budget</p>
                                        <p class="font-medium">{{ $project->budget ? '$' . number_format($project->budget, 2) : 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline Phases -->
                        @if($project->timelinePhases->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Timeline Phases</h3>
                            <div class="space-y-4">
                                @foreach($project->timelinePhases as $phase)
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border-l-4 border-blue-500">
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-medium text-blue-900 dark:text-blue-100">{{ $phase->name }}</h4>
                                            <span class="text-sm text-blue-700 dark:text-blue-300">{{ $phase->progress_percentage }}% Complete</span>
                                        </div>
                                        <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                                            {{ $phase->start_date->format('M d, Y') }} - {{ $phase->end_date->format('M d, Y') }}
                                        </p>

                                        <!-- Progress Bar -->
                                        <div class="w-full bg-blue-200 dark:bg-blue-800 rounded-full h-2 mb-3">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $phase->progress_percentage }}%"></div>
                                        </div>

                                        <!-- Tasks -->
                                        @if($phase->tasks->count() > 0)
                                        <div class="space-y-2">
                                            <h5 class="text-sm font-medium text-blue-800 dark:text-blue-200">Tasks:</h5>
                                            @foreach($phase->tasks as $task)
                                                <div class="flex justify-between items-center bg-white dark:bg-gray-800 p-2 rounded text-sm">
                                                    <span>{{ $task->name }}</span>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs
                                                            @if($task->status === 'completed') text-green-600
                                                            @elseif($task->status === 'in_progress') text-blue-600
                                                            @else text-gray-600 @endif">
                                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                                        </span>
                                                        <span class="text-xs text-gray-500">{{ $task->progress_percentage }}%</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @else
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>No timeline phases found for this project.</p>
                            <p class="text-sm mt-2">Create project planning and timeline to see the calendar view.</p>
                        </div>
                        @endif

                        <!-- Project Planning -->
                        @if($project->planning->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Project Planning</h3>
                            @foreach($project->planning as $planning)
                                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                                    <p><strong>Duration:</strong> {{ $planning->start_date->format('M d, Y') }} - {{ $planning->end_date->format('M d, Y') }}</p>
                                    @if($planning->duration)
                                        <p><strong>Days:</strong> {{ $planning->duration }}</p>
                                    @endif
                                    @if($planning->notes)
                                        <p><strong>Notes:</strong> {{ $planning->notes }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @endif

                        <!-- Estimates -->
                        @if($project->estimates->count() > 0)
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Project Estimates</h3>
                            @foreach($project->estimates as $estimate)
                                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                                    <h4 class="font-medium mb-2">{{ $estimate->title }}</h4>
                                    <p class="text-sm text-purple-700 dark:text-purple-300 mb-3">Total Cost: ${{ number_format($estimate->total_cost, 2) }}</p>

                                    @if($estimate->items->count() > 0)
                                    <div class="space-y-1">
                                        @foreach($estimate->items as $item)
                                            <div class="flex justify-between text-sm bg-white dark:bg-gray-800 p-2 rounded">
                                                <span>{{ $item->name }} ({{ ucfirst($item->type) }})</span>
                                                <span>${{ number_format($item->total_cost, 2) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @endif
                    @else
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Project not found.
                        </div>
                    @endif
                @else
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Select a Project</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose a project from the dropdown to view its timeline and planning details.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>