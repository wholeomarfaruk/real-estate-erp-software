@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 autofill:shadow-[inset_0_0_0_1000px_#fff] autofill:[-webkit-text-fill-color:var(--color-gray-900)] autofill:[caret-color:var(--color-gray-900)]']) !!}>
