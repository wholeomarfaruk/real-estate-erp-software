<div class="w-full">
    <form wire:submit="save" class="space-y-5">
        {{-- Slug --}}
        <div>
            <label for="slug" class="block text-sm font-medium text-gray-700">Category Slug *</label>
            <input
                type="text"
                id="slug"
                wire:model="slug"
                required
                maxlength="80"
                placeholder="e.g., utilities, maintenance"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
                wire:model="name"
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

        {{-- Transaction Category (Optional) --}}
        <div>
            <label for="transaction_category_id" class="block text-sm font-medium text-gray-700">Transaction Category (Optional)</label>
            <select
                id="transaction_category_id"
                wire:model="transaction_category_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
                <option value="">-- Select a transaction category --</option>
                @foreach ($transactionCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('transaction_category_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Form Actions --}}
        <div class="border-t border-gray-200 pt-4 flex items-center justify-end gap-3">
            <button
                type="button"
                wire:click="closeModal"
                class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Cancel
            </button>
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="px-4 py-2 rounded-md bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
            >
                <span wire:loading.remove>Create Category</span>
                <span wire:loading>Creating...</span>
            </button>
        </div>
    </form>
</div>
