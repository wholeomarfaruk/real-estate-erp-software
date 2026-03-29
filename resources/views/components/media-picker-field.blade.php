<div class="grid grid-cols-1 gap-1 mb-2">
    <label class="block text-sm font-medium text-gray-900" for="{{ $field }}">
        {{ $label }}
        @if($required)
            <span class="size-6 text-red-500 mr-1.5">*</span>
        @endif
    </label>

    <input wire:model="{{ $field }}" id="{{ $field }}" type="hidden" />

    <div
        wire:click="$dispatch('openMediaPicker', { target: '{{ $field }}', multiple: {{ $multiple ? 'true' : 'false' }}, type: '{{ $type }}' })"
        class="min-h-30 bg-gray-200 border border-gray-300 rounded-lg shadow-sm w-full grid place-content-center {{ $value ? '' : 'cursor-pointer' }}"
    >
        @if ($value)
            @if ($multiple && is_array($value))
                <div class="flex flex-wrap gap-2 p-2">
                    @foreach ($value as $item)
                        <div class="relative inline-block m-2 h-25">
                            @if ($type === 'image')
                                <img
                                    src="{{ file_path(is_array($item) ? ($item['path'] ?? $item['id'] ?? '') : $item) }}"
                                    class="h-full rounded border"
                                >
                            @elseif ($type === 'video')
                                <video class="h-full rounded border" controls>
                                    <source src="{{ file_path(is_array($item) ? ($item['path'] ?? $item['id'] ?? '') : $item) }}">
                                </video>
                            @else
                                <div class="p-3 bg-white rounded border text-sm">
                                    {{ is_array($item) ? ($item['name'] ?? ($item['id'] ?? 'File')) : basename($item) }}
                                </div>
                            @endif

                            <button
                                type="button"
                                wire:click.stop="removeMedia('{{ $field }}', '{{ is_array($item) ? ($item['id'] ?? $item['path'] ?? '') : $item }}')"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center"
                            >
                                ✕
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="relative inline-block m-2">
                    @if ($type === 'image')
                        <img
                            src="{{ file_path($value) }}"
                            class="w-full max-h-24 rounded border"
                        >
                    @elseif ($type === 'video')
                        <video class="w-full max-h-24 rounded border" controls>
                            <source src="{{ file_path($value) }}">
                        </video>
                    @else
                        <div class="p-3 bg-white rounded border text-sm">
                            {{ basename($value) }}
                        </div>
                    @endif

                    <button
                        type="button"
                        wire:click.stop="removeMedia('{{ $field }}', '{{ is_array($value) ? ($value['id'] ?? $value['path'] ?? '') : $value }}')"
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center"
                    >
                        ✕
                    </button>
                </div>
            @endif
        @else
            <span class="text-gray-500">{{ $placeholder }}</span>
        @endif
    </div>

    @error($field)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
        @livewire('admin.file.media-picker', ['mediapickerModal' => false], key('media-picker-'.$field))
</div>