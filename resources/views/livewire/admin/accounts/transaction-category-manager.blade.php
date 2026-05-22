<div x-data x-init="$store.pageName = { name: 'Transaction Categories', slug: 'transaction-categories' }">

    {{-- Breadcrumb --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <nav>
            <ol class="flex items-center gap-1.5 text-sm text-gray-500">
                <li><a href="{{ route('admin.dashboard') }}" class="hover:text-gray-800">Dashboard</a></li>
                <li>/</li>
                <li><a href="{{ route('admin.accounts.banking.index') }}" class="hover:text-gray-800">Banking</a></li>
                <li>/</li>
                <li class="text-gray-800">Transaction Categories</li>
            </ol>
        </nav>

        <button type="button" wire:click="openCreate"
            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            New Category
        </button>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap gap-2">
        <div class="relative">
            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search categories…"
                class="h-9 rounded-lg border border-gray-300 pl-8 pr-3 text-sm focus:border-indigo-500 focus:outline-none min-w-52">
        </div>

        <select wire:model.live="filterType"
            class="h-9 rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
            <option value="">All Types</option>
            @foreach($types as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- Category Groups --}}
    <div class="space-y-4">
        @forelse($groups as $parent)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">

            {{-- Parent header --}}
            <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <span class="rounded-lg bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 uppercase tracking-wide">
                        {{ $types[$parent->type] ?? $parent->type }}
                    </span>
                    <span class="font-semibold text-gray-800">{{ $parent->name }}</span>
                    @if($parent->is_locked)
                        <span class="rounded bg-gray-100 px-2 py-0.5 text-[10px] text-gray-500">system</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if(!$parent->is_locked)
                    <button wire:click="openEdit({{ $parent->id }})"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    <button wire:click="delete({{ $parent->id }})"
                        wire:confirm="Delete this category?"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
                            <path d="M10 11v6"/><path d="M14 11v6"/>
                        </svg>
                    </button>
                    @endif
                </div>
            </div>

            {{-- Sub-categories --}}
            @if($parent->children->count())
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-wide">
                        <th class="px-5 py-2 text-left font-medium">Sub-category</th>
                        <th class="px-5 py-2 text-left font-medium">Slug</th>
                        <th class="px-5 py-2 text-center font-medium">Status</th>
                        <th class="px-5 py-2 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($parent->children->sortBy('name') as $child)
                        @include('livewire.admin.accounts.partials.category-row', ['category' => $child, 'level' => 1])
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="px-5 py-4 text-sm text-gray-400 italic">No sub-categories yet.</p>
            @endif
        </div>
        @empty
        <div class="rounded-2xl border border-gray-200 bg-white px-6 py-12 text-center text-sm text-gray-400">
            No categories found.
        </div>
        @endforelse
    </div>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
         x-data x-on:keydown.escape.window="$wire.closeModal()">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">

            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-800">
                    {{ $isEditing ? 'Edit Category' : 'New Category' }}
                </h3>
                <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4 px-6 py-5">

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" placeholder="e.g. Employee Advance"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Type --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">Type <span class="text-red-500">*</span></label>
                    <select wire:model.live="type"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">— Select type —</option>
                        @foreach($types as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Parent (optional) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">Parent Category <span class="text-gray-400">(optional)</span></label>
                    <select wire:model="parent_id"
                        class="mt-1 h-9 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-indigo-500 focus:outline-none">
                        <option value="">— None (top-level) —</option>
                        @foreach($parentOptions->where('type', $type) as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600">Description</label>
                    <textarea wire:model="description" rows="2" placeholder="Optional notes…"
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                </div>

            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                <button wire:click="closeModal"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button wire:click="save"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                    {{ $isEditing ? 'Update' : 'Create' }}
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
