<div
    x-data="{
        drawerOpen: $wire.entangle('drawerOpen'),
        dPropertyUnitId: $wire.entangle('dPropertyUnitId'),
        dSaleAmount: $wire.entangle('dSaleAmount'),
        dDiscountAmount: $wire.entangle('dDiscountAmount'),
        dTaxAmount: $wire.entangle('dTaxAmount'),
    }"
    x-init="$store.pageName = { name: 'Property Sales', slug: 'property-sales' }"
    style="
        --paper:#FCFBF7; --canvas:#F2EFE7;
        --ink-1:#1A1814; --ink-2:#5C5648; --ink-3:#9B9686;
        --rule:#EAE5D9; --accent:#1F3A68;
        --mono:'IBM Plex Mono', ui-monospace, monospace;
        --av-bg:#D2E7D5; --av-fg:#1F5A2C;
        --bk-bg:#F7E6C4; --bk-fg:#7A5418;
        --sd-bg:#D8E4F5; --sd-fg:#1F3D72;
        --rt-bg:#DCD9F2; --rt-fg:#3A3582;
        --rj-bg:#F1D3CE; --rj-fg:#7A2A1E;
        --in-bg:#EFEAE0; --in-fg:#5C5648;
        font-family:'Inter', system-ui, sans-serif;
        color:var(--ink-1); background:var(--canvas);
    "
    class="min-h-screen"
