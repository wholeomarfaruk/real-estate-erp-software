<div class="w-full">
    <form wire:submit="save" class="space-y-5">
        {{-- Slug --}}
        <div>
            <div class="flex items-center justify-between">
                <label for="slug" class="block text-sm font-medium text-gray-700">Category Slug *</label>
                @if(! $slugManuallyEdited && $slug)
                    <span class="text-xs text-indigo-500 font-medium">✦ auto-generated</span>
                @endif
            </div>
            <input
                type="text"
                id="slug"
                wire:model.blur="slug"
                required
                maxlength="80"
                placeholder="e.g., utilities, maintenance"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-sm"
            />
            @error('slug')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Category Name *</label>
            <input
                type="text"
                id="name"
                wire:model.blur="name"
                required
                maxlength="120"
                placeholder="e.g., Utilities & Maintenance"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
            <textarea
                id="description"
                wire:model="description"
                maxlength="500"
                rows="3"
                placeholder="Brief description of this expense category"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            ></textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            {{-- Icon --}}
            <div>
                <label for="icon" class="block text-sm font-medium text-gray-700">Icon *</label>
                <select
                    id="icon"
                    wire:model="icon"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="">Select an icon</option>
                    @foreach ($iconOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('icon')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Color --}}
            <div>
                <label for="color" class="block text-sm font-medium text-gray-700">Color *</label>
                <select
                    id="color"
                    wire:model="color"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="">Select a color</option>
                    @foreach ($colorOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('color')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Feature Account (Optional) --}}
        <div>
            <label for="feature_type" class="block text-sm font-medium text-gray-700">Feature Account (Optional)</label>
            <select
                id="feature_type"
                wire:model="feature_type"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
                <option value="">-- Select a feature account --</option>
                @foreach ($featureAccounts as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('feature_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Form Actions --}}
        <div class="border-t border-gray-200 pt-4 flex items-center justify-end gap-3">
            <button
                type="button"
                @click="$dispatch('close-expense-category-modal')"
                class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Cancel
            </button>
            <button
                type="submit"
                wire:loading.attr="disabled" wire:target="save"
                class="px-4 py-2 rounded-md bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="save">{{ $categoryId ? 'Update Category' : 'Create Category' }}</span>
                <span wire:loading wire:target="save">{{ $categoryId ? 'Updating...' : 'Creating...' }}</span>
            </button>
        </div>
    </form>
</div>
