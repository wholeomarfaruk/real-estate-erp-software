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
        </div>
    </div>
@endif
