@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    [$label, $class] = match ($value) {
        'unpaid' => ['Unpaid', 'bg-rose-100 text-rose-700'],
        'partial' => ['Partial', 'bg-amber-100 text-amber-700'],
        'paid' => ['Paid', 'bg-emerald-100 text-emerald-700'],
        default => [ucfirst($value ?: 'N/A'), 'bg-gray-100 text-gray-700'],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2.5 py-1 text-xs font-medium '.$class]) }}>
    {{ $label }}
</span>