>
    {{-- ─── BREADCRUMB + HEAD ───────────────────────────────────────────── --}}
    <div style="padding:28px 24px 0;" class="flex items-end justify-between gap-6 flex-wrap">
        <div>
            <div style="font-size:11.5px; color:var(--ink-3); font-family:var(--mono); display:flex; gap:6px; align-items:center; margin-bottom:8px;">
                <span>Real Estate</span>
                <span style="opacity:.5">/</span>
                <span style="color:var(--ink-1)">Property Sales</span>
            </div>
            <div style="font-size:24px; font-weight:600; letter-spacing:-.01em;">Property Sales</div>
            <div style="margin-top:4px; font-size:13px; color:var(--ink-2);">Track and manage all property sale agreements and payment status.</div>
        </div>
        <div class="flex gap-2">
            @can('property_sale.create')
                <a href="{{ route('admin.properties.sales.create') }}"
                    style="appearance:none; border:1px solid var(--ink-1); background:var(--ink-1); color:var(--paper);
                           padding:7px 14px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                           display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New Sale
                </a>
            @endcan
        </div>
    </div>

    <div style="padding:20px 24px 80px;">

        {{-- ─── KPI STRIP ───────────────────────────────────────────────── --}}
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1px;
                    background:var(--rule); border:1px solid var(--rule); border-radius:10px;
                    overflow:hidden; margin-bottom:20px;">
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Total Sales</div>
                <div style="margin-top:5px; font:600 22px var(--mono); font-variant-numeric:tabular-nums;">{{ $kpi['total'] }}</div>
                <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">on record</div>
            </div>
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Total Revenue</div>
                <div style="margin-top:5px; font:600 22px var(--mono); color:var(--av-fg); font-variant-numeric:tabular-nums;">
                    ৳ {{ number_format($kpi['revenue'], 0) }}
                </div>
                <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">net amount BDT</div>
            </div>
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Pending Payments</div>
                <div style="margin-top:5px; font:600 22px var(--mono); color:var(--bk-fg);">{{ $kpi['pending'] }}</div>
                <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">awaiting payment</div>
            </div>
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Completed Sales</div>
                <div style="margin-top:5px; font:600 22px var(--mono); color:var(--av-fg);">{{ $kpi['completed'] }}</div>
                <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">status completed</div>
            </div>
        </div>

        {{-- ─── FILTERS ─────────────────────────────────────────────────── --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px;
                    padding:10px; display:flex; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap;">
            {{-- Search --}}
            <div style="flex:1; min-width:240px; display:flex; align-items:center; gap:8px;
                        padding:0 12px; height:34px; background:var(--canvas);
                        border-radius:6px; border:1px solid transparent;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--ink-3); flex-shrink:0;"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input wire:model.live.debounce.300ms="search"
                    placeholder="Search by sale #, customer name, or unit code…"
                    style="border:0; background:transparent; outline:none; width:100%; font:13px 'Inter', sans-serif; color:var(--ink-1);" />
            </div>
            {{-- Payment Status select --}}
            <select wire:model.live="filterPaymentStatus"
                style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1);
                       padding:7px 28px 7px 12px; font:500 12px 'Inter', sans-serif; border-radius:6px;
                       background-image: linear-gradient(45deg, transparent 50%, var(--ink-2) 50%), linear-gradient(135deg, var(--ink-2) 50%, transparent 50%);
                       background-position: calc(100% - 14px) 50%, calc(100% - 10px) 50%;
                       background-size: 4px 4px, 4px 4px; background-repeat: no-repeat;">
                <option value="all">All Payments</option>
                <option value="pending">Pending</option>
                <option value="partial">Partial</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
            </select>
            {{-- Status select --}}
            <select wire:model.live="filterStatus"
                style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1);
                       padding:7px 28px 7px 12px; font:500 12px 'Inter', sans-serif; border-radius:6px;
                       background-image: linear-gradient(45deg, transparent 50%, var(--ink-2) 50%), linear-gradient(135deg, var(--ink-2) 50%, transparent 50%);
                       background-position: calc(100% - 14px) 50%, calc(100% - 10px) 50%;
                       background-size: 4px 4px, 4px 4px; background-repeat: no-repeat;">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="on_hold">On Hold</option>
            </select>
        </div>

        {{-- ─── TABLE ───────────────────────────────────────────────────── --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
            {{-- Header --}}
            <div style="display:grid; grid-template-columns: 130px 1.4fr 1fr 100px 130px 110px 110px 90px;
                        padding:12px 18px; background:rgba(0,0,0,.012); border-bottom:1px solid var(--rule);
                        font:600 10.5px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-2);">
                <div>Sale #</div>
                <div>Unit &amp; Property</div>
                <div>Customer</div>
                <div>Sale Date</div>
                <div>Net Amount</div>
                <div>Payment</div>
                <div>Status</div>
                <div style="text-align:right;">Actions</div>
            </div>

            {{-- Rows --}}
            @forelse($sales as $sale)
                @php
                    $paymentColors = [
                        'pending'   => ['bg' => 'var(--bk-bg)', 'fg' => 'var(--bk-fg)'],
                        'partial'   => ['bg' => 'var(--sd-bg)', 'fg' => 'var(--sd-fg)'],
                        'paid'      => ['bg' => 'var(--av-bg)', 'fg' => 'var(--av-fg)'],
                        'cancelled' => ['bg' => 'var(--rj-bg)', 'fg' => 'var(--rj-fg)'],
                    ];
                    $statusColors = [
                        'active'    => ['bg' => 'var(--sd-bg)', 'fg' => 'var(--sd-fg)'],
                        'completed' => ['bg' => 'var(--av-bg)', 'fg' => 'var(--av-fg)'],
                        'cancelled' => ['bg' => 'var(--rj-bg)', 'fg' => 'var(--rj-fg)'],
                        'on_hold'   => ['bg' => 'var(--bk-bg)', 'fg' => 'var(--bk-fg)'],
                    ];
                    $pc = $paymentColors[$sale->payment_status] ?? $paymentColors['pending'];
                    $sc = $statusColors[$sale->status] ?? $statusColors['active'];
                @endphp
                <div style="display:grid; grid-template-columns: 130px 1.4fr 1fr 100px 130px 110px 110px 90px;
                            padding:14px 18px; border-bottom:1px solid var(--rule); align-items:center;"
                    class="hover:bg-black/[.018] transition-colors">

                    {{-- Sale # + Type --}}
                    <div>
                        <div style="font-family:var(--mono); font-size:12px; font-weight:600; color:var(--accent); letter-spacing:.03em;">
                            {{ $sale->sale_number }}
                        </div>
                        <div style="margin-top:4px;">
                            @if($sale->sale_type === 'rent')
                                <span style="padding:1px 7px; border-radius:999px; background:var(--rt-bg); color:var(--rt-fg); font:600 9.5px 'Inter', sans-serif; letter-spacing:.05em; text-transform:uppercase;">Rent</span>
                            @else
                                <span style="padding:1px 7px; border-radius:999px; background:var(--sd-bg); color:var(--sd-fg); font:600 9.5px 'Inter', sans-serif; letter-spacing:.05em; text-transform:uppercase;">Sale</span>
                            @endif
                        </div>
                    </div>

                    {{-- Unit & Property --}}
                    <div>
                        <div style="font-size:13.5px; font-weight:600;">
                            {{ $sale->propertyUnit?->code ?? '—' }}
                        </div>
                        <div style="margin-top:2px; font-size:11.5px; color:var(--ink-2);">
                            {{ $sale->propertyUnit?->property?->name ?? '—' }}
                            @if($sale->propertyUnit?->type || $sale->propertyUnit?->unit_type)
                                · <span style="text-transform:capitalize;">{{ $sale->propertyUnit->type ?? $sale->propertyUnit->unit_type }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Customer --}}
                    <div>
                        <div style="font-size:13px; font-weight:500;">{{ $sale->customer?->name ?? '—' }}</div>
                        <div style="margin-top:2px; font:11px var(--mono); color:var(--ink-3);">{{ $sale->customer?->phone ?? '' }}</div>
                    </div>

                    {{-- Sale Date --}}
                    <div style="font:12px var(--mono); color:var(--ink-2);">
                        {{ $sale->sale_date?->format('Y-m-d') ?? '—' }}
                    </div>

                    {{-- Net Amount --}}
                    <div style="font:600 13px var(--mono); font-variant-numeric:tabular-nums;">
                        ৳ {{ number_format($sale->net_amount, 2) }}
                    </div>

                    {{-- Payment Status badge --}}
                    <div>
                        <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px;
                                     background:{{ $pc['bg'] }}; color:{{ $pc['fg'] }};
                                     font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                            <span style="width:5px; height:5px; border-radius:50%; background:{{ $pc['fg'] }};"></span>
                            {{ ucfirst($sale->payment_status) }}
                        </span>
                    </div>

                    {{-- Status badge --}}
                    <div>
                        <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px;
                                     background:{{ $sc['bg'] }}; color:{{ $sc['fg'] }};
                                     font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                            <span style="width:5px; height:5px; border-radius:50%; background:{{ $sc['fg'] }};"></span>
                            {{ ucwords(str_replace('_', ' ', $sale->status)) }}
                        </span>
                    </div>

                    {{-- Actions --}}
                    <div style="text-align:right; display:flex; gap:4px; justify-content:flex-end; color:var(--ink-3);">
                        @can('property_sale.view')
                            <a href="{{ route('admin.properties.sales.show', $sale) }}"
                                title="View"
                                style="width:28px; height:28px; border-radius:5px; display:inline-flex; align-items:center; justify-content:center;
                                       text-decoration:none; background:transparent; border:0; color:var(--ink-3);"
                                class="hover:bg-black/5 hover:text-[var(--ink-1)] transition-colors">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                        @endcan
                        @can('property_sale.edit')
                            <button wire:click="openEdit({{ $sale->id }})"
                                title="Edit"
                                style="width:28px; height:28px; border-radius:5px; display:flex; align-items:center; justify-content:center;
                                       cursor:pointer; background:transparent; border:0; color:var(--ink-3);"
                                class="hover:bg-black/5 hover:text-[var(--ink-1)] transition-colors">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                        @endcan
                        {{-- Delete — locked once handed over (superadmin may still delete) --}}
                        @can('property_sale.delete')
                            @if(! $sale->isHandedOver() || auth()->user()?->hasRole('superadmin'))
                                <button
                                    x-data="livewireConfirm()"
                                    @click="confirmAction({ id: {{ $sale->id }}, method: 'deletePropertySale', title: 'Delete Property Sale?', text: 'This sale will be permanently deleted. This action cannot be undone.' })"
                                    title="Delete"
                                    style="width:28px; height:28px; border-radius:5px; display:flex; align-items:center; justify-content:center;
                                           cursor:pointer; background:transparent; border:0; color:var(--rj-fg);"
                                    class="hover:bg-[var(--rj-bg)] transition-colors">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            @else
                                <span title="Handed over — locked"
                                    style="width:28px; height:28px; border-radius:5px; display:flex; align-items:center; justify-content:center; color:var(--ink-3);">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                </span>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty
                <div style="padding:64px; text-align:center; color:var(--ink-3);">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="margin:0 auto 12px; display:block; opacity:.35;"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    <div style="font:600 13px 'Inter', sans-serif;">No property sales found.</div>
                    <div style="margin-top:4px; font:12px 'Inter', sans-serif;">Adjust your filters or create a new sale.</div>
                </div>
            @endforelse

            {{-- Pagination footer --}}
            <div style="padding:12px 18px; border-top:1px solid var(--rule); background:rgba(0,0,0,.012);
                        display:flex; justify-content:space-between; align-items:center;
                        font:11.5px var(--mono); color:var(--ink-3);">
                <span>
                    Showing {{ $sales->firstItem() ?? 0 }}–{{ $sales->lastItem() ?? 0 }}
                    of {{ $sales->total() }} sales
                </span>
                <div>
                    {{ $sales->links() }}
                </div>
            </div>
        </div>

    </div>

    {{-- ─── DRAWER SCRIM ────────────────────────────────────────────────── --}}
    <div
        x-show="drawerOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$wire.closeDrawer()"
        style="position:fixed; inset:0; background:rgba(20,18,16,.45); backdrop-filter:blur(4px); z-index:50;"
        x-cloak
    ></div>

    {{-- ─── DRAWER PANEL ────────────────────────────────────────────────── --}}
    <aside
        x-show="drawerOpen"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="transform translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform translate-x-full"
        style="position:fixed; top:0; right:0; bottom:0; width:680px; max-width:100vw;
               background:var(--canvas); z-index:51; display:flex; flex-direction:column;
               box-shadow: -20px 0 40px -20px rgba(0,0,0,.25);"
        x-cloak
    >
        {{-- Drawer Head --}}
        <div style="padding:18px 24px; border-bottom:1px solid var(--rule); background:var(--paper);
                    display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:16px; font-weight:600;">Edit Sale</h3>
                <div style="margin-top:2px; font:500 11px var(--mono); color:var(--ink-3); letter-spacing:.04em; text-transform:uppercase;">
                    Update sale details
                </div>
            </div>
            <button @click="$wire.closeDrawer()"
                style="appearance:none; border:0; background:transparent; color:var(--ink-2);
                       width:32px; height:32px; border-radius:6px; cursor:pointer;
                       display:flex; align-items:center; justify-content:center;"
                class="hover:bg-black/5 hover:text-[var(--ink-1)] transition-colors">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Drawer Body --}}
        <div style="flex:1; overflow-y:auto; padding:24px; display:flex; flex-direction:column; gap:18px;">

            {{-- ── 1. Property & Customer ── --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:14px;">
                    <h4 style="margin:0; font-size:13px; font-weight:600;">Property &amp; Customer</h4>
                    <span style="font:11px var(--mono); color:var(--ink-3);">required</span>
                </div>

                {{-- Sale Type (full width) --}}
                <div style="margin-bottom:12px;">
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                        Sale Type <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                        @foreach(['sale' => 'Sale', 'rent' => 'Rent'] as $val => $label)
                            <label style="display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:7px; cursor:pointer;
                                          border:1.5px solid {{ $dSaleType === $val ? 'var(--accent)' : 'var(--rule)' }};
                                          background:{{ $dSaleType === $val ? 'rgba(31,58,104,.06)' : 'transparent' }};">
                                <input type="radio" wire:model.live="dSaleType" value="{{ $val }}" style="accent-color:var(--accent);">
                                <span style="font:500 12.5px 'Inter', sans-serif; color:{{ $dSaleType === $val ? 'var(--accent)' : 'var(--ink-2)' }};">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('dSaleType') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                    {{-- Property select --}}
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Property <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <select wire:model.live="dPropertyId"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                            <option value="">— Select property —</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}">{{ $property->name }} ({{ $property->code }})</option>
                            @endforeach
                        </select>
                        @error('dPropertyId') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>

                    {{-- Unit select — filtered by selected property --}}
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Unit <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <select wire:model="dPropertyUnitId"
                            @if(!$dPropertyId) disabled @endif
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule);
                                   background:{{ $dPropertyId ? 'var(--paper)' : 'var(--canvas)' }};
                                   color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;
                                   opacity:{{ $dPropertyId ? '1' : '.5' }};">
                            <option value="">{{ $dPropertyId ? '— Select unit —' : '— Select property first —' }}</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">
                                    {{ $unit->code ?? $unit->unit_number }}
                                    ({{ ucfirst($unit->type ?? $unit->unit_type ?? '') }},
                                    {{ ucfirst($unit->status ?? $unit->availability_status ?? '') }})
                                </option>
                            @endforeach
                        </select>
                        @error('dPropertyUnitId') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>

                    {{-- Customer select (full width) --}}
                    <div style="grid-column:span 2;">
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Customer <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <select wire:model="dCustomerId"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                            <option value="">— Select customer —</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->customer_id }})</option>
                            @endforeach
                        </select>
                        @error('dCustomerId') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ── 2. Dates ── --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <div style="margin-bottom:14px;">
                    <h4 style="margin:0; font-size:13px; font-weight:600;">Dates</h4>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Sale Date</label>
                        <input wire:model="dSaleDate" type="date"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" class="flatpickr-only-date" />
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Contract Date</label>
                        <input wire:model="dContractDate" type="date"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" class="flatpickr-only-date" />
                    </div>
                </div>
            </div>

            {{-- ── 3. Financial ── --}}
            <div style="background:#F5F2E8; border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:14px;">
                    <h4 style="margin:0; font-size:13px; font-weight:600;">Financial Details</h4>
                    <span style="font:11px var(--mono); color:var(--ink-3);">BDT (৳)</span>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px 14px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Sale Amount <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <input wire:model.blur="dSaleAmount" type="number" min="0" step="0.01" placeholder="0.00"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        @error('dSaleAmount') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Discount</label>
                        <input wire:model.blur="dDiscountAmount" type="number" min="0" step="0.01" placeholder="0.00"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Tax</label>
                        <input wire:model.blur="dTaxAmount" type="number" min="0" step="0.01" placeholder="0.00"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                    </div>
                </div>
                {{-- Net Amount display --}}
                <div style="margin-top:14px; padding:12px 16px; background:var(--paper); border:1.5px solid var(--accent); border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font:600 11px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Net Amount (Sale − Discount + Tax)</span>
                    <span style="font:700 20px var(--mono); color:var(--accent); font-variant-numeric:tabular-nums;">
                        ৳ {{ number_format((float)$dNetAmount, 2) }}
                    </span>
                </div>
            </div>

            {{-- ── 4. Sale Details ── --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <div style="margin-bottom:14px;">
                    <h4 style="margin:0; font-size:13px; font-weight:600;">Sale Details</h4>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px 14px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Payment Terms (days)</label>
                        <input wire:model="dPaymentTerms" type="number" min="0" placeholder="e.g. 30"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Payment Status <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <select wire:model="dPaymentStatus"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        @error('dPaymentStatus') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Status <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <select wire:model="dStatus"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="on_hold">On Hold</option>
                        </select>
                        @error('dStatus') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ── 5. Sales Rep & Notes ── --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <div style="margin-bottom:14px;">
                    <h4 style="margin:0; font-size:13px; font-weight:600;">Sales Representative &amp; Notes</h4>
                </div>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Sales Representative</label>
                        <input wire:model="dSalesRepresentative" type="text" placeholder="Name of the sales agent"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Notes</label>
                        <textarea wire:model="dNotes" placeholder="Internal notes about this sale…" rows="3"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif; resize:vertical; min-height:72px;"></textarea>
                    </div>
                </div>
            </div>

        </div>

        {{-- Drawer Footer --}}
        <div style="border-top:1px solid var(--rule); background:var(--paper);">

            {{-- Validation error summary --}}
            @if($errors->any())
                <div style="padding:9px 24px; background:var(--rj-bg); border-bottom:1px solid rgba(0,0,0,.06);">
                    <ul style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:3px;">
                        @foreach($errors->all() as $error)
                            <li style="font:500 11.5px 'Inter', sans-serif; color:var(--rj-fg); display:flex; align-items:center; gap:6px;">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="padding:14px 24px; display:flex; justify-content:flex-end; align-items:center; gap:10px;">
                <button @click="$wire.closeDrawer()"
                    style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2);
                           padding:7px 16px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                           display:inline-flex; align-items:center; gap:6px;">
                    Cancel
                </button>
                <button wire:click="savePropertySale"
                    wire:loading.attr="disabled"
                    style="appearance:none; border:1px solid var(--accent); background:var(--accent); color:#fff;
                           padding:7px 18px; font:600 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                           display:inline-flex; align-items:center; gap:6px;">
                    <span wire:loading.remove wire:target="savePropertySale" style="display:inline-flex; align-items:center; gap:6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Update Sale
                    </span>
                    <span wire:loading wire:target="savePropertySale">Saving…</span>
                </button>
            </div>
        </div>
    </aside>

</div>
