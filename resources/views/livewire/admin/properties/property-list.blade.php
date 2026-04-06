<div x-data x-init="$store.pageName = { name: 'Properties', slug: 'properties' }">
    <div class="flex flex-wrap justify-between gap-6 ">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500"
                        href="{{ route('admin.dashboard') }}">
                        Dashboard
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800" x-text="$store.pageName?.name ?? ''"></li>
            </ol>
        </nav>
    </div>

    <div class="flex-1 w-full bg-white rounded-lg min-h-[60vh]">
        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <div class="flex items-center justify-between">
                    <div>
                        @can('property.create')
                            <a href="{{ route('admin.projects.properties.create') }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">
                                <svg class="stroke-current" width="20" height="20" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Create Property
                            </a>
                        @endcan
                    </div>
                    <div class="flex gap-3 w-2/3">
                        <input type="text" wire:model.live.debounce="search" placeholder="Search properties..."
                            class="h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800" />
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 p-5 sm:p-6">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div class="max-w-full overflow-x-auto min-h-[50vh]">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="px-5 py-3">Property</th>
                                    <th class="px-5 py-3">Project</th>
                                    <th class="px-5 py-3">Type / Purpose</th>
                                    <th class="px-5 py-3">Floors</th>
                                    <th class="px-5 py-3">Units</th>
                                    <th class="px-5 py-3">Available</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($properties as $property)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <img src="{{ $property->image ? file_path($property->image) : 'https://ui-avatars.com/api/?name=' . urlencode($property->name) }}"
                                                    class="h-12 w-12 rounded-sm object-cover">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-800">{{ $property->name }}</p>
                                                    @if($property->code)
                                                        <p class="text-xs text-gray-500">Code: {{ $property->code }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">{{ $property->project?->name }}</td>
                                        <td class="px-5 py-4">
                                            <div class="text-sm">{{ $property->property_type ?? '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ $property->purpose ?? '-' }}</div>
                                        </td>
                                        <td class="px-5 py-4">{{ $property->total_floors ?? '-' }}</td>
                                        <td class="px-5 py-4">{{ $property->units->count() }}</td>
                                        <td class="px-5 py-4">{{ $property->units->where('availability_status','available')->count() }}</td>
                                        <td class="px-5 py-4">
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">{{ ucfirst($property->status) }}</span>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <div class="relative inline-block text-left" x-data="{open:false}">
                                                <button @click="open = !open" class="h-9 w-9 rounded-md border bg-white">...
                                                </button>
                                                <div x-show="open" @click.away="open=false" class="absolute right-0 mt-2 w-44 bg-white border rounded-md">
                                                    <a href="{{ route('admin.projects.properties.details', $property) }}" class="block px-3 py-2">View</a>
                                                    <a href="{{ route('admin.projects.properties.create', ['property_id' => $property->id]) }}" class="block px-3 py-2">Edit</a>
                                                    @can('property.delete')
                                                        <button @click="if(confirm('Delete this property?')) { $wire.deleteProperty({{ $property->id }}) }" class="w-full text-left px-3 py-2 text-red-600">Delete</button>
                                                    @endcan
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="px-5 py-8 text-center text-gray-500">No properties found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($properties->hasPages())
                    <div class="mt-6">{{ $properties->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    @if($viewModal && $selectedProperty)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-lg max-w-4xl w-full p-6">
                <h3 class="text-lg font-medium">Property Details: {{ $selectedProperty->name }}</h3>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p><strong>Project:</strong> {{ $selectedProperty->project?->name }}</p>
                        <p><strong>Type:</strong> {{ $selectedProperty->property_type ?? '-' }}</p>
                        <p><strong>Purpose:</strong> {{ $selectedProperty->purpose ?? '-' }}</p>
                    </div>
                    <div>
                        <p><strong>Floors:</strong> {{ $selectedProperty->floors->count() }}</p>
                        <p><strong>Units:</strong> {{ $selectedProperty->units->count() }}</p>
                        <p><strong>Available:</strong> {{ $selectedProperty->units->where('availability_status','available')->count() }}</p>
                    </div>
                </div>
                <div class="mt-4 text-right">
                    <button @click="$wire.closeViewModal()" class="px-4 py-2 rounded bg-gray-100">Close</button>
                </div>
            </div>
        </div>
    @endif
</div>
