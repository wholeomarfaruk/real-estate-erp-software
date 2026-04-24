@props(['type'])

@php
    $value = $type instanceof \BackedEnum ? $type->value : (string) $type;

    [$label, $class] = match ($value) {
        'asset' => ['Asset', 'bg-blue-100 text-blue-700'],
        'liability' => ['Liability', 'bg-amber-100 text-amber-700'],
        'income' => ['Income', 'bg-emerald-100 text-emerald-700'],
        'expense' => ['Expense', 'bg-rose-100 text-rose-700'],
        'equity' => ['Equity', 'bg-violet-100 text-violet-700'],
        default => [ucfirst($value ?: 'N/A'), 'bg-gray-100 text-gray-700'],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2.5 py-1 text-xs font-medium '.$class]) }}>
    {{ $label }}
</span>
