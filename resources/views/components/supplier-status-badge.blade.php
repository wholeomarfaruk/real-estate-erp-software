@props([
    'status' => 'Inactive',
])

@php
    $normalizedStatus = strtolower(trim((string) $status));

    [$label, $classes] = match ($normalizedStatus) {
        'active' => ['Active', 'bg-green-100 text-green-700'],
        'blocked' => ['Blocked', 'bg-rose-100 text-rose-700'],
        default => ['Inactive', 'bg-amber-100 text-amber-700'],
    };
@endphp

<span {{ $attributes->class(['inline-flex rounded-full px-2.5 py-1 text-xs font-medium', $classes]) }}>
    {{ $label }}
</span>
