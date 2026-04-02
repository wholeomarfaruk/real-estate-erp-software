<div x-data x-init="$store.pageName = { name: 'Manage Products' }">
    {{-- ======================== Page Header Start From Here ======================== --}}
    <div class="flex flex-wrap justify-between gap-6 ">
        {{-- Page Name  --}}
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''">
        </h1>
        {{-- Breadcrumb  --}}
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="{{ route('admin.dashboard') }}">
                        Dashboard
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>

                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90" x-text="$store.pageName?.name ?? ''"></li>
            </ol>
        </nav>
    </div>
    {{-- ======================== Page Header End Here ======================== --}}

    <div class="grid grid-cols-2 gap-4 px-4 py-4  bg-white rounded-lg shadow-sm  mb-2">

        <div>
            <div class="flex gap-4 sm:gap-6 justify-end items-end mt-2">

                <div class="group">

                </div>
            </div>
        </div>
        <div>
            <label for="Search">
                {{-- <span class="text-sm font-medium text-gray-700"> Search </span> --}}

                <div class="relative">
                    <input type="text" wire:model.live.debounce="search" id="Search" placeholder="Search by Name"
                        class="mt-0.5 w-full rounded border-gray-300 px-2 py-2 shadow-sm sm:text-sm">

                    <span class="absolute inset-y-0 right-2 grid w-8 place-content-center">
                        <button type="button" aria-label="Submit"
                            class="rounded-full p-1.5 text-gray-700 transition-colors hover:bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z">
                                </path>
                            </svg>
                        </button>
                    </span>
                </div>
            </label>
        </div>
    </div>
    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <form wire:submit.prevent="saveProduct" class="md:col-span-1 space-y-3 bg-white p-4 rounded-lg shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">Create / Edit Product</h2>
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Name</label>
                <input type="text" wire:model.defer="name" class="w-full rounded-md border-gray-300 text-sm">
                @error('name')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1">
                <div class="flex justify-between gap-2">

                    <label class="text-xs text-gray-500">Category</label>
                    <button type="button" wire:click="addCategoryModalOpen = true"
                        class="text-xs  text-gray-500 px-2 py-1 border border-gray-300 rounded flex items-center gap-1 hover:text-gray-800 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>

                        Add</button>
                </div>
                <select wire:model.defer="category_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Select category</option>
                    @foreach ($categories as $parent)
                        @if ($parent->children->count())
                            <optgroup label="{{ $parent->name }}">
                                @foreach ($parent->children as $child)
                                    <option value="{{ $child->id }}">
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @else
                            <option value="{{ $parent->id }}">
                                {{ $parent->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
                @error('category_id')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1">
                <div class="flex justify-between gap-2">

                    <label class="text-xs text-gray-500">Brand</label>
                    <button type="button" wire:click="addBrandModalOpen = true"
                        class="text-xs  text-gray-500 px-2 py-1 border border-gray-300 rounded flex items-center gap-1 hover:text-gray-800 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>

                        Add</button>
                </div>
                <select wire:model.defer="brand_id" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">No brand</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
                @error('brand_id')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1">
                <div class="flex justify-between gap-2">

                    <label class="text-xs text-gray-500">Unit</label>
                    <button type="button" wire:click="addUnitModalOpen = true"
                        class="text-xs  text-gray-500 px-2 py-1 border border-gray-300 rounded flex items-center gap-1 hover:text-gray-800 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>

                        Add</button>
                </div>
                <select wire:model.defer="unit" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">Select Unit</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->name }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                @error('unit')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Description</label>
                <textarea wire:model.defer="description" rows="3" class="w-full rounded-md border-gray-300 text-sm"></textarea>
            </div>
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Image</label>
                <x-media-picker-field field="image_id" :value="$image_id" placeholder="Click to Upload Image"
                    :multiple="false" type="image" label="" :required="false" />
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="px-3 py-2 bg-gray-900 text-white text-xs rounded-md">Save
                    Product</button>
                <button type="button" wire:click="resetProductForm"
                    class="px-3 py-2 text-xs border rounded-md">Clear</button>
            </div>
        </form>

        {{-- ======================== Page content Start From Here ======================== --}}
        <div class="md:col-span-2 bg-white p-4 rounded-lg shadow-sm space-y-4">
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
                                <td class="px-3 py-2 font-medium text-gray-800">

                                    <div class="flex items-center gap-3">
                                        <a class="block"
                                            href="{{ $product->image_id ? file_path($product->image_id) : 'https://ui-avatars.com/api/?name=' . urlencode($product->name) . '&background=111827&color=fff&rounded=falese&bold=true' }}"
                                            data-fancybox
                                            data-caption="{{ $product->name }} <br> {{ $product->description }}">
                                            <img src="{{ $product->image_id ? file_path($product->image_id) : 'https://ui-avatars.com/api/?name=' . urlencode($product->name) . '&background=111827&color=fff&rounded=falese&bold=true' }}"
                                                class="h-10 w-10 rounded-sm object-cover">
                                        </a>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                                {{ $product->name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-gray-600">{{ $product->category?->name }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $product->brand?->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $product->unit ?? '-' }}</td>
                                <td class="px-3 py-2 space-x-2">
                                    <button wire:click="editProduct({{ $product->id }})"
                                        class="text-xs px-2 py-1 rounded border cursor-pointer hover:bg-gray-100">Edit</button>
                                    <button wire:click="deleteProduct({{ $product->id }})"
                                        class="text-xs px-2 py-1 rounded border border-red-300 text-red-600 cursor-pointer hover:bg-gray-100">Delete</button>
                                    <button wire:click="startVariant({{ $product->id }})"
                                        class="text-xs px-2 py-1 rounded border border-gray-300 cursor-pointer hover:bg-gray-100">Variants
                                        ({{ $product->variants->count() }})
                                    </button>
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
    {{-- ===============================Modals Start=================================== --}}
    {{-- add category  --}}
    <div x-cloak x-data="{ addCategoryModalOpen: @entangle('addCategoryModalOpen') }" x-show="addCategoryModalOpen" x-transition
        class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true"
        aria-labelledby="modalTitle">
        <div class="w-full md:w-md  rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-900 sm:text-2xl">Add Category</h2>

                <button wire:click="addCategoryModalOpen=false" type="button"
                    class="cursor-pointer -me-4 -mt-4 rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600 focus:outline-none"
                    aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-4">

                <form action="#" class="space-y-4" wire:submit.prevent="saveCategory">
                    <div class="grid grid-cols-1 gap-1">
                        <label class="block text-sm font-medium text-gray-900" for="name">Name <span
                                class="text-red-400">*</span></label>
                        <input wire:model="categoryName"
                            class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:outline-none p-2"
                            id="name" type="text" placeholder="Enter Category Name" />
                        @error('categoryName')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-1">
                        <label class="block text-sm font-medium text-gray-900" for="parent_category">Parent
                            category</label>
                        <select wire:model="categoryParentId"
                            class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:outline-none p-2"
                            id="parent_category">

                            <option value="">No Parent</option>

                            @foreach ($categories as $parent)
                                @if ($parent->children->count())
                                    <optgroup label="{{ $parent->name }}">
                                        @foreach ($parent->children as $child)
                                            <option value="{{ $child->id }}">
                                                {{ $child->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @else
                                    <option value="{{ $parent->id }}">
                                        {{ $parent->name }}
                                    </option>
                                @endif
                            @endforeach

                        </select>
                        @error('categoryParentId')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>


                    <button type="submit"
                        class="block w-full rounded-lg border border-indigo-600 bg-white px-12 py-3 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-500 hover:text-white cursor-pointer">
                        Submit
                    </button>
                </form>

            </div>
        </div>
    </div>
    {{-- add brands  --}}
    <div x-cloak x-data="{ addBrandModalOpen: @entangle('addBrandModalOpen') }" x-show="addBrandModalOpen" x-transition
        class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true"
        aria-labelledby="modalTitle">
        <div class="w-full md:w-md  rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-900 sm:text-2xl">Add Brand</h2>

                <button wire:click="addBrandModalOpen=false" type="button"
                    class="cursor-pointer -me-4 -mt-4 rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600 focus:outline-none"
                    aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-4">

                <form action="#" class="space-y-4" wire:submit.prevent="saveBrand">
                    <div class="grid grid-cols-1 gap-1">
                        <label class="block text-sm font-medium text-gray-900" for="brand_name">Brand Name <span
                                class="text-red-400">*</span></label>
                        <input wire:model="brandName"
                            class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:outline-none p-2"
                            id="brand_name" type="text" placeholder="Enter Brand Name" />
                        @error('brandName')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-1">
                        <label class="block text-sm font-medium text-gray-900" for="brand_description">Brand
                            Description</label>
                        <textarea wire:model="brandDescription"
                            class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:outline-none p-2"
                            id="brand_description" placeholder="Enter Brand Description"></textarea>
                        @error('brandDescription')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-1">
                        <label class="block text-sm font-medium text-gray-900" for="brand_image">Brand Image</label>
                        <x-media-picker-field field="brandImageId" :value="$brandImageId"
                            placeholder="Click to Upload Image" :multiple="false" type="image" label=""
                            :required="false" />
                        @error('brandImageId')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>


                    <button type="submit"
                        class="block w-full rounded-lg border border-indigo-600 bg-white px-12 py-3 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-500 hover:text-white cursor-pointer">
                        Submit
                    </button>
                </form>

            </div>
        </div>
    </div>
    {{-- add units  --}}
    <div x-cloak x-data="{ addUnitModalOpen: @entangle('addUnitModalOpen') }" x-show="addUnitModalOpen" x-transition
        class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true"
        aria-labelledby="modalTitle">
        <div class="w-full md:w-md  rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-900 sm:text-2xl">Add Unit</h2>

                <button wire:click="addUnitModalOpen=false" type="button"
                    class="cursor-pointer -me-4 -mt-4 rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600 focus:outline-none"
                    aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-4">

                <form action="#" class="space-y-4" wire:submit.prevent="saveUnit">
                    <div class="grid grid-cols-1 gap-1">
                        <label class="block text-sm font-medium text-gray-900" for="unit_name">Unit Name <span
                                class="text-red-400">*</span></label>
                        <input wire:model="unitName"
                            class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:outline-none p-2"
                            id="unit_name" type="text" placeholder="Enter Unit Name" />
                        @error('unitName')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit"
                        class="block w-full rounded-lg border border-indigo-600 bg-white px-12 py-3 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-500 hover:text-white cursor-pointer">
                        Submit
                    </button>
                </form>

            </div>
        </div>
    </div>
    {{-- product varients  --}}
    <div x-cloak x-data="{ productVarientModalOpen: @entangle('productVarientModalOpen') }" x-show="productVarientModalOpen" x-transition
        class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true"
        aria-labelledby="modalTitle">
        <div class="w-full md:w-md  rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-900 sm:text-2xl">Product Variant</h2>

                <button wire:click="productVarientModalOpen=false" type="button"
                    class="cursor-pointer -me-4 -mt-4 rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600 focus:outline-none"
                    aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-4">
                @if ($selectedVariantProduct)
                <div class="flex justify-between item-center gap-2 mb-2">
                    
                    <div class="flex items-center gap-3">
                        <a class="block"
                            href="{{ $selectedVariantProduct->image_id ? file_path($selectedVariantProduct->image_id) : 'https://ui-avatars.com/api/?name=' . urlencode($selectedVariantProduct->name) . '&background=111827&color=fff&rounded=falese&bold=true' }}"
                            data-fancybox
                            data-caption="{{ $selectedVariantProduct->name }} <br> {{ $selectedVariantProduct->description }}">
                            <img src="{{ $selectedVariantProduct->image_id ? file_path($selectedVariantProduct->image_id) : 'https://ui-avatars.com/api/?name=' . urlencode($selectedVariantProduct->name) . '&background=111827&color=fff&rounded=falese&bold=true' }}"
                                class="h-10 w-10 rounded-sm object-cover">
                        </a>
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $selectedVariantProduct->name }}
                            </p>
                        </div>
                    </div>
<button type="button" wire:click="addVariantModalOpen = true"
                        class="text-xs  text-gray-500 px-2 py-1 border border-gray-300 rounded flex items-center gap-1 hover:text-gray-800 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>

                        Add Variant</button>                    </div>
                @endif
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-left">
                        <tr>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Description</th>
                            <th class="px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($productVariants as $product_variant)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-800">

                                    <div class="flex items-center gap-3">
                                        <a class="block"
                                            href="{{ $product_variant->image_id ? file_path($product_variant->image_id) : 'https://ui-avatars.com/api/?name=' . urlencode($product_variant->name) . '&background=111827&color=fff&rounded=falese&bold=true' }}"
                                            data-fancybox
                                            data-caption="{{ $product_variant->name }} <br> {{ $product_variant->description }}">
                                            <img src="{{ $product_variant->image_id ? file_path($product_variant->image_id) : 'https://ui-avatars.com/api/?name=' . urlencode($product_variant->name) . '&background=111827&color=fff&rounded=falese&bold=true' }}"
                                                class="h-10 w-10 rounded-sm object-cover">
                                        </a>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                                {{ $product_variant->name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-2 text-gray-600">{{ $product_variant->description ?? '-' }}</td>
                                <td class="px-3 py-2 space-x-2 flex gap-1">
                                    <button wire:click="editProduct({{ $product_variant->id }})"
                                        class="text-xs px-2 py-1 rounded border">Edit</button>
                                    <button wire:click="deleteProduct({{ $product_variant->id }})"
                                        class="text-xs px-2 py-1 rounded border border-red-300 text-red-600">Delete</button>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500">No variants yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>
        {{-- add variant  --}}
    <div x-cloak x-data="{ addVariantModalOpen: @entangle('addVariantModalOpen') }" x-show="addVariantModalOpen" x-transition
        class="fixed inset-0 z-50 grid place-content-center bg-black/50 p-4" role="dialog" aria-modal="true"
        aria-labelledby="modalTitle">
        <div class="w-full md:w-md  rounded-lg bg-white p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-900 sm:text-2xl">Add Variant</h2>

                <button wire:click="addVariantModalOpen=false" type="button"
                    class="cursor-pointer -me-4 -mt-4 rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600 focus:outline-none"
                    aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-4">

                <form action="#" class="space-y-4" wire:submit.prevent="saveVariant">
                    <div class="grid grid-cols-1 gap-1">
                        <label class="block text-sm font-medium text-gray-900" for="variant_name">Variant Name <span
                                class="text-red-400">*</span></label>
                        <input wire:model="variantName"
                            class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:outline-none p-2"
                            id="variant_name" type="text" placeholder="Enter Variant Name" />
                        @error('variantName')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-1">
                        <label class="block text-sm font-medium text-gray-900" for="variant_description">Variant
                            Description</label>
                        <textarea wire:model="variantDescription"
                            class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:outline-none p-2"
                            id="variant_description" placeholder="Enter Variant Description"></textarea>
                        @error('variantDescription')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-1">
                        <label class="block text-sm font-medium text-gray-900" for="variant_image">Variant Image</label>
                        <x-media-picker-field field="variantImageId" :value="$variantImageId"
                            placeholder="Click to Upload Image" :multiple="false" type="image" label=""
                            :required="false" />
                        @error('variantImageId')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>


                    <button type="submit"
                        class="block w-full rounded-lg border border-indigo-600 bg-white px-12 py-3 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-500 hover:text-white cursor-pointer">
                        Submit
                    </button>
                </form>

            </div>
        </div>
    </div>
    {{-- ===============================Modals END=================================== --}}
</div>
