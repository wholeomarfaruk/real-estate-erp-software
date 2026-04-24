@props(['type'])

@php
    $value = $type instanceof \BackedEnum ? $type->value : (string) $type;

    [$label, $class] = match ($value) {
        'payment' => ['Payment', 'bg-rose-100 text-rose-700'],
        'collection' => ['Collection', 'bg-emerald-100 text-emerald-700'],
        'expense' => ['Expense', 'bg-amber-100 text-amber-700'],
        'journal' => ['Journal', 'bg-indigo-100 text-indigo-700'],
        default => [ucfirst($value ?: 'N/A'), 'bg-gray-100 text-gray-700'],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2.5 py-1 text-xs font-medium '.$class]) }}>
    {{ $label }}
</span>
