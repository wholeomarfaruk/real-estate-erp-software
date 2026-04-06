<div x-data x-init="$store.pageName = { name: 'Floor Details' }">
    <div class="flex flex-wrap justify-between gap-6 ">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
    </div>

    <div class="mt-4 bg-white rounded-lg p-4">
        <div class="mb-4">
            <h2 class="text-lg font-semibold">{{ $floor->floor_name }}</h2>
            <p class="text-sm text-gray-600">Property: {{ $property->name }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <p class="text-sm text-gray-500">Number</p>
                <p class="text-theme-sm">{{ $floor->floor_number }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Type</p>
                <p class="text-theme-sm">{{ $floor->floor_type }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Status</p>
                <p class="text-theme-sm">{{ ucfirst($floor->status) }}</p>
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('admin.projects.properties.floors.edit', [$property->id, $floor->id]) }}" class="inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Edit</a>
            <a href="{{ route('admin.projects.properties.floors', $property->id) }}" class="ml-3 inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Back to list</a>
        </div>
    </div>
</div>
