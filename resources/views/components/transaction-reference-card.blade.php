@props(['reference' => null])

@if ($reference)
    @php
        $bgColor = match ($reference['icon'] ?? '') {
            '⚠️' => 'border-amber-200 bg-amber-50',
            '❌' => 'border-red-200 bg-red-50',
            default => 'border-gray-200 bg-gray-50',
        };
    @endphp
    <div {{ $attributes->merge(['class' => "flex items-center gap-3 rounded-lg border {$bgColor} p-3"]) }}>
        <span class="inline-grid h-8 w-8 shrink-0 place-items-center rounded-lg text-lg"
            @class([
                'bg-gray-600 text-white' => !in_array($reference['icon'] ?? '', ['⚠️', '❌']),
                'bg-amber-100' => ($reference['icon'] ?? '') === '⚠️',
                'bg-red-100' => ($reference['icon'] ?? '') === '❌',
            ])>
            {{ $reference['icon'] ?? '🔗' }}
        </span>
        <div class="flex-1 min-w-0">
            <p class="font-mono text-xs font-semibold text-gray-800">
                {{ $reference['label'] }}
            </p>
            @if (isset($reference['details']) && is_array($reference['details']))
                @foreach ($reference['details'] as $key => $value)
                    @if ($value)
                        <p class="mt-0.5 font-mono text-[10px] text-gray-500">
                            <span class="font-semibold text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span> {{ $value }}
                        </p>
                    @endif
                @endforeach
            @elseif (isset($reference['details']))
                <p class="mt-0.5 font-mono text-[10px] text-gray-500">
                    {{ $reference['details'] }}
                </p>
            @endif

            @if (!empty($reference['url']))
                <a href="{{ $reference['url'] }}" target="_blank" rel="noopener noreferrer"
                    class="mt-1 inline-flex items-center gap-1 text-[10px] font-semibold text-indigo-600 hover:text-indigo-800 hover:underline">
                    {{ $reference['url_label'] ?? 'Open' }}
                    <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                </a>
            @endif
        </div>
    </div>
@endif
