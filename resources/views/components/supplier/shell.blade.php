{{-- Shared chrome for every Supplier Detail tab --}}
@props(['supplier', 'active' => 'details'])

@php
    $initials = \Illuminate\Support\Str::of($supplier->name)
        ->replace(['&', '.', ','], ' ')->squish()->explode(' ')
        ->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');

    $bdt = fn ($n) => '৳ ' . (abs($n) >= 1_000_000
        ? rtrim(rtrim(number_format($n / 1_000_000, 2), '0'), '.') . 'M'
        : (abs($n) >= 1000 ? number_format(round($n / 1000)) . 'K' : number_format($n)));

    $totalPurchased = (float) $supplier->purchaseInvoices()->sum('total_amount');
    $totalDue       = (float) $supplier->purchaseInvoices()->sum('due_amount');
    $totalAdvance   = (float) $supplier->purchaseFunds()->whereIn('status', ['pending', 'completed'])->sum('amount');
    $invoiceCount   = $supplier->purchase_invoices_count ?? $supplier->purchaseInvoices()->count();
    $orderCount     = $supplier->purchase_orders_count  ?? $supplier->purchaseOrders()->count();
    $advanceCount   = $supplier->purchase_funds_count   ?? $supplier->purchaseFunds()->count();

    $tabs = [
        'details'  => ['label' => 'Details',          'route' => 'admin.supplier.suppliers.show.details',  'badge' => null],
        'invoices' => ['label' => 'Invoices',          'route' => 'admin.supplier.suppliers.show.invoices', 'badge' => $invoiceCount],
        'orders'   => ['label' => 'Purchase Orders',   'route' => 'admin.supplier.suppliers.show.orders',   'badge' => $orderCount],
        'advance'  => ['label' => 'Advance Payments',  'route' => 'admin.supplier.suppliers.show.advances', 'badge' => $advanceCount],
    ];
@endphp

<div class="min-h-screen bg-gray-50 font-sans text-gray-800">
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center gap-2 text-xs text-gray-500">
        <span class="text-gray-400">Purchases</span>
        <span class="text-gray-300">/</span>
        <a href="{{ route('admin.supplier.suppliers.index') }}" wire:navigate
           class="hover:text-gray-700 transition-colors">Suppliers</a>
        <span class="text-gray-300">/</span>
        <span class="font-medium text-gray-700">{{ $supplier->code }} · {{ $supplier->name }}</span>
    </nav>

    {{-- HERO --}}
    <div class="mb-5 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-start sm:justify-between">

        {{-- Avatar + Info --}}
        <div class="flex items-start gap-4">
            {{-- Avatar --}}
            <div class="relative shrink-0">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#0d2a4a] text-xl font-bold uppercase tracking-wide text-white shadow">
                    {{ strtoupper($initials) }}
                </div>
                @if ($supplier->status && !$supplier->is_blocked)
                    <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 shadow ring-2 ring-white" title="Active supplier">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                @endif
            </div>

            {{-- Name / meta --}}
            <div>
                <p class="font-mono text-xs text-gray-400">{{ $supplier->code }} · Supplier since {{ $supplier->created_at?->format('Y-m-d') }}</p>
                <h1 class="mt-0.5 text-xl font-semibold text-gray-900">{{ $supplier->name }}</h1>
                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-500">
                    @if ($supplier->contact_person)
                        <span class="font-mono">{{ $supplier->contact_person }}</span>
                        <span class="text-gray-300">·</span>
                    @endif
                    @if ($supplier->phone)
                        <span class="font-mono">{{ $supplier->phone }}</span>
                        <span class="text-gray-300">·</span>
                    @endif
                    @if ($supplier->address)
                        <span>{{ $supplier->address }}</span>
                    @endif
                </div>
                {{-- Status pills --}}
                <div class="mt-2 flex flex-wrap gap-2">
                    @if ($supplier->is_blocked)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Blocked
                        </span>
                    @elseif ($supplier->status)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">
                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Inactive
                        </span>
                    @endif
                    @if ($supplier->trade_license_no)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>Licensed
                        </span>
                    @endif
                    @if ($supplier->tin_no)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-2.5 py-0.5 text-xs font-medium text-violet-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-violet-500"></span>TIN Registered
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="flex flex-col items-stretch gap-2 sm:items-end">
            <a href="{{ route('admin.supplier.suppliers.edit', $supplier) }}" wire:navigate
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0d2a4a] px-4 py-2 text-xs font-medium text-white shadow-sm hover:bg-[#0a2240] transition-colors">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit Supplier
            </a>
            <div class="flex gap-2">
                <a href="{{ route('admin.supplier.suppliers.show.invoices', $supplier) }}" wire:navigate
                   class="flex-1 inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    Invoices
                </a>
                <a href="{{ route('admin.supplier.suppliers.show.advances', $supplier) }}" wire:navigate
                   class="flex-1 inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    Advances
                </a>
            </div>
        </div>
    </div>

    {{-- KPI STRIP --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Total purchased</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $bdt($totalPurchased) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">{{ $invoiceCount }} invoices</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Outstanding due</p>
            <p class="mt-1 text-lg font-semibold text-red-600">{{ $bdt($totalDue) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">unpaid invoices</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Advance held</p>
            <p class="mt-1 text-lg font-semibold text-emerald-600">{{ $bdt($totalAdvance) }}</p>
            <p class="mt-0.5 text-xs text-gray-400">{{ $advanceCount }} advance{{ $advanceCount == 1 ? '' : 's' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500">Purchase orders</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $orderCount }}</p>
            <p class="mt-0.5 text-xs text-gray-400">all time</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm col-span-2 sm:col-span-1">
            <p class="text-xs text-gray-500">Stock receives</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $supplier->stock_receives_count ?? $supplier->stockReceives()->count() }}</p>
            <p class="mt-0.5 text-xs text-gray-400">all time</p>
        </div>
    </div>

    {{-- TAB NAV --}}
    <div class="mb-5 border-b border-gray-200">
        <nav class="-mb-px flex gap-1 overflow-x-auto">
            @foreach ($tabs as $key => $tab)
                <a href="{{ route($tab['route'], $supplier) }}" wire:navigate
                   class="inline-flex shrink-0 items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors
                          {{ $active === $key
                              ? 'border-[#0d2a4a] text-[#0d2a4a]'
                              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    @if ($key === 'details')
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    @endif
                    {{ $tab['label'] }}
                    @if ($tab['badge'] !== null)
                        <span class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-xs font-semibold leading-none
                                     {{ $active === $key ? 'bg-[#0d2a4a] text-white' : 'bg-gray-100 text-gray-600' }}">
                            {{ $tab['badge'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Active tab body --}}
    {{ $slot }}

</div>
</div>
