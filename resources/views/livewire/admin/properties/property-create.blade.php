<div x-data x-init="$store.pageName = { name: 'Create Property', slug: 'properties.create' }">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li><a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500">Dashboard</a></li>
                <li class="text-sm text-gray-800">Create Property</li>
            </ol>
        </nav>
    </div>

    <div class="bg-white rounded-lg p-6">
        {{-- Auto-populate notification --}}
        @if($selectedProject)
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <strong>✓ Project Selected:</strong> Data from <strong>{{ $selectedProject->name }}</strong> has been auto-filled. You can edit these fields if needed.
                </p>
            </div>
        @endif

        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600">Project <span class="text-red-500">*</span></label>
                    <select wire:model.live="project_id" class="w-full rounded border px-3 py-2">
                        <option value="">-- Select Project --</option>
                        @foreach($projects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-600">Name</label>
                    <input type="text" wire:model.defer="name" class="w-full rounded border px-3 py-2" />
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-600">Code (optional)</label>
                    <input type="text" wire:model.defer="code" class="w-full rounded border px-3 py-2" />
                    @error('code') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

                </div>

                <div>
                    <label class="block text-sm text-gray-600">
                        Type
                        @if($selectedProject && $property_type) <span class="text-green-600 text-xs">(auto-filled)</span> @endif
                    </label>
                    <select wire:model="property_type" class="w-full rounded border px-3 py-2 {{ $selectedProject && $property_type ? 'bg-blue-50' : '' }}">
                        <option value="">-- Select --</option>
                        @foreach (\App\Enums\Property\Type::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>

                        @endforeach
                    </select>
                    @error('property_type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

                </div>

                <div>
                    <label class="block text-sm text-gray-600">Purpose</label>
                    <select wire:model="purpose" class="w-full rounded border px-3 py-2">
                        <option value="">-- Select --</option>
                        <option value="sell">Sell</option>
                        <option value="rent">Rent</option>
                        <option value="sell_rent">Sell / Rent</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600">Total Floors</label>
                    <input type="number" wire:model.defer="total_floors" class="w-full rounded border px-3 py-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600">
                        Address
                        @if($selectedProject && $address) <span class="text-green-600 text-xs">(auto-filled from project location)</span> @endif
                    </label>
                    <textarea wire:model.defer="address" class="w-full rounded border px-3 py-2 {{ $selectedProject && $address ? 'bg-blue-50' : '' }}" rows="3"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-600">
                        Description / Notes
                        @if($selectedProject && $description) <span class="text-green-600 text-xs">(auto-filled from project)</span> @endif
                    </label>
                    <textarea wire:model.defer="description" class="w-full rounded border px-3 py-2 {{ $selectedProject && $description ? 'bg-blue-50' : '' }}" rows="4"></textarea>
                </div>
            </div>

            <div class="mt-4 text-right">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Save</button>
                <a href="{{ route('admin.projects.properties') }}" class="ml-2 px-4 py-2 rounded border">Cancel</a>
            </div>
        </form>
    </div>
</div>
