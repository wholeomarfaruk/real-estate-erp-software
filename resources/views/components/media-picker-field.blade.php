<div class="grid grid-cols-1 gap-1 mb-2">
    <label class="block text-sm font-medium text-gray-900" for="{{ $field }}">
        {{ $label }}
        @if ($required)
            <span class="size-6 text-red-500 mr-1.5">*</span>
        @endif
    </label>

    <input wire:model="{{ $field }}" id="{{ $field }}" type="hidden" />

    <div wire:click="$dispatch('openMediaPicker', { target: '{{ $field }}', multiple: {{ $multiple ? 'true' : 'false' }}, type: '{{ $type }}' })"
        class="min-h-30 bg-gray-50 border border-gray-200 rounded-lg shadow-sm w-full grid place-content-center {{ $value ? '' : 'cursor-pointer' }}">
        @if ($value)
            @if ($multiple && is_array($value))
                <div class="flex flex-wrap gap-2 p-2">
                    @foreach ($value as $item)
                        <div class="relative inline-block m-2 h-25">
                            @if ($type === 'image')
                                <img src="{{ file_path(is_array($item) ? $item['path'] ?? ($item['id'] ?? '') : $item) }}"
                                    class="h-full rounded border">
                            @elseif ($type === 'video')
                                <video class="h-full rounded border" controls>
                                    <source
                                        src="{{ file_path(is_array($item) ? $item['path'] ?? ($item['id'] ?? '') : $item) }}">
                                </video>
                            @else
                                <div class="p-3 bg-white rounded border text-sm">
                                    {{ is_array($item) ? $item['name'] ?? ($item['id'] ?? 'File') : basename($item) }}
                                </div>
                            @endif

                            <button type="button"
                                wire:click.stop="removeMedia('{{ $field }}', '{{ is_array($item) ? $item['id'] ?? ($item['path'] ?? '') : $item }}')"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center">
                                ✕
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                @if (!$multiple)
                    <div class="relative inline-block m-2">
                        @if ($type === 'image')
                            <img src="{{ file_path($value) }}" class="w-full max-h-24 rounded border">
                        @elseif ($type === 'video')
                            <video class="w-full max-h-24 rounded border" controls>
                                <source src="{{ file_path($value) }}">
                            </video>
                        @else
                            <div class="p-3 bg-white rounded border text-sm">
                                {{ basename($value) }}
                            </div>
                        @endif

                        <button type="button"
                            wire:click.stop="removeMedia('{{ $field }}', '{{ is_array($value) ? $value['id'] ?? ($value['path'] ?? '') : $value }}')"
                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center">
                            ✕
                        </button>
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                     
                        @php
                            $arrayValue = $value;
                            $arrayValue = is_array($arrayValue) ? $arrayValue : [$arrayValue];
                            $arrayValue = \App\Models\File::whereIn('id', array_column($arrayValue, 'id'))->get();
                        @endphp
                        @forelse ($arrayValue as $file)
                            
                            <div
                                class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-800">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-10 w-10 rounded-lg bg-indigo-50 text-indigo-600 grid place-content-center text-sm font-semibold dark:bg-indigo-900/40">
                                        {{ strtoupper(substr($file->extension ?? 'file', 0, 3)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white/90">
                                            {{ $file->name ?? 'Document ' . $loop->iteration }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ strtoupper($file->extension ?? '') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ file_path($file->id) }}" data-fancybox="project-docs"
                                        {{ $file->extension == 'pdf' ? 'data-type=iframe data-autosize=true' : '' }}
                                        data-caption="{{ $file->name ?? 'Document ' . $loop->iteration }}"
                                        class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-50 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        View
                                    </a>
                                    <a href="{{ file_path($file->id) }}" download
                                        class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-50 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 4.5v9m0 0 3.5-3.5M12 13.5 8.5 10M4.5 19.5h15" />
                                        </svg>
                                        Download
                                    </a>
                                    @if ($canEdit)
                                        <button type="button"
                                            wire:click="removeMedia('documents', '{{ $file->id }}')"
                                            class="inline-flex items-center gap-1 rounded-md px-3 py-1.5 text-sm font-medium text-red-600 ring-1 ring-red-200 transition hover:bg-red-50 dark:ring-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No files added yet.</p>
                        @endforelse
                    </div>
                @endif
            @endif
        @else
            <span class="text-gray-500">{{ $placeholder }}</span>
        @endif
    </div>

    @error($field)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @livewire('admin.file.media-picker', ['mediapickerModal' => false], key('media-picker-' . $field))
</div>
