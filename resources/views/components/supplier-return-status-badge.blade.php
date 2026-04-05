@props([
    'status' => null,
])

@php
    $rawStatus = is_object($status) && method_exists($status, 'value') ? $status->value : (string) $status;
    $normalized = strtolower(trim($rawStatus));

    [$label, $classes] = match ($normalized) {
        'draft' => ['Draft', 'bg-zinc-100 text-zinc-700'],
        'approved' => ['Approved', 'bg-emerald-100 text-emerald-700'],
        'cancelled' => ['Cancelled', 'bg-rose-100 text-rose-700'],
        default => ['N/A', 'bg-gray-100 text-gray-700'],
    };
@endphp

<span {{ $attributes->class(['inline-flex rounded-full px-2.5 py-1 text-xs font-medium', $classes]) }}>
    {{ $label }}
</span>
