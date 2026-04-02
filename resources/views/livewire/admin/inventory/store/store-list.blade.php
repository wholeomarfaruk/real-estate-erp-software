<div x-data x-init="$store.pageName = { name: 'Store Management', slug: 'stores' }">
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
                <li class="text-sm text-gray-800">Store Management</li>
            </ol>
        </nav>
    </div>

    <div class="mt-4 rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label for="search" class="sr-only">Search</label>
                    <input
                        id="search"
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search by name, code, or address"
                        class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="typeFilter" class="sr-only">Type</label>
                    <select id="typeFilter" wire:model.live="typeFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="statusFilter" class="sr-only">Status</label>
                    <select id="statusFilter" wire:model.live="statusFilter" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            @can('inventory.store.create')
                <div class="mt-4">
                    <a href="{{ route('admin.inventory.stores.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Create Store
                    </a>
                </div>
            @endcan
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="max-w-full overflow-x-auto min-h-[55vh]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Store</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Project</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Address</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($stores as $store)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-medium text-gray-800">{{ $store->name }}</p>
                                        <p class="text-xs text-gray-500">Code: {{ $store->code }}</p>
                                    </td>

                                    <td class="px-5 py-4">
                                        @php
                                            $isOffice = $store->type?->value === \App\Enums\Inventory\StoreType::OFFICE->value;
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $isOffice ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ $store->type?->label() }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        @if ($store->project)
                                            <p>{{ $store->project->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $store->project->code }}</p>
                                        @else
                                            <span class="text-xs text-gray-500">N/A</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $store->address ? \Illuminate\Support\Str::limit($store->address, 40) : 'N/A' }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $store->status ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $store->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="relative flex justify-end" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-600 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <span class="sr-only">Open actions</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5A1.5 1.5 0 1 0 10 8.5a1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" style="display: none;" x-transition class="absolute right-0 z-40 mt-10 w-48 origin-top-right rounded-md border border-zinc-200 bg-white p-1 shadow-lg">
                                                @can('inventory.store.update')
                                                    <a href="{{ route('admin.inventory.stores.edit', $store) }}" class="flex items-center rounded-lg px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100">
                                                        Edit
                                                    </a>
                                                @endcan

                                                @can('inventory.store.update')
                                                    <button
                                                        type="button"
                                                        x-data="livewireConfirm"
                                                        @click="confirmAction({
                                                            id: {{ $store->id }},
                                                            method: 'toggleStatus',
                                                            title: 'Change store status?',
                                                            text: 'Store status will be updated immediately.',
                                                            confirmText: 'Yes, update status'
                                                        })"
                                                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-100"
                                                    >
                                                        {{ $store->status ? 'Mark Inactive' : 'Mark Active' }}
                                                    </button>
                                                @endcan

                                                @can('inventory.store.delete')
                                                    <button
                                                        type="button"
                                                        x-data="livewireConfirm"
                                                        @click="confirmAction({
                                                            id: {{ $store->id }},
                                                            method: 'deleteStore',
                                                            title: 'Delete store?',
                                                            text: 'This store will be permanently deleted.',
                                                            confirmText: 'Yes, delete store'
                                                        })"
                                                        class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
                                                    >
                                                        Delete
                                                    </button>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <div class="mx-auto max-w-sm">
                                            <p class="text-sm font-medium text-gray-700">No stores found</p>
                                            <p class="mt-1 text-xs text-gray-500">Try changing search or filters, or create a new store.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($stores->hasPages())
                <div class="mt-6">
                    {{ $stores->links() }}
                </div>
            @endif
        </div>
    </div>
</div>