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

    $statusKey = $supplier->status_key;
@endphp

<div class="su-detail" style="font-family:'Inter',system-ui,sans-serif; color:var(--ink-1); background:var(--canvas);">
<main class="page">

    {{-- Breadcrumb --}}
    <div class="crumb">
        <a href="#">Purchases</a>
        <span class="sep">/</span>
        <a href="{{ route('admin.supplier.suppliers.index') }}" wire:navigate>Suppliers</a>
        <span class="sep">/</span>
        <span class="crumb-now">{{ $supplier->code }} · {{ $supplier->name }}</span>
    </div>

    {{-- HERO TOPBAR --}}
    <section class="hero">
        <div class="hero-avatar">
            {{ strtoupper($initials) }}
            @if ($supplier->status && !$supplier->is_blocked)
                <span class="badge-check" title="Active supplier">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
            @endif
        </div>

        <div class="hero-mid">
            <div class="hero-id">{{ $supplier->code }} · Supplier since {{ $supplier->created_at?->format('Y-m-d') }}</div>
            <div class="hero-name">{{ $supplier->name }}</div>
            <div class="hero-tagline">
                @if ($supplier->contact_person)
                    <span class="mono">{{ $supplier->contact_person }}</span>
                    <span class="mid"></span>
                @endif
                @if ($supplier->phone)
                    <span class="mono">{{ $supplier->phone }}</span>
                    <span class="mid"></span>
                @endif
                @if ($supplier->address)
                    <span>{{ $supplier->address }}</span>
                @endif
            </div>
            <div class="hero-pills">
                @if ($supplier->is_blocked)
                    <span class="pill blocked"><span class="dot"></span>Blocked</span>
                @elseif ($supplier->status)
                    <span class="pill active"><span class="dot"></span>Active</span>
                @else
                    <span class="pill inactive"><span class="dot"></span>Inactive</span>
                @endif
                @if ($supplier->trade_license_no)
                    <span class="pill verified"><span class="dot"></span>Licensed</span>
                @endif
                @if ($supplier->tin_no)
                    <span class="pill material"><span class="dot"></span>TIN Registered</span>
                @endif
            </div>
        </div>

        <div class="hero-actions">
            <div class="row">
                <a href="{{ route('admin.supplier.suppliers.edit', $supplier) }}" wire:navigate class="btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                </a>
            </div>
            <div class="row">
                <a href="{{ route('admin.supplier.suppliers.show.invoices', $supplier) }}" wire:navigate class="btn btn-sm" style="flex:1;">Invoices</a>
                <a href="{{ route('admin.supplier.suppliers.show.advances', $supplier) }}" wire:navigate class="btn btn-sm" style="flex:1;">Advances</a>
            </div>
        </div>
    </section>

    {{-- KPI STRIP --}}
    <section class="kpi-strip">
        <div class="kpi">
            <div class="kpi-lbl">Total purchased</div>
            <div class="kpi-val">{{ $bdt($totalPurchased) }}</div>
            <div class="kpi-foot">lifetime · {{ $invoiceCount }} invoices</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Outstanding due</div>
            <div class="kpi-val" style="color:var(--bk-fg)">{{ $bdt($totalDue) }}</div>
            <div class="kpi-foot">across unpaid invoices</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Advance held</div>
            <div class="kpi-val" style="color:var(--av-fg)">{{ $bdt($totalAdvance) }}</div>
            <div class="kpi-foot">{{ $advanceCount }} advance{{ $advanceCount == 1 ? '' : 's' }}</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Purchase orders</div>
            <div class="kpi-val">{{ $orderCount }}</div>
            <div class="kpi-foot">all time</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Stock receives</div>
            <div class="kpi-val">{{ $supplier->stock_receives_count ?? $supplier->stockReceives()->count() }}</div>
            <div class="kpi-foot">all time</div>
        </div>
    </section>

    {{-- TAB NAV --}}
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
