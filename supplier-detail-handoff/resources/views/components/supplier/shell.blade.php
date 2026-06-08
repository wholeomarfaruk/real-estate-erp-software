{{--
    resources/views/components/supplier/shell.blade.php
    Shared chrome for every Supplier Detail tab: breadcrumb + hero topbar +
    KPI strip + tab nav. Each tab is its OWN Livewire route/component and wraps
    its body in this component, so the hero/tabs are identical across modules.

    Tabs link with wire:navigate → SPA-feel, no full reload, shared layout stays.

    Usage (from a tab view):
        <x-supplier.shell :supplier="$supplier" active="invoices">
            ...tab body...
        </x-supplier.shell>

    Props:
        $supplier  App\Models\Supplier (or any object exposing the fields below)
        $active    one of: details | invoices | orders | advance
--}}
@props(['supplier', 'active' => 'details'])

@php
    // Initials for the avatar (e.g. "Meghna Cement…" → "MC")
    $initials = \Illuminate\Support\Str::of($supplier->name)
        ->replace(['&', '.', ','], ' ')->squish()->explode(' ')
        ->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');

    // Compact BDT (৳ 18.4M / ৳ 842K). Replace with your money helper if you have one.
    $bdt = fn ($n) => '৳ ' . (abs($n) >= 1_000_000
        ? rtrim(rtrim(number_format($n / 1_000_000, 2), '0'), '.') . 'M'
        : (abs($n) >= 1000 ? round($n / 1000) . 'K' : number_format($n)));

    $tabs = [
        'details'  => ['label' => 'Details',         'route' => 'suppliers.show.details',  'badge' => null],
        'invoices' => ['label' => 'Invoices',        'route' => 'suppliers.show.invoices', 'badge' => $supplier->invoices_count ?? 118],
        'orders'   => ['label' => 'Purchase Orders', 'route' => 'suppliers.show.orders',   'badge' => $supplier->orders_count ?? 63],
        'advance'  => ['label' => 'Advance Payments','route' => 'suppliers.show.advances', 'badge' => $supplier->advances_count ?? 7],
    ];
@endphp

<div class="su-detail" style="font-family:'Inter',system-ui,sans-serif; color:var(--ink-1); background:var(--canvas);">
<main class="page">

    {{-- Breadcrumb --}}
    <div class="crumb">
        <a href="#">Purchases</a>
        <span class="sep">/</span>
        <a href="{{ route('suppliers.index') }}" wire:navigate>Suppliers</a>
        <span class="sep">/</span>
        <span class="crumb-now">{{ $supplier->code }} · {{ $supplier->name }}</span>
    </div>

    {{-- HERO TOPBAR --}}
    <section class="hero">
        <div class="hero-avatar">
            {{ strtoupper($initials) }}
            @if ($supplier->is_verified ?? true)
                <span class="badge-check" title="Verified vendor">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
            @endif
        </div>

        <div class="hero-mid">
            <div class="hero-id">{{ $supplier->code }} · Supplier since {{ $supplier->created_at?->format('Y-m-d') ?? '2026-05-28' }}</div>
            <div class="hero-name">{{ $supplier->name }}</div>
            <div class="hero-tagline">
                <span>{{ $supplier->category ?? 'Cement, aggregates & RMC supplier' }}</span>
                <span class="mid"></span>
                <span class="mono">{{ $supplier->contact_person ?? 'Rafiqul Islam' }}</span>
                <span class="mid"></span>
                <span class="mono">{{ $supplier->phone ?? '+880 1711 904 220' }}</span>
                <span class="mid"></span>
                <span>{{ $supplier->address ?? 'Tongi I/A, Gazipur' }}</span>
            </div>
            <div class="hero-pills">
                <span class="pill active"><span class="dot"></span>Active</span>
                <span class="pill verified"><span class="dot"></span>Verified</span>
                <span class="pill material"><span class="dot"></span>Materials</span>
                <span class="pill preferred"><span class="dot"></span>Preferred vendor</span>
            </div>
        </div>

        <div class="hero-actions">
            <div class="row">
                <button class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    New invoice
                </button>
                <button class="btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                </button>
            </div>
            <div class="row">
                <button class="btn btn-sm" style="flex:1;">Record payment</button>
                <button class="btn btn-sm" style="flex:1;">Statement</button>
            </div>
        </div>
    </section>

    {{-- KPI STRIP --}}
    <section class="kpi-strip">
        <div class="kpi">
            <div class="kpi-lbl">Total purchased</div>
            <div class="kpi-val">{{ $bdt($supplier->total_purchased ?? 18400000) }}</div>
            <div class="kpi-foot">lifetime · {{ $tabs['invoices']['badge'] }} invoices</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Outstanding due</div>
            <div class="kpi-val" style="color:var(--bk-fg)">{{ $bdt($supplier->outstanding_due ?? 842000) }}</div>
            <div class="kpi-foot">4 unpaid · ৳ 96K overdue</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Advance held</div>
            <div class="kpi-val" style="color:var(--av-fg)">{{ $bdt($supplier->advance_held ?? 0) }}</div>
            <div class="kpi-foot">no open advance</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Purchase orders</div>
            <div class="kpi-val">{{ $tabs['orders']['badge'] }}</div>
            <div class="kpi-foot">3 open · 60 received</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Avg. payment days</div>
            <div class="kpi-val">26<span style="font-size:13px; color:var(--ink-3)"> days</span></div>
            <div class="kpi-foot">net-30 terms</div>
        </div>
    </section>

    {{-- TAB NAV (separate modules, linked by route + wire:navigate) --}}
    <nav class="tabs-bar">
        @foreach ($tabs as $key => $tab)
            <a href="{{ route($tab['route'], $supplier) }}" wire:navigate
               class="{{ $active === $key ? 'active' : '' }}">
                @if ($key === 'details')
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                @endif
                {{ $tab['label'] }}
                @if ($tab['badge'] !== null)
                    <span class="badge">{{ $tab['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    {{-- Active tab body --}}
    {{ $slot }}

</main>
</div>
