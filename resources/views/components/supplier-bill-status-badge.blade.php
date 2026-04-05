@props([
    'status' => null,
])

@php
    $rawStatus = is_object($status) && method_exists($status, 'value') ? $status->value : (string) $status;
    $normalized = strtolower(trim($rawStatus));

    [$label, $classes] = match ($normalized) {
        'draft' => ['Draft', 'bg-zinc-100 text-zinc-700'],
        'open' => ['Open', 'bg-blue-100 text-blue-700'],
        'partial' => ['Partial', 'bg-amber-100 text-amber-700'],
        'paid' => ['Paid', 'bg-green-100 text-green-700'],
        'overdue' => ['Overdue', 'bg-rose-100 text-rose-700'],
        'cancelled' => ['Cancelled', 'bg-gray-100 text-gray-700'],
        default => ['N/A', 'bg-gray-100 text-gray-700'],
    };
@endphp

<span {{ $attributes->class(['inline-flex rounded-full px-2.5 py-1 text-xs font-medium', $classes]) }}>
    {{ $label }}
</span>
