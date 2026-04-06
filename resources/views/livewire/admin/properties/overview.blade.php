<div x-data x-init="$store.pageName = { name: 'Building Overview', slug: 'properties.overview' }">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
    </div>

    <div class="bg-white rounded-lg p-6">
        <h2 class="text-lg font-semibold">Property: {{ $property->name }}</h2>
        <div class="mt-4 space-y-6">
            @foreach($property->floors->sortBy('floor_number') as $floor)
                <div class="border rounded p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium">{{ $floor->floor_name }}</h3>
                            <div class="text-sm text-gray-500">Total: {{ $floor->units->count() }} | Available: {{ $floor->units->where('availability_status','available')->count() }}</div>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                        @foreach($floor->units as $unit)
                            <div class="p-3 border rounded">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium">{{ $unit->unit_number }} {{ $unit->unit_name ? '- '.$unit->unit_name : '' }}</div>
                                        <div class="text-xs text-gray-500">{{ $unit->unit_type }}</div>
                                    </div>
                                    <div>
                                        <span class="inline-block px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">{{ ucfirst($unit->availability_status) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
