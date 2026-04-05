@props([
    'type' => null,
])

@php
    $rawType = is_object($type) && method_exists($type, 'value') ? $type->value : (string) $type;
    $normalized = strtolower(trim($rawType));

    [$label, $classes] = match ($normalized) {
        'opening_balance' => ['Opening Balance', 'bg-zinc-100 text-zinc-700'],
        'bill' => ['Bill', 'bg-indigo-100 text-indigo-700'],
        'payment' => ['Payment', 'bg-emerald-100 text-emerald-700'],
        'return' => ['Return', 'bg-sky-100 text-sky-700'],
        'adjustment' => ['Adjustment', 'bg-amber-100 text-amber-700'],
        'advance_adjustment' => ['Advance Adjustment', 'bg-fuchsia-100 text-fuchsia-700'],
        default => ['N/A', 'bg-gray-100 text-gray-700'],
    };
@endphp

<span {{ $attributes->class(['inline-flex rounded-full px-2.5 py-1 text-xs font-medium', $classes]) }}>
    {{ $label }}
</span>
