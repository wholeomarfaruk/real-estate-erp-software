@php
    $canEdit  ??= true;
    $required ??= false;

    $imgExts = ['jpg','jpeg','png','gif','webp','ico','tiff','svg'];
    $vidExts = ['mp4','mov','avi','mkv'];

    $isEmpty = !$value || (is_array($value) && count($value) === 0);
$fancyGallery = null;
    if (!$isEmpty) {
        if (!$multiple) {
            $singleId    = is_array($value) ? ($value['id'] ?? null) : $value;
            $singleFile  = $singleId ? \App\Models\File::find($singleId) : null;
            $singleUrl   = $singleId ? file_path($singleId) : null;
            $singleExt   = strtolower($singleFile?->extension ?? '');
            $singleIsImg = in_array($singleExt, $imgExts);
            $singleIsVid = in_array($singleExt, $vidExts);
            $singleIsPdf = $singleExt === 'pdf';
            $fancyGallery = 'mpf-' . $field;
        } else {
            $items   = is_array($value) ? array_values($value) : [$value];
            $fileIds = array_values(array_filter(array_map(
                fn($i) => (int)(is_array($i) ? ($i['id'] ?? 0) : $i),
                $items
            )));
            $filesMap = $fileIds
                ? \App\Models\File::whereIn('id', $fileIds)->get()->keyBy('id')
                : collect();
        }
    }
@endphp

