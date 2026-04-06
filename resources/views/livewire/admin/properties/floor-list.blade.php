<div x-data x-init="$store.pageName = { name: 'Floors', slug: 'properties.floors' }">
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

    <div class="bg-white rounded-lg p-4">
        <div class="mb-4 flex justify-between">
            <div>
                <a href="{{ route('admin.projects.properties.floors.create', $property->id) }}" class="px-3 py-2 rounded border">Create Floor</a>
            </div>
            <div>
                <input type="text" wire:model.live.debounce="search" placeholder="Search floors..." class="rounded border px-3 py-2" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Number</th>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Units</th>
                        <th class="px-4 py-2">Available</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($floors as $floor)
                        <tr>
                            <td class="px-4 py-3">{{ $floor->floor_name }}</td>
                            <td class="px-4 py-3">{{ $floor->floor_number }}</td>
                            <td class="px-4 py-3">{{ $floor->floor_type }}</td>
                            <td class="px-4 py-3">{{ $floor->units->count() }}</td>
                            <td class="px-4 py-3">{{ $floor->units->where('availability_status','available')->count() }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.projects.properties.floors.view', [$property->id, $floor->id]) }}" class="text-blue-600 mr-3">View</a>
                                <a href="{{ route('admin.projects.properties.floors.edit', [$property->id, $floor->id]) }}" class="text-indigo-600 mr-3">Edit</a>
                                <button @click="$wire.deleteFloor({{ $floor->id }})" class="text-red-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No floors found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($floors->hasPages())
            <div class="mt-4">{{ $floors->links() }}</div>
        @endif
    </div>
</div>
