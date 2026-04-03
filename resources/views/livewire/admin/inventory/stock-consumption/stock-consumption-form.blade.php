<div x-data x-init="$store.pageName = { name: '{{ $editMode ? 'Edit Stock Consumption' : 'Create Stock Consumption' }}', slug: 'stock-consumptions' }">
    <div class="flex flex-wrap justify-between gap-6">
        <h1 class="text-gray-500 text-lg font-bold" x-cloak x-text="$store.pageName?.name ?? ''"></h1>

        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.dashboard') }}">
                        Dashboard
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500" href="{{ route('admin.inventory.stock-consumptions.index') }}">
                        Stock Consumption
                        <svg class="stroke-current" width="17" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800" x-cloak x-text="$store.pageName?.name ?? ''"></li>
            </ol>
        </nav>
    </div>

    <form wire:submit.prevent="saveDraft" class="mt-4 space-y-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <h2 class="text-base font-semibold text-gray-800">Consumption Details</h2>
            <p class="mt-1 text-xs text-gray-500">Project store manager can only create and post consumption from assigned store.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="consumption_no" class="text-xs font-medium text-gray-600">Consumption No *</label>
                    <input id="consumption_no" type="text" wire:model="consumption_no" class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <x-input-error for="consumption_no" class="mt-1" />
                </div>

                <div>
                    <label for="consumption_date" class="text-xs font-medium text-gray-600">Consumption Date *</label>
                    <input id="consumption_date" type="date" wire:model="consumption_date" class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                    <x-input-error for="consumption_date" class="mt-1" />
                </div>

                <div>
                    <label for="store_id" class="text-xs font-medium text-gray-600">Store *</label>
                    <select id="store_id" wire:model.live="store_id" class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">Select store</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">
                                {{ $store->name }} ({{ strtoupper($store->type?->value ?? (string) $store->type) }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error for="store_id" class="mt-1" />
                </div>

                <div>
                    <label for="project_id" class="text-xs font-medium text-gray-600">Project</label>
                    <select id="project_id" wire:model="project_id" class="mt-1 h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">Select project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->code }})</option>
                        @endforeach
                    </select>
                    <x-input-error for="project_id" class="mt-1" />
                </div>

                <div class="md:col-span-2 xl:col-span-4">
                    <label for="remarks" class="text-xs font-medium text-gray-600">Remarks</label>
                    <textarea id="remarks" wire:model="remarks" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none" placeholder="Optional usage note"></textarea>
                    <x-input-error for="remarks" class="mt-1" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Consumption Items</h2>
                    <p class="mt-1 text-xs text-gray-500">Stock will be validated during posting. Cost rate uses moving weighted average.</p>
                </div>
                <button type="button" wire:click="addItem" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Item
                </button>
            </div>

            <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
                <div class="max-w-full overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Product *</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Qty *</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Rate</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Remarks</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($items as $index => $item)
                                <tr>
                                    <td class="px-4 py-3 min-w-[280px]">
                                        <select wire:model="items.{{ $index }}.product_id" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                            <option value="">Select product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error for="items.{{ $index }}.product_id" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[130px]">
                                        <input type="number" min="0.001" step="0.001" wire:model.live="items.{{ $index }}.quantity" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                        <x-input-error for="items.{{ $index }}.quantity" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[130px]">
                                        <input type="number" min="0" step="0.01" wire:model.live="items.{{ $index }}.unit_price" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                        <x-input-error for="items.{{ $index }}.unit_price" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[130px]">
                                        <input type="number" min="0" step="0.01" wire:model.live="items.{{ $index }}.total_price" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                                        <x-input-error for="items.{{ $index }}.total_price" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 min-w-[220px]">
                                        <input type="text" wire:model="items.{{ $index }}.remarks" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none" placeholder="Optional note">
                                        <x-input-error for="items.{{ $index }}.remarks" class="mt-1" />
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <button type="button" wire:click="removeItem({{ $index }})" class="inline-flex items-center rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50">
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ route('admin.inventory.stock-consumptions.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </a>

            @can('inventory.stock.consumption.create')
                <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                    Save Draft
                </button>
            @endcan

            @can('inventory.stock.consumption.post')
                <button type="button" wire:click="postNow" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                    Save & Post
                </button>
            @endcan
        </div>
    </form>
</div>
