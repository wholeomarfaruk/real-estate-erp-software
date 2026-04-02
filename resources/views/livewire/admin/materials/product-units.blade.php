<div x-data x-init="$store.pageName = { name: 'Product Units' }">
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
        <form wire:submit.prevent="save" class="md:col-span-1 space-y-3 bg-white p-4 rounded-lg shadow-sm">
            <h2 class="text-sm font-semibold text-gray-700">{{ $editingId ? 'Edit' : 'Create' }}</h2>
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Name <span class="text-red-400">*</span></label>
                <input type="text" wire:model.live.debounce.500ms="name" class="w-full rounded-md border-gray-300 text-sm">
                @error('name')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="px-3 py-2 bg-gray-900 text-white text-xs rounded-md cursor-pointer">Save Brand</button>
                <button type="button" wire:click="resetForm" class="px-3 py-2 text-xs border rounded-md cursor-pointer">Clear</button>
            </div>
        </form>

        <div class="md:col-span-2 bg-white p-4 rounded-lg shadow-sm">


            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-left">
                        <tr>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Updated</th>
                            <th class="px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($units as $unit)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-800">
                                    <div class="flex items-center gap-3">
                                        <a class="block"
                                            href="{{ 'https://ui-avatars.com/api/?name=' . urlencode($unit->name) . '&background=111827&color=fff&rounded=falese&bold=true' }}"
                                            data-fancybox

                                            <img src="{{'https://ui-avatars.com/api/?name=' . urlencode($unit->name) . '&background=111827&color=fff&rounded=falese&bold=true' }}"
                                                class="h-10 w-10 rounded-sm object-cover">
                                        </a>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                                {{ $unit->name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-gray-500">{{ $unit->updated_at?->diffForHumans() }}</td>
                                <td class="px-3 py-2 flex gap-2">
                                    <button wire:click="edit({{ $unit->id }})"
                                        class="text-xs px-2 py-1 rounded border flex gap-2 hover:bg-gray-50 cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                        Edit</button>
                                    <button x-data="livewireConfirm()"
                                                    @click="confirmAction({id: {{ $unit->id }},method: 'delete',title: 'Are you sure?',text: 'This record & all related data will be permanently deleted!',confirmText: 'Yes, delete unit!',icon: 'warning'})"
                                        class="text-xs px-2 py-1 rounded border border-red-300 text-red-600 flex gap-2 hover:bg-red-50 cursor-pointer"><svg
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-gray-500">No Units yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $units->links() }}</div>
        </div>
    </div>

</div>
