{{-- ======================== Page Layout Start From Here ======================== --}}
<div x-data x-init="$store.pageName = { name: 'Project Details' }">
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
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white/90">{{ $project->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Code: {{ $project->code ?? 'N/A' }}</p>
                    </div>
                    <div class="flex gap-3">
                        <span
                            class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium {{ $project->status?->badge() }}">
                            {{ $project->status?->label() }}
                        </span>
                        @if ($project->project_type)
                            <span
                                class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ $project->project_type?->label() }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white/90 mb-4">Project Overview
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Location</p>
                                    <p class="font-medium">{{ $project->location }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Budget</p>
                                    <p class="font-medium">
                                        {{ $project->budget ? '$' . number_format($project->budget, 2) : 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Start Date</p>
                                    <p class="font-medium">{{ optional($project->start_date)->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">End Date</p>
                                    <p class="font-medium">{{ optional($project->end_date)->format('M d, Y') }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <p class="text-gray-500 dark:text-gray-400">Description</p>
                                    <p class="font-medium">{{ $project->description ?? 'No description provided.' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white/90">Documents</h3>
                                @if ($canEdit)
                                    <button wire:click="saveDocuments"
                                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                        Save Documents
                                    </button>
                                @endif
                            </div>

                            @if ($canEdit)
                                <x-media-picker-field field="documents" :value="$documents"
                                    placeholder="Click to upload files" :multiple="true" type="all"
                                    label="Manage Documents" required="false" />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Add or remove attachments;
                                    changes save automatically when you pick or remove files, or click "Save Documents"
                                    to confirm.</p>
                            @endif

                            <div class="mt-4 space-y-3">
                                @forelse ($documentFiles as $file)
                                    <div
                                        class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-800">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-10 w-10 rounded-lg bg-indigo-50 text-indigo-600 grid place-content-center text-sm font-semibold dark:bg-indigo-900/40">
                                                {{ strtoupper(substr($file->extension ?? 'file', 0, 3)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white/90">
                                                    {{ $file->name ?? 'Document ' . $loop->iteration }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ strtoupper($file->extension ?? '') }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ file_path($file->id) }}" data-fancybox="project-docs"
                                                {{ $file->extension == 'pdf' ? 'data-type=iframe data-autosize=true' : '' }}
                                                data-caption="{{ $file->name ?? 'Document ' . $loop->iteration }}"
                                                class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-50 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                                View
                                            </a>
                                            <a href="{{ file_path($file->id) }}" download
                                                class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-50 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 4.5v9m0 0 3.5-3.5M12 13.5 8.5 10M4.5 19.5h15" />
                                                </svg>
                                                Download
                                            </a>
                                            @if ($canEdit)
                                                <button type="button"
                                                    wire:click="removeMedia('documents', '{{ $file->id }}')"
                                                    class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-sm font-medium text-red-600 ring-1 ring-red-200 transition hover:bg-red-50 dark:ring-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No documents uploaded for this
                                        project yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white/90 mb-4">At a Glance</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Floors</span>
                                    <span class="text-lg font-semibold text-indigo-600">{{ $stats['floors'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Units</span>
                                    <span class="text-lg font-semibold text-indigo-600">{{ $stats['units'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Available Units</span>
                                    <span
                                        class="text-lg font-semibold text-green-600">{{ $stats['available_units'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Sold Units</span>
                                    <span
                                        class="text-lg font-semibold text-orange-600">{{ $stats['sold_units'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Listen for the custom 'toast' event to display toast notifications
            Fancybox.bind("[data-fancybox='project-docs']", {
                Toolbar: true,
                closeButton: "top",
                Html: {
                    iframe: {
                        preload: false,
                        css: {
                            width: "90%",
                            height: "90%",
                        }
                    }
                }
            });
        });
    </script>
@endpush
