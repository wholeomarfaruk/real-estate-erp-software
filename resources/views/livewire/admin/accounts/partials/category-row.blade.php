<tr class="hover:bg-gray-50 transition">
    <td class="px-5 py-2.5 font-medium text-gray-700">
        <span style="margin-left: {{ $level * 20 }}px;">
            @if($category->children->count())
                <span class="text-gray-400 mr-1">└</span>
            @else
                <span class="mr-1"></span>
            @endif
            {{ $category->name }}
        </span>
    </td>
    <td class="px-5 py-2.5 text-gray-400 font-mono text-xs">{{ $category->slug }}</td>
    <td class="px-5 py-2.5 text-center">
        <button wire:click="toggleActive({{ $category->id }})"
            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium transition
                {{ $category->is_active
                    ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                    : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
            {{ $category->is_active ? 'Active' : 'Inactive' }}
        </button>
    </td>
    <td class="px-5 py-2.5 text-right">
        <div class="flex items-center justify-end gap-1">
            @if(!$category->is_locked)
            <button wire:click="openEdit({{ $category->id }})"
                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </button>
            <button wire:click="delete({{ $category->id }})"
                wire:confirm="Delete '{{ $category->name }}'?"
                class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600 transition">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
                    <path d="M10 11v6"/><path d="M14 11v6"/>
                </svg>
            </button>
            @else
            <span class="text-xs text-gray-300 px-2">locked</span>
            @endif
        </div>
    </td>
</tr>
@foreach($category->children->sortBy('name') as $child)
    @include('livewire.admin.accounts.partials.category-row', ['category' => $child, 'level' => $level + 1])
@endforeach
