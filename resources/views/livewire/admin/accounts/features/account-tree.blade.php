@forelse ($accounts as $account)
    @php
        // Check if all children are enabled
        $allChildrenIds = collect($account['children'])->pluck('id')->map('strval')->toArray();
        $allChildrenEnabled = !empty($allChildrenIds) && count(array_intersect($allChildrenIds, $enabledMappings)) === count($allChildrenIds);
        $someChildrenEnabled = !empty($allChildrenIds) && count(array_intersect($allChildrenIds, $enabledMappings)) > 0;
    @endphp

    @if (count($account['children']) > 0)
        {{-- Parent Account (has children) --}}
        <div style="margin-left: {{ $depth * 24 }}px;" class="mt-3">
            @if ($depth > 0)
                {{-- Show checkbox for non-root parents --}}
                <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
                    <div class="mt-1 w-4 h-4 shrink-0">
                        <input type="checkbox"
                            wire:click="toggleParent({{ $account['id'] }})"
                            @checked($allChildrenEnabled)
                            indeterminate="{{ $someChildrenEnabled && !$allChildrenEnabled ? 'true' : 'false' }}"
                            style="width: auto; padding: 0; border: 1px solid #ccc;"
                            class="text-indigo-600 rounded cursor-pointer">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-gray-900 cursor-pointer flex items-center gap-2">
                            <span class="text-indigo-600">📁</span>
                            {{ $account['name'] }}
                        </label>
                        @if ($account['code'])
                            <span class="text-xs text-gray-500">{{ $account['code'] }}</span>
                        @endif
                    </div>
                </div>
            @else
                {{-- Root parent (no checkbox, just title) --}}
                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span class="text-indigo-600">▼</span>
                    {{ $account['name'] }}
                    @if ($account['code'])
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">{{ $account['code'] }}</span>
                    @endif
                </h3>
            @endif

            {{-- Recursively render children --}}
            <div style="margin-left: {{ 12 }}px;">
                @include('livewire.admin.accounts.features.account-tree', [
                    'accounts' => $account['children'],
                    'depth' => $depth + 1,
                    'enabledMappings' => $enabledMappings
                ])
            </div>
        </div>
    @else
        {{-- Child Account (leaf node - has checkbox) --}}
        <div style="margin-left: {{ $depth * 24 }}px;" class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
            <div class="mt-1 w-4 h-4 shrink-0">
                <input type="checkbox"
                    wire:click="toggleChild({{ $account['id'] }})"
                    @checked(in_array((string)$account['id'], $enabledMappings))
                    style="width: auto; padding: 0; border: 1px solid #ccc;"
                    class="text-indigo-600 rounded cursor-pointer">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-900 cursor-pointer">{{ $account['name'] }}</label>
                @if ($account['code'])
                    <span class="text-xs text-gray-500">{{ $account['code'] }}</span>
                @endif
            </div>
        </div>
    @endif
@empty
    <p class="text-sm text-gray-500">No accounts available</p>
@endforelse
