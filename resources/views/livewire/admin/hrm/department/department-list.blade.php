<div x-data x-init="$store.pageName = { name: 'Departments', slug: 'hrm-departments' }">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-gray-700">Departments</h1>
            <p class="text-sm text-gray-500">Manage HRM departments for employee assignment.</p>
        </div>

        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li>/</li>
                <li class="text-gray-700">Departments</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div class="md:col-span-4">
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search by department name or code"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>
                <div>
                    @can('hrm.departments.create')
                        <button type="button" wire:click="openCreateModal" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">
                            Create
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Name</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Code</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Designations</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Employees</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($departments as $department)
                                <tr>
                                    <td class="px-5 py-4 text-sm font-medium text-gray-800">{{ $department->name }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-700">{{ $department->code ?: 'N/A' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $department->status ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $department->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ $department->designations_count }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-gray-700">{{ $department->employees_count }}</td>
                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900">
                                                <span class="sr-only">Actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" style="display: none;" class="absolute right-0 z-40 mt-10 w-44 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('hrm.departments.update')
                                                    <button type="button" wire:click="openEditModal({{ $department->id }})" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Edit
                                                    </button>
                                                @endcan
                                                @can('hrm.departments.delete')
                                                    <button
                                                        type="button"
                                                        x-data="livewireConfirm"
                                                        @click="confirmAction({
                                                            id: {{ $department->id }},
                                                            method: 'deleteDepartment',
                                                            title: 'Delete department?',
                                                            text: 'This action cannot be undone.',
                                                            confirmText: 'Yes, delete'
                                                        })"
                                                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50"
                                                    >
                                                        Delete
                                                    </button>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-700">No departments found.</p>
                                        <p class="mt-1 text-xs text-gray-500">Try changing search or create a department.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($departments->hasPages())
                <div class="mt-6">
                    {{ $departments->links() }}
                </div>
            @endif
        </div>
    </div>

    <div x-cloak x-data="{ open: @entangle('showFormModal') }" x-show="open" x-transition class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4">
        <div class="w-full max-w-xl rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 class="text-xl font-bold text-gray-900">{{ $editingId ? 'Edit Department' : 'Create Department' }}</h2>
                <button type="button" @click="open = false; $wire.closeFormModal()" class="-me-4 -mt-4 rounded-full p-2 text-gray-400 transition hover:bg-gray-50 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="mt-4 space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700">Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model.defer="name" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Code</label>
                    <input type="text" wire:model.defer="code" class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model.defer="status" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Active Department
                </label>

                <div class="mt-2 flex justify-end gap-2">
                    <button type="button" @click="open = false; $wire.closeFormModal()" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                        {{ $editingId ? 'Update' : 'Save' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

