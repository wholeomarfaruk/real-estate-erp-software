<div x-data x-init="$store.pageName = { name: 'Property Details', slug: 'properties.details' }">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li><a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500">Dashboard</a></li>
                <li class="text-sm text-gray-800">{{ $property->name }}</li>
            </ol>
        </nav>
    </div>

    <div class="bg-white rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <h2 class="text-lg font-semibold">{{ $property->name }}</h2>
                <p class="text-sm text-gray-600">{{ $property->address }}</p>
                <div class="mt-4">
                    <h4 class="font-medium">Description</h4>
                    <p class="text-sm text-gray-700">{{ $property->description ?? 'N/A' }}</p>
                </div>
                <div class="mt-4">
                    <h4 class="font-medium">Floors</h4>
                    <ul class="mt-2 space-y-2">
                        @foreach($property->floors as $floor)
                            <li class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium">{{ $floor->floor_name }}</div>
                                    <div class="text-xs text-gray-500">Units: {{ $floor->units->count() }}</div>
                                </div>
                                <div class="text-sm">Available: {{ $floor->units->where('availability_status','available')->count() }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div>
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['floors'] }}</div>
                    <div class="text-sm text-gray-600">Floors</div>
                </div>
                <div class="mt-3 bg-gray-50 p-4 rounded">
                    <div class="text-2xl font-bold text-green-600">{{ $stats['units'] }}</div>
                    <div class="text-sm text-gray-600">Units</div>
                </div>
                <div class="mt-3 bg-gray-50 p-4 rounded">
                    <div class="text-2xl font-bold text-purple-600">{{ $stats['available_units'] }}</div>
                    <div class="text-sm text-gray-600">Available</div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex gap-2">
            @can('property.edit')
                <a href="{{ route('admin.projects.properties.create', ['property_id' => $property->id]) }}" class="px-4 py-2 rounded border">Edit Property</a>
            @endcan
            <a href="{{ route('admin.projects.properties.floors', $property) }}" class="px-4 py-2 rounded border">Manage Floors</a>
            <a href="{{ route('admin.projects.properties.units', $property) }}" class="px-4 py-2 rounded border">Manage Units</a>
            <a href="{{ route('admin.projects.properties.overview', $property) }}" class="px-4 py-2 rounded border">Overview</a>
        </div>
    </div>
</div>
