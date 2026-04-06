<div x-data x-init="$store.pageName = { name: 'Units', slug: 'properties.units' }">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
    </div>

    <div class="bg-white rounded-lg p-4">
        <div class="mb-4 flex justify-between">
            <div>
                <a href="{{ route('admin.projects.properties.units.create', $property->id) }}" class="px-3 py-2 rounded border">Create Unit</a>
            </div>
            <div>
                <input type="text" wire:model.live.debounce="search" placeholder="Search units..." class="rounded border px-3 py-2" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-2">Unit</th>
                        <th class="px-4 py-2">Floor</th>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Purpose</th>
                        <th class="px-4 py-2">Size (sqft)</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                        <tr>
                            <td class="px-4 py-3">{{ $unit->unit_number }} {{ $unit->unit_name ? '- '.$unit->unit_name : '' }}</td>
                            <td class="px-4 py-3">{{ $unit->floor?->floor_name }}</td>
                            <td class="px-4 py-3">{{ $unit->unit_type }}</td>
                            <td class="px-4 py-3">{{ $unit->purpose }}</td>
                            <td class="px-4 py-3">{{ $unit->size_sqft }}</td>
                            <td class="px-4 py-3">{{ ucfirst($unit->availability_status) }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.projects.properties.units.view', [$property->id, $unit->id]) }}" class="text-blue-600 mr-3">View</a>
                                <a href="{{ route('admin.projects.properties.units.edit', [$property->id, $unit->id]) }}" class="text-indigo-600 mr-3">Edit</a>
                                <button @click="$wire.deleteUnit({{ $unit->id }})" class="text-red-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No units found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($units->hasPages())
            <div class="mt-4">{{ $units->links() }}</div>
        @endif
    </div>
</div>
