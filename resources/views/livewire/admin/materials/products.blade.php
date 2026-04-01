<div x-data x-init="$store.pageName = { name: '"'"'Products & Variants'"'"', slug: '"'"'materials'"'"' }">
    <div class="flex flex-wrap justify-between gap-6 mb-4">
        <h1 class="text-gray-700 text-lg font-bold" x-text="$store.pageName?.name"></h1>
        <input type="text" placeholder="Search products" wire:model.debounce.300ms="search"
            class="w-64 rounded-md border-gray-300 text-sm" />
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <form wire:submit.prevent="saveProduct" class="md:col-span-1 space-y-3 bg-white p-4 rounded-lg shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Create / Edit Product</h2>
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Name</label>
                <input type="text" wire:model.defer="name" class="w-full rounded-md border-gray-300 text-sm">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Category</label>
                <select wire:model.defer="category_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Brand</label>
                <select wire:model.defer="brand_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">No brand</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
                @error('brand_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Unit</label>
                <input type="text" wire:model.defer="unit" class="w-full rounded-md border-gray-300 text-sm" placeholder="pcs, kg, bag...">
                @error('unit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Description</label>
                <textarea wire:model.defer="description" rows="3" class="w-full rounded-md border-gray-300 text-sm"></textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="px-3 py-2 bg-gray-900 text-white text-xs rounded-md">Save Product</button>
                <button type="button" wire:click="resetProductForm" class="px-3 py-2 text-xs border rounded-md">Clear</button>
            </div>
        </form>

        <div class="md:col-span-2 bg-white p-4 rounded-lg shadow-sm space-y-4">
            @if (session()->has('success'))
                <div class="text-green-600 text-sm">{{ session('success') }}</div>
            @endif
            @if (session()->has('error'))
                <div class="text-red-600 text-sm">{{ session('error') }}</div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-left">
                        <tr>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Category</th>
                            <th class="px-3 py-2">Brand</th>
                            <th class="px-3 py-2">Unit</th>
                            <th class="px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($products as $product)
                            <tr x-data="{ open: false }">
                                <td class="px-3 py-2 font-medium text-gray-800">{{ $product->name }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $product->category?->name }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $product->brand?->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $product->unit ?? '-' }}</td>
                                <td class="px-3 py-2 space-x-2">
                                    <button wire:click="editProduct({{ $product->id }})"
                                        class="text-xs px-2 py-1 rounded border">Edit</button>
                                    <button wire:click="deleteProduct({{ $product->id }})"
                                        class="text-xs px-2 py-1 rounded border border-red-300 text-red-600">Delete</button>
                                    <button @click="open = !open" wire:click="startVariant({{ $product->id }})"
                                        class="text-xs px-2 py-1 rounded border border-gray-300">Variants ({{ $product->variants->count() }})</button>
                                </td>
                            </tr>
                            <tr x-show="open" x-cloak>
                                <td colspan="5" class="bg-gray-50 px-3 py-3">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($product->variants as $variant)
                                                <div class="border rounded-md px-3 py-2 bg-white flex items-center gap-2">
                                                    <div>
                                                        <div class="text-gray-800 text-sm font-semibold">{{ $variant->name }}</div>
                                                        <div class="text-xs text-gray-500">{{ $variant->description }}</div>
                                                    </div>
                                                    <div class="space-x-1">
                                                        <button @click="open = true" wire:click="editVariant({{ $variant->id }})"
                                                            class="text-xs px-2 py-1 border rounded">Edit</button>
                                                        <button wire:click="deleteVariant({{ $variant->id }})"
                                                            class="text-xs px-2 py-1 border border-red-300 text-red-600 rounded">Delete</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <form wire:submit.prevent="saveVariant" class="bg-white rounded-md border p-3 space-y-2">
                                            <div class="flex gap-3 items-end flex-wrap">
                                                <div class="flex-1 min-w-[180px] space-y-1">
                                                    <label class="text-xs text-gray-500">Variant Name</label>
                                                    <input type="text" wire:model.defer="variantName" class="w-full rounded-md border-gray-300 text-sm">
                                                    @error('variantName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="w-40 space-y-1">
                                                    <label class="text-xs text-gray-500">Image File ID</label>
                                                    <input type="number" wire:model.defer="variantImageId" class="w-full rounded-md border-gray-300 text-sm">
                                                    @error('variantImageId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs text-gray-500">Description</label>
                                                <textarea wire:model.defer="variantDescription" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                                            </div>
                                            <input type="hidden" wire:model="variantProductId">
                                            <div class="flex gap-2">
                                                <button type="submit" class="px-3 py-2 bg-gray-900 text-white text-xs rounded-md">Save Variant</button>
                                                <button type="button" wire:click="resetVariantForm" class="px-3 py-2 text-xs border rounded-md">Clear</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500">No products yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $products->links() }}</div>
        </div>
    </div>
</div>