<div class="flex flex-col gap-1.5">

    {{-- Label --}}
    <label class="block text-xs font-semibold tracking-wide uppercase text-gray-500" for="{{ $field }}">
        {{ $label }}
        @if($required)<span class="text-red-500 ml-0.5">*</span>@endif
    </label>

    {{-- Hidden binding --}}
    <input wire:model="{{ $field }}" id="{{ $field }}" type="hidden" />

    @if($isEmpty)
        {{-- ── Empty drop-zone ──────────────────────────────────────────────── --}}
        <button type="button"
            wire:click="$dispatch('openMediaPicker', { target: '{{ $field }}', multiple: {{ $multiple ? 'true' : 'false' }}, type: '{{ $type }}' })"
            class="group flex flex-col items-center justify-center gap-2.5 w-full min-h-20 rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 hover:border-indigo-400 hover:bg-indigo-50/50 transition-all cursor-pointer">
            <div class="w-8 h-8 rounded-full bg-white border border-gray-200 group-hover:border-indigo-300 group-hover:bg-indigo-50 flex items-center justify-center transition-all shadow-sm">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400 group-hover:text-indigo-500 transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12M12 16.5V3"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-400 group-hover:text-indigo-600 transition-colors">{{ $placeholder }}</span>
        </button>

    @elseif(!$multiple)
        {{-- ── Single file ───────────────────────────────────────────────────── --}}
        <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
            @if($singleIsImg && $singleUrl)
                <div class="relative group">
                    <img src="{{ $singleUrl }}" alt="{{ $singleFile?->name ?? 'Image' }}"
                        class="w-full max-h-52 object-contain block bg-gray-50">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/35 transition-all flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
                        <a href="{{ $singleUrl }}"
                            data-fancybox
                            data-caption="{{ $singleFile?->name ?? 'Image' }}"
                            target="_blank"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-white/95 text-gray-700 text-xs font-semibold shadow hover:bg-white transition-all">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            View
                        </a>
                        <a href="{{ $singleUrl }}" download
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-white/95 text-gray-700 text-xs font-semibold shadow hover:bg-white transition-all">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download
                        </a>
                    </div>
                </div>

            @elseif($singleIsVid && $singleUrl)
                <video class="w-full max-h-52 bg-black" controls>
                    <source src="{{ $singleUrl }}">
                </video>

            @elseif($singleUrl)
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold shrink-0">
                        {{ strtoupper(substr($singleExt ?: 'FILE', 0, 4)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $singleFile?->name ?? 'File' }}</p>
                        <p class="text-xs text-gray-400">{{ strtoupper($singleExt) }}</p>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <a href="{{ $singleUrl }}"
                            data-fancybox="{{ $fancyGallery }}"
                            data-caption="{{ $singleFile?->name ?? 'File' }}"
                            @if($singleIsPdf) data-type="iframe" data-autosize="true" @endif
                            target="_blank"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 transition">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            View
                        </a>
                        <a href="{{ $singleUrl }}" download
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 transition">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download
                        </a>
                    </div>
                </div>
            @endif

            {{-- Footer: change / remove --}}
            <div class="flex items-center justify-between px-4 py-2 border-t border-gray-100 bg-gray-50">
                <button type="button"
                    wire:click="$dispatch('openMediaPicker', { target: '{{ $field }}', multiple: false, type: '{{ $type }}' })"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Change
                </button>
                @if($canEdit)
                    <button type="button"
                        wire:click.stop="removeMedia('{{ $field }}')"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-red-500 hover:text-red-700 transition">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                        Remove
                    </button>
                @endif
            </div>
        </div>

    @else
        {{-- ── Multiple files ────────────────────────────────────────────────── --}}
        <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">

            @if($type === 'image')
                {{-- Image thumbnails grid --}}
                <div class="flex flex-wrap gap-2 p-3">
                    @foreach($items as $item)
                        @php
                            $iId   = is_array($item) ? ($item['id'] ?? null) : (int)$item;
                            $iUrl  = $iId ? file_path($iId) : null;
                            $iFile = $iId ? ($filesMap[$iId] ?? null) : null;
                        @endphp
                        @if($iUrl)
                            <div class="relative group w-24 h-24 shrink-0 rounded-lg overflow-hidden border border-gray-200">
                                <img src="{{ $iUrl }}" alt="{{ $iFile?->name ?? 'Image' }}"
                                    class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/45 transition-all flex flex-col items-center justify-center gap-1 opacity-0 group-hover:opacity-100 p-1">
                                    <a href="{{ $iUrl }}"
                                        data-fancybox="{{ $fancyGallery }}"
                                        data-caption="{{ $iFile?->name ?? 'Image' }}"
                                        target="_blank"
                                        class="w-full text-center px-1 py-1 rounded bg-white/95 text-gray-700 text-xs font-semibold leading-none hover:bg-white transition">
                                        View
                                    </a>
                                    <a href="{{ $iUrl }}" download
                                        class="w-full text-center px-1 py-1 rounded bg-white/95 text-gray-700 text-xs font-semibold leading-none hover:bg-white transition">
                                        Download
                                    </a>
                                </div>
                                @if($canEdit)
                                    <button type="button"
                                        wire:click.stop="removeMedia('{{ $field }}', '{{ $iId }}')"
                                        class="absolute top-1 right-1 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center shadow-sm hover:bg-red-600 transition opacity-0 group-hover:opacity-100">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>

            @else
                {{-- Document / mixed file list --}}
                <div class="divide-y divide-gray-100">
                    @foreach($items as $item)
                        @php
                            $iId    = is_array($item) ? ($item['id'] ?? null) : (int)$item;
                            $iUrl   = $iId ? file_path($iId) : null;
                            $iFile  = $iId ? ($filesMap[$iId] ?? null) : null;
                            $iExt   = strtolower($iFile?->extension ?? '');
                            $iIsImg = in_array($iExt, $imgExts);
                            $iIsPdf = $iExt === 'pdf';
                        @endphp
                        @if($iUrl)
                            <div class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    @if($iIsImg)
                                        <div class="w-9 h-9 rounded-md overflow-hidden border border-gray-200 shrink-0">
                                            <img src="{{ $iUrl }}" class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <div class="w-9 h-9 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ strtoupper(substr($iExt ?: 'F', 0, 4)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate leading-snug">{{ $iFile?->name ?? 'File #' . $iId }}</p>
                                        <p class="text-xs text-gray-400 leading-none mt-0.5">{{ strtoupper($iExt) ?: 'FILE' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0 ml-3">
                                    <a href="{{ $iUrl }}"
                                        data-fancybox="{{ $fancyGallery }}"
                                        data-caption="{{ $iFile?->name ?? 'File' }}"
                                        @if($iIsPdf) data-type="iframe" data-autosize="true" @endif
                                        target="_blank"
                                        class="inline-flex items-center gap-1 px-2 py-1.5 rounded-md text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-100 transition">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        View
                                    </a>
                                    <a href="{{ $iUrl }}" download
                                        class="inline-flex items-center gap-1 px-2 py-1.5 rounded-md text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-100 transition">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        Download
                                    </a>
                                    @if($canEdit)
                                        <button type="button"
                                            wire:click.stop="removeMedia('{{ $field }}', '{{ $iId }}')"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-md text-red-500 ring-1 ring-red-200 hover:bg-red-50 transition">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Footer: file count + add more --}}
            <div class="flex items-center justify-between px-4 py-2 border-t border-gray-100 bg-gray-50">
                <span class="text-xs text-gray-400">{{ count($items) }} {{ count($items) === 1 ? 'file' : 'files' }} attached</span>
                <button type="button"
                    wire:click="$dispatch('openMediaPicker', { target: '{{ $field }}', multiple: true, type: '{{ $type }}' })"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50 transition">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add More
                </button>
            </div>
        </div>
    @endif

    {{-- Error message --}}
    @error($field)
        <p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>
    @enderror

    {{-- Media picker modal (embedded once per field) --}}
    @livewire('admin.file.media-picker', ['mediapickerModal' => false], key('media-picker-' . $field))
</div>
