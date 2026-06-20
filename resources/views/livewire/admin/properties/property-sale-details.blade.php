<div
    x-data="{
        drawerOpen: $wire.entangle('drawerOpen'),
        scheduleDrawerOpen: $wire.entangle('scheduleDrawerOpen'),
        payNowModalOpen: $wire.entangle('payNowModalOpen'),
        receiptModalOpen: $wire.entangle('receiptModalOpen'),
        receiptTx: {},
        attachModalOpen: $wire.entangle('attachModalOpen'),
        attachList: [],
        dSaleAmount: $wire.entangle('dSaleAmount'),
        dDiscountAmount: $wire.entangle('dDiscountAmount'),
        dTaxAmount: $wire.entangle('dTaxAmount'),
    }"
    x-init="$store.pageName = { name: 'Sale Details', slug: 'property-sales' }"
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
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>

    {{-- ─── HEADER ─────────────────────────────────────────────────────────── --}}
    <div style="padding:28px 24px 0;" class="flex items-start justify-between gap-6 flex-wrap">
        <div>
            <div style="font-size:11.5px; color:var(--ink-3); font-family:var(--mono); display:flex; gap:6px; align-items:center; margin-bottom:8px;">
                <span>Real Estate</span>
                <span style="opacity:.5">/</span>
                <a href="{{ route('admin.properties.sales.index') }}" style="color:var(--ink-3); text-decoration:none;">Property Sales</a>
                <span style="opacity:.5">/</span>
                <span style="color:var(--ink-1);">{{ $sale->sale_number }}</span>
            </div>
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <div style="font-size:24px; font-weight:600; letter-spacing:-.01em; font-family:var(--mono);">
                    {{ $sale->sale_number }}
                </div>
                @if($sale->sale_type === 'rent')
                    <span style="display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px;
                                 background:var(--rt-bg); color:var(--rt-fg);
                                 font:600 10.5px 'Inter', sans-serif; letter-spacing:.05em; text-transform:uppercase;">
                        <span style="width:5px; height:5px; border-radius:50%; background:var(--rt-fg);"></span>
                        Rent
                    </span>
                @else
                    <span style="display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px;
                                 background:var(--sd-bg); color:var(--sd-fg);
                                 font:600 10.5px 'Inter', sans-serif; letter-spacing:.05em; text-transform:uppercase;">
                        <span style="width:5px; height:5px; border-radius:50%; background:var(--sd-fg);"></span>
                        Sale
                    </span>
                @endif
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
                <span style="display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px;
                             background:{{ $pc['bg'] }}; color:{{ $pc['fg'] }};
                             font:600 10.5px 'Inter', sans-serif; letter-spacing:.05em; text-transform:uppercase;">
                    <span style="width:5px; height:5px; border-radius:50%; background:{{ $pc['fg'] }};"></span>
                    {{ ucfirst($sale->payment_status) }}
                </span>
                <span style="display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px;
                             background:{{ $sc['bg'] }}; color:{{ $sc['fg'] }};
                             font:600 10.5px 'Inter', sans-serif; letter-spacing:.05em; text-transform:uppercase;">
                    <span style="width:5px; height:5px; border-radius:50%; background:{{ $sc['fg'] }};"></span>
                    {{ ucwords(str_replace('_', ' ', $sale->status)) }}
                </span>
            </div>
            <div style="margin-top:5px; font-size:13px; color:var(--ink-2);">
                Recorded {{ $sale->created_at->format('d M Y') }}
                @if($sale->sale_date) · Sale date {{ $sale->sale_date->format('d M Y') }} @endif
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-shrink:0;">
            <a href="{{ route('admin.properties.sales.invoice', $sale) }}" target="_blank"
                style="appearance:none; border:1px solid var(--accent); background:var(--accent); color:#fff;
                       padding:7px 14px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                       display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Invoice
            </a>
            <a href="{{ route('admin.properties.sales.schedule', $sale) }}" target="_blank"
                style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2);
                       padding:7px 14px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                       display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Schedule
            </a>
            @can('property_sale.edit')
                <button @click="drawerOpen = true"
                    style="appearance:none; border:1px solid var(--ink-1); background:var(--ink-1); color:var(--paper);
                           padding:7px 14px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                           display:inline-flex; align-items:center; gap:6px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                </button>
            @endcan
            <a href="{{ route('admin.properties.sales.index') }}"
                style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2);
                       padding:7px 14px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                       display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back
            </a>
        </div>
    </div>

    {{-- ─── BODY ────────────────────────────────────────────────────────────── --}}
    <div style="padding:20px 24px 80px; display:grid; grid-template-columns:1fr 340px; gap:16px; align-items:start;">

        {{-- ── LEFT COLUMN ── --}}
        <div style="display:flex; flex-direction:column; gap:16px;">

            {{-- Financial Breakdown --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
                <div style="padding:14px 20px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-size:13px; font-weight:600;">Financial Summary</h3>
                    <span style="font:11px var(--mono); color:var(--ink-3);">BDT (৳)</span>
                </div>
                <div style="padding:20px; display:grid; grid-template-columns:1fr 1fr 1fr; gap:1px; background:var(--rule);">
                    <div style="background:var(--paper); padding:16px 18px;">
                        <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Sale Amount</div>
                        <div style="font:600 20px var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format($sale->sale_amount, 2) }}</div>
                    </div>
                    <div style="background:var(--paper); padding:16px 18px;">
                        <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--rj-fg); margin-bottom:6px;">Discount</div>
                        <div style="font:600 20px var(--mono); color:var(--rj-fg); font-variant-numeric:tabular-nums;">− ৳ {{ number_format($sale->discount_amount, 2) }}</div>
                    </div>
                    <div style="background:var(--paper); padding:16px 18px;">
                        <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Tax</div>
                        <div style="font:600 20px var(--mono); font-variant-numeric:tabular-nums;">+ ৳ {{ number_format($sale->tax_amount, 2) }}</div>
                    </div>
                </div>
                <div style="padding:16px 20px; background:#F5F2E8; display:flex; justify-content:space-between; align-items:center; border-top:1.5px solid var(--accent);">
                    <span style="font:600 11px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-2);">Net Amount (Sale − Discount + Tax)</span>
                    <span style="font:700 26px var(--mono); color:var(--accent); font-variant-numeric:tabular-nums;">৳ {{ number_format($sale->net_amount, 2) }}</span>
                </div>
            </div>

            {{-- Sale Details --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
                <div style="padding:14px 20px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-size:13px; font-weight:600;">Sale Details</h3>
                    <span style="font:11px var(--mono); color:var(--ink-3);">BDT (৳)</span>
                </div>

                @if($sale->sale_type === 'rent')
                    {{-- ── RENT ── --}}
                    <div style="padding:16px 20px; display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div style="background:var(--canvas); border-radius:8px; padding:12px 14px;">
                            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Monthly Rent</div>
                            <div style="font:600 16px var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format($sale->propertyUnit->rent_amount ?? 0, 2) }}</div>
                        </div>
                        <div style="background:var(--canvas); border-radius:8px; padding:12px 14px;">
                            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Security Deposit</div>
                            <div style="font:600 16px var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format($sale->security_deposit_amount ?? 0, 2) }}</div>
                        </div>
                        <div style="background:var(--canvas); border-radius:8px; padding:12px 14px;">
                            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Service Charge</div>
                            <div style="font:600 16px var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format($sale->propertyUnit->service_charge ?? 0, 2) }}</div>
                        </div>
                        @if($sale->rent_start_date || $sale->rent_end_date)
                            <div style="background:var(--canvas); border-radius:8px; padding:12px 14px;">
                                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Rent Period</div>
                                <div style="font:500 12px var(--mono); color:var(--ink-1);">
                                    {{ $sale->rent_start_date?->format('d M Y') ?? '--' }} &rarr; {{ $sale->rent_end_date?->format('d M Y') ?? '--' }}
                                </div>
                            </div>
                        @endif
                    </div>
                    <div style="padding:12px 20px; background:#F5F2E8; display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--rule);">
                        <span style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Move-in Cost (Deposit + Service Charge)</span>
                        <span style="font:700 18px var(--mono); color:var(--accent); font-variant-numeric:tabular-nums;">৳ {{ number_format(($sale->security_deposit_amount ?? 0) + ($sale->propertyUnit->service_charge ?? 0), 2) }}</span>
                    </div>

                @else
                    {{-- ── SALE (multi-unit) ── --}}
                    @php
                        // Prefer per-unit breakdown rows; fall back to the primary unit for
                        // legacy sales created before multi-unit support.
                        $units = $sale->saleUnits;
                        if ($units->isEmpty() && $sale->propertyUnit) {
                            $units = collect([(object) [
                                'propertyUnit'    => $sale->propertyUnit,
                                'sale_amount'     => $sale->sale_amount,
                                'discount_amount' => $sale->discount_amount,
                                'tax_amount'      => $sale->tax_amount,
                                'net_amount'      => $sale->net_amount,
                                'service_charge'  => $sale->propertyUnit->service_charge ?? 0,
                                'utility_charge'  => $sale->propertyUnit->utility_charge ?? 0,
                            ]]);
                        }
                        $serviceTotal = (float) $units->sum('service_charge');
                        $utilityTotal = (float) $units->sum('utility_charge');
                        $finalTotal   = (float) $sale->net_amount + $serviceTotal + $utilityTotal;
                    @endphp

                    {{-- Per-unit breakdown --}}
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                            <thead>
                                <tr style="background:var(--canvas);">
                                    <th style="padding:8px 16px; text-align:left;  font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Unit</th>
                                    <th style="padding:8px 12px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Sale</th>
                                    <th style="padding:8px 12px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--rj-fg); border-bottom:1px solid var(--rule);">Discount</th>
                                    <th style="padding:8px 12px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Tax</th>
                                    <th style="padding:8px 12px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Service</th>
                                    <th style="padding:8px 12px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Utility</th>
                                    <th style="padding:8px 16px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($units as $u)
                                    @php $pu = $u->propertyUnit; @endphp
                                    <tr style="border-bottom:1px solid var(--rule);">
                                        <td style="padding:10px 16px;">
                                            <div style="font:600 12.5px var(--mono); color:var(--accent);">{{ $pu?->effective_code ?? '—' }}</div>
                                            <div style="font:11px 'Inter', sans-serif; color:var(--ink-3); margin-top:1px;">
                                                {{ $pu?->property?->name ?? '—' }}@if($pu?->effective_type) · {{ ucfirst($pu->effective_type) }}@endif
                                            </div>
                                        </td>
                                        <td style="padding:10px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format((float) $u->sale_amount, 2) }}</td>
                                        <td style="padding:10px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums; color:{{ (float) $u->discount_amount > 0 ? 'var(--rj-fg)' : 'var(--ink-3)' }};">{{ (float) $u->discount_amount > 0 ? '− ' : '' }}৳ {{ number_format((float) $u->discount_amount, 2) }}</td>
                                        <td style="padding:10px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format((float) $u->tax_amount, 2) }}</td>
                                        <td style="padding:10px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format((float) $u->service_charge, 2) }}</td>
                                        <td style="padding:10px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format((float) ($u->utility_charge ?? 0), 2) }}</td>
                                        <td style="padding:10px 16px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums; font-weight:600;">৳ {{ number_format((float) $u->net_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @if($units->count() > 1)
                                <tfoot>
                                    <tr style="background:var(--canvas);">
                                        <td style="padding:9px 16px; font:600 10px 'Inter', sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-2);">{{ $units->count() }} units · total</td>
                                        <td style="padding:9px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums; font-weight:600;">৳ {{ number_format((float) $sale->sale_amount, 2) }}</td>
                                        <td style="padding:9px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums; font-weight:600; color:var(--rj-fg);">− ৳ {{ number_format((float) $sale->discount_amount, 2) }}</td>
                                        <td style="padding:9px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums; font-weight:600;">৳ {{ number_format((float) $sale->tax_amount, 2) }}</td>
                                        <td style="padding:9px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums; font-weight:600;">৳ {{ number_format($serviceTotal, 2) }}</td>
                                        <td style="padding:9px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums; font-weight:600;">৳ {{ number_format($utilityTotal, 2) }}</td>
                                        <td style="padding:9px 16px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums; font-weight:700; color:var(--accent);">৳ {{ number_format((float) $sale->net_amount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                    {{-- Down payment + combined service + utility --}}
                    <div style="border-top:1px solid var(--rule); display:grid; grid-template-columns:1fr 1fr 1fr; gap:1px; background:var(--rule);">
                        <div style="background:var(--paper); padding:12px 20px;">
                            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Down Payment</div>
                            <div style="font:600 16px var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format($sale->down_payment_amount ?? 0, 2) }}@if($sale->down_payment_percentage)<span style="font:500 11px var(--mono); color:var(--ink-3);"> ({{ number_format($sale->down_payment_percentage, 2) }}%)</span>@endif</div>
                        </div>
                        <div style="background:var(--paper); padding:12px 20px;">
                            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Service Charge</div>
                            <div style="font:600 16px var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format($serviceTotal, 2) }}</div>
                        </div>
                        <div style="background:var(--paper); padding:12px 20px;">
                            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Utility Charge</div>
                            <div style="font:600 16px var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format($utilityTotal, 2) }}</div>
                        </div>
                    </div>
                    <div style="border-top:1px solid var(--rule);">
                        <div style="padding:10px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--rule);">
                            <span style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Initial Commitment (Down Payment + Service + Utility)</span>
                            <span style="font:600 14px var(--mono); color:var(--ink-2); font-variant-numeric:tabular-nums;">৳ {{ number_format(($sale->down_payment_amount ?? 0) + $serviceTotal + $utilityTotal, 2) }}</span>
                        </div>
                        <div style="padding:12px 20px; background:#F5F2E8; display:flex; justify-content:space-between; align-items:center;">
                            <span style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-2);">Final Amount (Net + Service + Utility)</span>
                            <span style="font:700 20px var(--mono); color:var(--accent); font-variant-numeric:tabular-nums;">৳ {{ number_format($finalTotal, 2) }}</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Timeline / Dates --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <h3 style="margin:0 0 14px; font-size:13px; font-weight:600;">Key Dates</h3>
                <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:12px;">
                    @php
                        $dates = [
                            ['label' => 'Sale Date',     'value' => $sale->sale_date,     'accent' => false],
                            ['label' => 'Contract Date', 'value' => $sale->contract_date, 'accent' => false],
                            ['label' => 'Created',       'value' => $sale->created_at,    'accent' => false],
                            ['label' => 'Last Updated',  'value' => $sale->updated_at,    'accent' => false],
                        ];
                    @endphp
                    @foreach($dates as $d)
                        <div style="padding:12px 14px; background:var(--canvas); border-radius:8px;">
                            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">{{ $d['label'] }}</div>
                            <div style="font:500 13px var(--mono); color:var(--ink-1);">
                                {{ $d['value'] ? $d['value']->format('d M Y') : '—' }}
                            </div>
                            @if($d['value'])
                                <div style="font:11px var(--mono); color:var(--ink-3); margin-top:2px;">{{ $d['value']->format('H:i') !== '00:00' ? $d['value']->format('H:i') : '' }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Payment Schedule --}}
            @php
                $schedules       = $sale->paymentSchedules;
                $totalScheduled  = $schedules->sum('amount');
                $totalPaid       = $schedules->sum('paid_amount');
                $totalDue        = $schedules->sum('due_amount');
                $schedStatusColors = [
                    'pending'  => ['bg'=>'var(--bk-bg)','fg'=>'var(--bk-fg)'],
                    'partial'  => ['bg'=>'var(--sd-bg)','fg'=>'var(--sd-fg)'],
                    'paid'     => ['bg'=>'var(--av-bg)','fg'=>'var(--av-fg)'],
                    'overdue'  => ['bg'=>'var(--rj-bg)','fg'=>'var(--rj-fg)'],
                ];
            @endphp
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
                <div style="padding:14px 20px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-size:13px; font-weight:600;">Payment Schedule</h3>
                    @can('property_sale.edit')
                        <button wire:click="openAddSchedule"
                            style="appearance:none; border:1px solid var(--rule); background:transparent; color:var(--ink-2);
                                   padding:5px 11px; font:500 11.5px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                                   display:inline-flex; align-items:center; gap:5px;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Entry
                        </button>
                    @endcan
                </div>

                {{-- Summary strip --}}
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1px; background:var(--rule);">
                    <div style="background:var(--paper); padding:12px 16px;">
                        <div style="font:600 9.5px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Scheduled</div>
                        <div style="font:600 16px var(--mono); font-variant-numeric:tabular-nums;">৳ {{ number_format($totalScheduled, 2) }}</div>
                    </div>
                    <div style="background:var(--paper); padding:12px 16px;">
                        <div style="font:600 9.5px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--av-fg); margin-bottom:4px;">Paid</div>
                        <div style="font:600 16px var(--mono); color:var(--av-fg); font-variant-numeric:tabular-nums;">৳ {{ number_format($totalPaid, 2) }}</div>
                    </div>
                    <div style="background:var(--paper); padding:12px 16px;">
                        <div style="font:600 9.5px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--rj-fg); margin-bottom:4px;">Due</div>
                        <div style="font:600 16px var(--mono); color:{{ $totalDue > 0 ? 'var(--rj-fg)' : 'var(--ink-3)' }}; font-variant-numeric:tabular-nums;">৳ {{ number_format($totalDue, 2) }}</div>
                    </div>
                </div>

                {{-- Schedule rows --}}
                @if($schedules->isEmpty())
                    <div style="padding:28px 20px; text-align:center; color:var(--ink-3); font-size:13px;">
                        No payment schedule entries yet.
                        @can('property_sale.edit')
                            <button wire:click="openAddSchedule" style="background:none; border:none; color:var(--accent); cursor:pointer; font-size:13px; text-decoration:underline; padding:0 0 0 4px;">Add the first entry.</button>
                        @endcan
                    </div>
                @else
                    <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                        <thead>
                            <tr style="background:var(--canvas);">
                                <th style="padding:8px 16px; text-align:left; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Description</th>
                                <th style="padding:8px 12px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Due Date</th>
                                <th style="padding:8px 12px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Amount</th>
                                <th style="padding:8px 12px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Paid</th>
                                <th style="padding:8px 12px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Due</th>
                                <th style="padding:8px 12px; text-align:center; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Status</th>
                                <th style="padding:8px 12px; text-align:center; border-bottom:1px solid var(--rule);"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($schedules as $sched)
                                @php
                                    $isOverdue = $sched->status === 'pending' && $sched->due_date->isPast();
                                    $displayStatus = $isOverdue ? 'overdue' : $sched->status;
                                    $sc2 = $schedStatusColors[$displayStatus] ?? $schedStatusColors['pending'];
                                @endphp
                                <tr style="border-bottom:1px solid var(--rule);" class="hover:bg-black/1.5">
                                    <td style="padding:10px 16px; font-weight:500;">
                                        {{ $sched->label() }}
                                        @if($sched->remarks)
                                            <div style="font:11px 'Inter', sans-serif; color:var(--ink-3); margin-top:2px;">{{ $sched->remarks }}</div>
                                        @endif
                                    </td>
                                    <td style="padding:10px 12px; text-align:right; font-family:var(--mono); color:var(--ink-2);">
                                        {{ $sched->due_date->format('d M Y') }}
                                    </td>
                                    <td style="padding:10px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums;">
                                        ৳ {{ number_format($sched->amount, 2) }}
                                    </td>
                                    <td style="padding:10px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums; color:var(--av-fg);">
                                        ৳ {{ number_format($sched->paid_amount, 2) }}
                                    </td>
                                    <td style="padding:10px 12px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums; color:{{ $sched->due_amount > 0 ? 'var(--rj-fg)' : 'var(--ink-3)' }};">
                                        ৳ {{ number_format($sched->due_amount, 2) }}
                                    </td>
                                    <td style="padding:10px 12px; text-align:center;">
                                        <span style="padding:2px 8px; border-radius:999px; background:{{ $sc2['bg'] }}; color:{{ $sc2['fg'] }}; font:600 9.5px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                                            {{ ucfirst($displayStatus) }}
                                        </span>
                                    </td>
                                    <td style="padding:10px 12px; text-align:center;">
                                        @can('property_sale.edit')
                                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                                @if($sched->status !== 'paid')
                                                    <button @click="payNowModalOpen = true; $wire.OpenPayNowModal({{ $sched->id }})"
                                                        title="Pay Now"
                                                        style="appearance:none; border:none; background:var(--av-bg); color:var(--av-fg);
                                                               padding:4px 10px; font:600 10.5px 'Inter', sans-serif; border-radius:6px; cursor:pointer; white-space:nowrap;
                                                               display:inline-flex; align-items:center; gap:4px; letter-spacing:.01em;">
                                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                                        Pay Now
                                                    </button>
                                                @else
                                                    <button title="Payment Details" @click="payNowModalOpen = true; $wire.OpenPayNowModal({{ $sched->id }})"
                                                        style="appearance:none; border:none; background:var(--av-bg); color:var(--av-fg);
                                                               padding:4px 10px; font:600 10.5px 'Inter', sans-serif; border-radius:6px; cursor:pointer; white-space:nowrap;
                                                               display:inline-flex; align-items:center; gap:4px; letter-spacing:.01em;">
                                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                                        Pay Details
                                                    </button>
                                                @endif
                                                <button wire:click="openEditSchedule({{ $sched->id }})"
                                                    title="Edit"
                                                    style="appearance:none; border:1px solid var(--rule); background:transparent; color:var(--ink-2);
                                                           width:26px; height:26px; border-radius:5px; cursor:pointer;
                                                           display:inline-flex; align-items:center; justify-content:center;">
                                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                </button>
                                                <button wire:click="deleteSchedule({{ $sched->id }})"
                                                    wire:confirm="Remove this schedule entry?"
                                                    title="Delete"
                                                    style="appearance:none; border:1px solid var(--rule); background:transparent; color:var(--rj-fg);
                                                           width:26px; height:26px; border-radius:5px; cursor:pointer;
                                                           display:inline-flex; align-items:center; justify-content:center;">
                                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                                </button>
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Notes --}}
            @if($sale->notes)
                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <h3 style="margin:0 0 10px; font-size:13px; font-weight:600;">Notes</h3>
                    <p style="margin:0; font-size:13px; color:var(--ink-2); line-height:1.6; white-space:pre-wrap;">{{ $sale->notes }}</p>
                </div>
            @endif

            {{-- Audit --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <h3 style="margin:0 0 12px; font-size:13px; font-weight:600;">Audit</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div style="padding:10px 14px; background:var(--canvas); border-radius:8px;">
                        <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Created By</div>
                        <div style="font:500 13px 'Inter', sans-serif;">{{ $sale->createdByUser?->name ?? '—' }}</div>
                        <div style="font:11px var(--mono); color:var(--ink-3); margin-top:2px;">{{ $sale->created_at->format('d M Y, H:i') }}</div>
                    </div>
                    <div style="padding:10px 14px; background:var(--canvas); border-radius:8px;">
                        <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Last Updated By</div>
                        <div style="font:500 13px 'Inter', sans-serif;">{{ $sale->updatedByUser?->name ?? '—' }}</div>
                        <div style="font:11px var(--mono); color:var(--ink-3); margin-top:2px;">{{ $sale->updated_at->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── RIGHT COLUMN ── --}}
        <div style="display:flex; flex-direction:column; gap:16px;">

            {{-- Property Unit(s) --}}
            @php
                $unitStatusColors = [
                    'available' => ['bg'=>'var(--av-bg)','fg'=>'var(--av-fg)'],
                    'booked'    => ['bg'=>'var(--bk-bg)','fg'=>'var(--bk-fg)'],
                    'sold'      => ['bg'=>'var(--rj-bg)','fg'=>'var(--rj-fg)'],
                    'rented'    => ['bg'=>'var(--sd-bg)','fg'=>'var(--sd-fg)'],
                ];
                $saleUnitRows = $sale->saleUnits;
                $isMultiUnit  = $saleUnitRows->count() > 1;
            @endphp
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
                <div style="padding:12px 16px; border-bottom:1px solid var(--rule); background:rgba(0,0,0,.012); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-3);">{{ $isMultiUnit ? 'Units in this invoice' : 'Property Unit' }}</h3>
                    @if($isMultiUnit)
                        <span style="font:600 10px var(--mono); color:var(--ink-3); background:var(--canvas); border-radius:999px; padding:2px 8px;">{{ $saleUnitRows->count() }}</span>
                    @endif
                </div>

                @if($isMultiUnit)
                    {{-- Multiple units — compact list across (possibly different) properties --}}
                    <div style="display:flex; flex-direction:column;">
                        @foreach($saleUnitRows as $su)
                            @php
                                $pu = $su->propertyUnit;
                                $uc = $unitStatusColors[$pu?->effective_status] ?? $unitStatusColors['available'];
                            @endphp
                            <div style="padding:12px 16px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                                <div style="min-width:0;">
                                    <div style="font:700 13px var(--mono); color:var(--accent);">{{ $pu?->effective_code ?? '—' }}</div>
                                    <div style="font:500 12px 'Inter', sans-serif; color:var(--ink-1); margin-top:2px;">{{ $pu?->property?->name ?? '—' }}</div>
                                    <div style="font:11px 'Inter', sans-serif; color:var(--ink-3); margin-top:1px;">
                                        <span style="text-transform:capitalize;">{{ $pu?->effective_type ?? '—' }}</span>@if($pu?->effective_area) · {{ number_format($pu->effective_area, 0) }} sqft @endif@if($pu?->floor) · {{ $pu->floor->label }} @endif
                                    </div>
                                </div>
                                <div style="text-align:right; flex-shrink:0;">
                                    @if($pu)
                                        <span style="padding:2px 8px; border-radius:999px; background:{{ $uc['bg'] }}; color:{{ $uc['fg'] }}; font:600 9.5px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">{{ ucfirst($pu->effective_status) }}</span>
                                    @endif
                                    <div style="font:600 12px var(--mono); color:var(--ink-1); margin-top:5px; font-variant-numeric:tabular-nums;">৳ {{ number_format((float) $su->net_amount, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif($sale->propertyUnit)
                    {{-- Single unit — full detail --}}
                    <div style="padding:16px;">
                        <div style="font:700 16px var(--mono); color:var(--accent); margin-bottom:4px;">{{ $sale->propertyUnit->effective_code }}</div>
                        <div style="font:500 13px 'Inter', sans-serif; color:var(--ink-1); margin-bottom:8px;">{{ $sale->propertyUnit->property?->name ?? '—' }}</div>
                        <div style="display:flex; flex-direction:column; gap:6px; font-size:12.5px;">
                            <div style="display:flex; justify-content:space-between;">
                                <span style="color:var(--ink-3);">Type</span>
                                <span style="font-weight:500; text-transform:capitalize;">{{ $sale->propertyUnit->effective_type }}</span>
                            </div>
                            @if($sale->propertyUnit->effective_area)
                                <div style="display:flex; justify-content:space-between;">
                                    <span style="color:var(--ink-3);">Area</span>
                                    <span style="font:500 12.5px var(--mono);">{{ number_format($sale->propertyUnit->effective_area, 0) }} sqft</span>
                                </div>
                            @endif
                            @if($sale->propertyUnit->floor)
                                <div style="display:flex; justify-content:space-between;">
                                    <span style="color:var(--ink-3);">Floor</span>
                                    <span style="font-weight:500;">{{ $sale->propertyUnit->floor->label }}</span>
                                </div>
                            @endif
                            @php $uc = $unitStatusColors[$sale->propertyUnit->effective_status] ?? $unitStatusColors['available']; @endphp
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="color:var(--ink-3);">Unit Status</span>
                                <span style="padding:2px 8px; border-radius:999px; background:{{ $uc['bg'] }}; color:{{ $uc['fg'] }}; font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                                    {{ ucfirst($sale->propertyUnit->effective_status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <div style="padding:16px; color:var(--ink-3); font-size:13px;">Unit not found.</div>
                @endif
            </div>

            {{-- Customer --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
                <div style="padding:12px 16px; border-bottom:1px solid var(--rule); background:rgba(0,0,0,.012);">
                    <h3 style="margin:0; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-3);">Customer</h3>
                </div>
                <div style="padding:16px;">
                    @if($sale->customer)
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                            <div style="width:38px; height:38px; border-radius:50%; background:var(--accent); color:#fff;
                                        display:flex; align-items:center; justify-content:center;
                                        font:700 14px 'Inter', sans-serif; flex-shrink:0;">
                                {{ $sale->customer->initials() }}
                            </div>
                            <div>
                                <div style="font:600 14px 'Inter', sans-serif;">{{ $sale->customer->name }}</div>
                                <div style="font:11px var(--mono); color:var(--ink-3);">{{ $sale->customer->customer_id }}</div>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:6px; font-size:12.5px;">
                            @if($sale->customer->phone)
                                <div style="display:flex; justify-content:space-between;">
                                    <span style="color:var(--ink-3);">Phone</span>
                                    <span style="font:500 12.5px var(--mono);">{{ $sale->customer->phone }}</span>
                                </div>
                            @endif
                            @if($sale->customer->email)
                                <div style="display:flex; justify-content:space-between; gap:8px;">
                                    <span style="color:var(--ink-3); flex-shrink:0;">Email</span>
                                    <span style="font-weight:500; text-align:right; word-break:break-all;">{{ $sale->customer->email }}</span>
                                </div>
                            @endif
                            @if($sale->customer->address)
                                <div style="display:flex; justify-content:space-between; gap:8px;">
                                    <span style="color:var(--ink-3); flex-shrink:0;">Address</span>
                                    <span style="font-weight:500; text-align:right;">{{ $sale->customer->address }}</span>
                                </div>
                            @endif
                            @php
                                $kycColors = [
                                    'verified' => ['bg'=>'var(--av-bg)','fg'=>'var(--av-fg)'],
                                    'pending'  => ['bg'=>'var(--bk-bg)','fg'=>'var(--bk-fg)'],
                                    'rejected' => ['bg'=>'var(--rj-bg)','fg'=>'var(--rj-fg)'],
                                ];
                                $kc = $kycColors[$sale->customer->kyc_status] ?? $kycColors['pending'];
                            @endphp
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="color:var(--ink-3);">KYC</span>
                                <span style="padding:2px 8px; border-radius:999px; background:{{ $kc['bg'] }}; color:{{ $kc['fg'] }}; font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                                    {{ ucfirst($sale->customer->kyc_status) }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div style="color:var(--ink-3); font-size:13px;">Customer not found.</div>
                    @endif
                </div>
            </div>

            {{-- Sale Details --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
                <div style="padding:12px 16px; border-bottom:1px solid var(--rule); background:rgba(0,0,0,.012);">
                    <h3 style="margin:0; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-3);">Sale Details</h3>
                </div>
                <div style="padding:16px; display:flex; flex-direction:column; gap:8px; font-size:12.5px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--ink-3);">Sale Type</span>
                        @if($sale->sale_type === 'rent')
                            <span style="padding:2px 9px; border-radius:999px; background:var(--rt-bg); color:var(--rt-fg); font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">Rent</span>
                        @else
                            <span style="padding:2px 9px; border-radius:999px; background:var(--sd-bg); color:var(--sd-fg); font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">Sale</span>
                        @endif
                    </div>
                    @if($sale->payment_terms)
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--ink-3);">Payment Terms</span>
                            <span style="font:500 12.5px var(--mono);">{{ $sale->payment_terms }} days</span>
                        </div>
                    @endif
                    @if($sale->sales_representative)
                        <div style="display:flex; justify-content:space-between; gap:8px;">
                            <span style="color:var(--ink-3); flex-shrink:0;">Sales Rep</span>
                            <span style="font-weight:500; text-align:right;">{{ $sale->sales_representative }}</span>
                        </div>
                    @endif
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--ink-3);">Payment</span>
                        <span style="padding:3px 9px; border-radius:999px; background:{{ $pc['bg'] }}; color:{{ $pc['fg'] }}; font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                            {{ ucfirst($sale->payment_status) }}
                        </span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--ink-3);">Status</span>
                        <span style="padding:3px 9px; border-radius:999px; background:{{ $sc['bg'] }}; color:{{ $sc['fg'] }}; font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                            {{ ucwords(str_replace('_', ' ', $sale->status)) }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ─── SCHEDULE DRAWER SCRIM ─────────────────────────────────────────── --}}
    <div
        x-show="scheduleDrawerOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="$wire.closeScheduleDrawer()"
        style="position:fixed; inset:0; background:rgba(20,18,16,.45); backdrop-filter:blur(4px); z-index:50;"
        x-cloak
    ></div>

    {{-- ─── SCHEDULE DRAWER ─────────────────────────────────────────────────── --}}
    <aside
        x-show="scheduleDrawerOpen"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="transform translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform translate-x-full"
        style="position:fixed; top:0; right:0; bottom:0; width:520px; max-width:100vw;
               background:var(--canvas); z-index:51; display:flex; flex-direction:column;
               box-shadow:-20px 0 40px -20px rgba(0,0,0,.25);"
        x-cloak
    >
        {{-- Head --}}
        <div style="padding:18px 24px; border-bottom:1px solid var(--rule); background:var(--paper);
                    display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:16px; font-weight:600;">
                    {{ $editingScheduleId ? 'Edit Schedule Entry' : 'Add Schedule Entry' }}
                </h3>
                <div style="margin-top:2px; font:500 11px var(--mono); color:var(--ink-3); letter-spacing:.04em; text-transform:uppercase;">
                    {{ $sale->sale_number }}
                </div>
            </div>
            <button @click="$wire.closeScheduleDrawer()"
                style="appearance:none; border:0; background:transparent; color:var(--ink-2);
                       width:32px; height:32px; border-radius:6px; cursor:pointer;
                       display:flex; align-items:center; justify-content:center;"
                class="hover:bg-black/5 transition-colors">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div style="flex:1; min-height:0; overflow-y:auto; padding:24px; display:flex; flex-direction:column; gap:18px;">

            {{-- Category & Sequence --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <h4 style="margin:0 0 14px; font-size:13px; font-weight:600;">Payment Type</h4>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Category <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <select wire:model.live="sPaymentCategory"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                            <option value="down_payment">Down Payment</option>
                            <option value="installment">Installment</option>
                            <option value="monthly_rent">Monthly Rent</option>
                            <option value="security_deposit">Security Deposit</option>
                        </select>
                        @error('sPaymentCategory') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Sequence No.</label>
                        <input wire:model="sSequenceNo" type="number" min="1" placeholder="e.g. 1"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                    </div>
                </div>
            </div>

            {{-- Date & Status --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <h4 style="margin:0 0 14px; font-size:13px; font-weight:600;">Schedule</h4>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                        Due Date <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <input wire:model="sDueDate" type="date"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" class="flatpickr-only-date" />
                    @error('sDueDate') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Financial --}}
            <div style="background:#F5F2E8; border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:14px;">
                    <h4 style="margin:0; font-size:13px; font-weight:600;">Amount</h4>
                    <span style="font:11px var(--mono); color:var(--ink-3);">BDT (৳)</span>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Amount <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <input wire:model="sAmount" type="number" min="0" step="0.01" placeholder="0.00"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        @error('sAmount') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Paid Amount</label>
                        <input wire:model="sPaidAmount" type="number" min="0" step="0.01" placeholder="0.00"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                    </div>
                </div>
                <div style="margin-top:10px; padding:10px 14px; background:var(--paper); border:1px solid var(--rule); border-radius:7px; font:11.5px 'Inter', sans-serif; color:var(--ink-3);">
                    Status is auto-set: 0 paid = <strong>Pending</strong> · partial = <strong>Partial</strong> · full = <strong>Paid</strong>
                </div>
            </div>

            {{-- Remarks --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <h4 style="margin:0 0 12px; font-size:13px; font-weight:600;">Remarks</h4>
                <textarea wire:model="sRemarks" placeholder="Optional notes for this entry…" rows="3"
                    style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif; resize:vertical; min-height:72px;"></textarea>
            </div>
        </div>

        {{-- Footer --}}
        <div style="border-top:1px solid var(--rule); background:var(--paper);">
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
                <button @click="$wire.closeScheduleDrawer()"
                    style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2);
                           padding:7px 16px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;">
                    Cancel
                </button>
                <button wire:click="saveSchedule"
                    wire:loading.attr="disabled"
                    style="appearance:none; border:1px solid var(--accent); background:var(--accent); color:#fff;
                           padding:7px 18px; font:600 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                           display:inline-flex; align-items:center; gap:6px;">
                    <span wire:loading.remove wire:target="saveSchedule" style="display:inline-flex; align-items:center; gap:6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ $editingScheduleId ? 'Update Entry' : 'Add Entry' }}
                    </span>
                    <span wire:loading wire:target="saveSchedule">Saving…</span>
                </button>
            </div>
        </div>
    </aside>

    {{-- ─── DRAWER SCRIM ────────────────────────────────────────────────────── --}}
    <div
        x-show="drawerOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="drawerOpen = false"
        style="position:fixed; inset:0; background:rgba(20,18,16,.45); backdrop-filter:blur(4px); z-index:50;"
        x-cloak
    ></div>

    {{-- ─── EDIT DRAWER ─────────────────────────────────────────────────────── --}}
    <aside
        x-show="drawerOpen"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="transform translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform translate-x-full"
        x-cloak
        style="position:fixed; top:0; right:0; bottom:0; width:680px; max-width:100vw; z-index:51;"
    >
        <div style="width:100%; height:100%; display:flex; flex-direction:column; overflow:hidden;
                    background:var(--canvas); box-shadow:-20px 0 40px -20px rgba(0,0,0,.25);">

        {{-- Drawer Head --}}
        <div style="padding:18px 24px; border-bottom:1px solid var(--rule); background:var(--paper);
                    display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
            <div>
                <h3 style="margin:0; font-size:16px; font-weight:600;">Edit Sale</h3>
                <div style="margin-top:2px; font:500 11px var(--mono); color:var(--ink-3); letter-spacing:.04em; text-transform:uppercase;">
                    Update sale details
                </div>
            </div>
            <button @click="drawerOpen = false"
                style="appearance:none; border:0; background:transparent; color:var(--ink-2);
                       width:32px; height:32px; border-radius:6px; cursor:pointer;
                       display:flex; align-items:center; justify-content:center;"
                class="hover:bg-black/5 transition-colors">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>


        {{-- Drawer Body --}}
        <div style="flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; padding:24px; display:flex; flex-direction:column; gap:18px;">

            {{-- 1. Property & Customer --}}
            <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:14px;">
                    <h4 style="margin:0; font-size:13px; font-weight:600;">Property &amp; Customer</h4>
                    <span style="font:11px var(--mono); color:var(--ink-3);">required</span>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Property Unit <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <select wire:model="dPropertyUnitId"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                            <option value="">— Select unit —</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">
                                    {{ $unit->property?->name ?? 'No Property' }} — {{ $unit->code ?? $unit->unit_number }} ({{ $unit->type ?? $unit->unit_type ?? '' }})
                                </option>
                            @endforeach
                        </select>
                        @error('dPropertyUnitId') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                    <div>
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

            {{-- 2. Dates --}}
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

            {{-- 3. Financial --}}
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
                <div style="margin-top:14px; padding:12px 16px; background:var(--paper); border:1.5px solid var(--accent); border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font:600 11px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Net Amount</span>
                    <span style="font:700 20px var(--mono); color:var(--accent); font-variant-numeric:tabular-nums;">
                        ৳ {{ number_format((float)$dNetAmount, 2) }}
                    </span>
                </div>
            </div>

            {{-- 4. Sale Details --}}
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

            {{-- 5. Sales Rep & Notes --}}
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
                        <textarea wire:model="dNotes" placeholder="Internal notes…" rows="3"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif; resize:vertical; min-height:72px;"></textarea>
                    </div>
                </div>
            </div>

        </div>

        {{-- Drawer Footer --}}
        <div style="border-top:1px solid var(--rule); background:var(--paper); flex-shrink:0;">
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
                           padding:7px 16px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;">
                    Cancel
                </button>
                <button wire:click="savePropertySale"
                    wire:loading.attr="disabled" wire:target="savePropertySale"
                    style="appearance:none; border:1px solid var(--accent); background:var(--accent); color:#fff;
                           padding:7px 18px; font:600 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                           display:inline-flex; align-items:center; gap:6px;">
                    <span wire:loading.remove wire:target="savePropertySale" style="display:contents;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Update Sale
                    </span>
                    <span wire:loading.flex wire:target="savePropertySale" style="align-items:center; gap:6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        Saving…
                    </span>
                </button>
            </div>
        </div>
        </div>{{-- /flex layer --}}
    </aside>



    {{-- ─── PAY NOW MODAL ───────────────────────────────────────────────────── --}}
    <x-modal wire:model="payNowModalOpen" maxWidth="lg" focusable>

            {{-- Header --}}
            <div style="padding:18px 22px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:30px; height:30px; border-radius:8px; background:var(--av-bg); color:var(--av-fg); display:flex; align-items:center; justify-content:center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    </div>
                    <div>
                        <h3 style="margin:0; font-size:15px; font-weight:600; color:var(--ink-1);">Pay Now</h3>
                        <div style="font:500 10.5px var(--mono); color:var(--ink-3); letter-spacing:.04em; text-transform:uppercase; margin-top:1px;">{{ $sale->sale_number }}</div>
                    </div>
                </div>
                <button @click="payNowModalOpen = false"
                    style="appearance:none; border:0; background:transparent; color:var(--ink-2); width:30px; height:30px; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div style="padding:20px 22px; display:flex; flex-direction:column; gap:14px; position:relative; min-height:120px;">

                {{-- Loading overlay --}}
                <div wire:loading.flex wire:target="OpenPayNowModal"
                    style="position:absolute; inset:0; background:var(--canvas); z-index:10; border-radius:0 0 14px 14px;
                           align-items:center; justify-content:center; gap:10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        style="animation:spin 1s linear infinite; color:var(--accent);">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                    <span style="font:500 13px 'Inter', sans-serif; color:var(--ink-2);">Loading…</span>
                </div>

                @if((float)$payNowAmount > 0)
                {{-- Row 1: Account Type + Account --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Receive Account Type <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <select wire:model.live="payNowAccountType"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                            <option value="">— Select type —</option>
                            @foreach(\App\Enums\Accounts\AccountSubType::cases() as $subType)
                                <option value="{{ $subType->value }}">{{ $subType->label() }}</option>
                            @endforeach
                        </select>
                        @error('payNowAccountType') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display:flex; align-items:center; gap:5px; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Account <span style="color:var(--rj-fg)">*</span>
                            <svg wire:loading wire:target="payNowAccountType" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite; color:var(--accent);"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        </label>
                        <select wire:model="payNowAccountId"
                            wire:loading.attr="disabled" wire:target="payNowAccountType"
                            @if(!$payNowAccountType) disabled @endif
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif; {{ !$payNowAccountType ? 'opacity:.5; cursor:not-allowed;' : '' }}">
                            <option value="">— Select account —</option>
                            @foreach($payNowAccounts as $account)
                                <option value="{{ $account['id'] }}">{{ $account['name'] }}</option>
                            @endforeach
                        </select>
                        @error('payNowAccountId') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Row 2: Payment Method + Payer Name --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Payment Method <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <select wire:model="payNowPaymentMethod"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="online">Online</option>
                            <option value="card">Card</option>
                            <option value="other">Other</option>
                        </select>
                        @error('payNowPaymentMethod') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Payer Name</label>
                        <input wire:model="payNowPayerName" type="text" placeholder="e.g. John Doe"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                    </div>
                </div>

                {{-- Row 2b: Reference No + Phone --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Reference No <span style="color:var(--ink-3); font-weight:400;">(Optional)</span></label>
                        <input wire:model="payNowReferenceNo" type="text" placeholder="e.g. Cheque no, TxnID…"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Payer Phone <span style="color:var(--ink-3); font-weight:400;">(Optional)</span></label>
                        <input wire:model="payNowPhone" type="text" placeholder="e.g. +880 1700 000 000"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                    </div>
                </div>

                {{-- Row 3: Amount + Payment Date --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Amount <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <div style="position:relative;">
                            <span style="position:absolute; left:11px; top:50%; transform:translateY(-50%); font:500 13px var(--mono); color:var(--ink-3); pointer-events:none;">৳</span>
                            <input wire:model="payNowAmount" type="number" min="0.01" step="0.01" placeholder="0.00"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1);
                                       padding:9px 12px 9px 26px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        </div>
                        @error('payNowAmount') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                            Payment Date <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <input wire:model="payNowDate" type="text" class="flatpickr" placeholder="Select datetime"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        @error('payNowDate') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Row 4: Notes --}}
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Notes</label>
                    <textarea wire:model="payNowNotes" rows="2" placeholder="Optional payment notes…"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif; resize:vertical;"></textarea>
                </div>

                {{-- Row 5: Attachments --}}
                <div>
                    <x-media-picker-field
                        field="payNowAttachmentIds"
                        :value="$payNowAttachmentIds"
                        label="Attachments"
                        placeholder="Click to select files"
                        :multiple="true"
                        type="all"
                        required="false"
                        :canEdit="true" />
                </div>

                {{-- Validation errors --}}
                @if($errors->hasAny(['payNowAccountType','payNowAccountId','payNowPaymentMethod','payNowDate','payNowAmount']))
                    <div style="padding:10px 14px; background:var(--rj-bg); border:1px solid rgba(122,42,30,.15); border-radius:8px;">
                        <ul style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:3px;">
                            @foreach(collect($errors->getBag('default')->getMessages())->only(['payNowAccountType','payNowAccountId','payNowPaymentMethod','payNowDate','payNowAmount'])->flatten() as $error)
                                <li style="font:500 11.5px 'Inter', sans-serif; color:var(--rj-fg); display:flex; align-items:center; gap:6px;">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @else
                <div style="display:flex; align-items:center; gap:10px; padding:14px 16px; background:var(--av-bg); border-radius:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--av-fg)" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span style="font:600 13px 'Inter', sans-serif; color:var(--av-fg);">This schedule is fully paid — no outstanding balance.</span>
                </div>
                @endif

                {{-- Payment History --}}
                @if(count($payTransactions) > 0)
                    <div style="border-top:1px solid var(--rule); padding-top:14px; display:flex; flex-direction:column; gap:10px;">
                        <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">
                            Payment History ({{ count($payTransactions) }})
                        </div>
                        <div style="border:1px solid var(--rule); border-radius:8px; overflow:hidden;">
                            @foreach($payTransactions as $idx => $tx)
                                <div style="display:flex; align-items:center; gap:10px; padding:10px 14px; {{ $idx > 0 ? 'border-top:1px solid var(--rule);' : '' }} background:var(--paper);">
                                    <div style="flex:1; min-width:0;">
                                        <div style="font:600 12px 'Inter', sans-serif; color:var(--ink-1);">
                                            {{ $tx['name'] ?? '—' }}
                                        </div>
                                        <div style="font:500 10.5px var(--mono); color:var(--ink-3); margin-top:2px; display:flex; align-items:center; gap:5px; flex-wrap:wrap;">
                                            <span>{{ \Carbon\Carbon::parse($tx['datetime'])->format('d M Y h:i A') }}</span>
                                            <span style="opacity:.4">•</span>
                                            <span>{{ ucfirst(str_replace('_', ' ', $tx['method'])) }}</span>
                                            @if(!empty($tx['account']))
                                                <span style="opacity:.4">•</span>
                                                <span>{{ $tx['account']['name'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="font:700 13px var(--mono); color:var(--av-fg); white-space:nowrap; flex-shrink:0;">
                                        ৳ {{ number_format($tx['debit'], 2) }}
                                    </div>
                                    <div style="display:flex; align-items:center; gap:5px; flex-shrink:0;">
                                        <button
                                            @click="receiptTx = {{ json_encode($tx) }}; receiptModalOpen = true"
                                            title="View Receipt"
                                            style="appearance:none; border:1px solid var(--rule); background:transparent; color:var(--ink-2);
                                                   padding:4px 9px; font:500 10.5px 'Inter', sans-serif; border-radius:5px; cursor:pointer;
                                                   display:inline-flex; align-items:center; gap:4px;">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            Receipt
                                        </button>
                                        <a target="_blank" href="{{ route('admin.properties.receipts.show', $tx['id']) }}"
                                            title="View Receipt"
                                            style="appearance:none; border:1px solid var(--rule); background:transparent; color:var(--ink-2);
                                                   padding:4px 9px; font:500 10.5px 'Inter', sans-serif; border-radius:5px; cursor:pointer;
                                                   display:inline-flex; align-items:center; gap:4px;">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            PDF
                                    </a>
                                        @if(!empty($tx['attachments']))
                                            <button
                                                @click="attachList = {{ json_encode($tx['attachments']) }}; attachModalOpen = true"
                                                title="View Attachments"
                                                style="appearance:none; border:1px solid var(--sd-fg); background:var(--sd-bg); color:var(--sd-fg);
                                                       padding:4px 9px; font:500 10.5px 'Inter', sans-serif; border-radius:5px; cursor:pointer;
                                                       display:inline-flex; align-items:center; gap:4px;">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                                {{ count($tx['attachments']) }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

            {{-- Footer --}}
            <div style="padding:14px 22px; border-top:1px solid var(--rule); background:var(--paper); border-radius:0 0 14px 14px;
                        display:flex; justify-content:flex-end; align-items:center; gap:10px;">
                <button @click="payNowModalOpen = false"
                    style="appearance:none; border:1px solid var(--rule); background:transparent; color:var(--ink-2);
                           padding:7px 16px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;">
                    Cancel
                </button>
                @if((float)$payNowAmount > 0)
                <button wire:click="submitPayment"
                    wire:loading.attr="disabled"
                    style="appearance:none; border:1px solid var(--av-fg); background:var(--av-fg); color:#fff;
                           padding:7px 18px; font:600 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                           display:inline-flex; align-items:center; gap:6px;">
                    <span wire:loading.remove wire:target="submitPayment" style="display:inline-flex; align-items:center; gap:6px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Confirm Payment
                    </span>
                    <span wire:loading wire:target="submitPayment">Processing…</span>
                </button>
                @endif
            </div>

    </x-modal>

    {{-- ─── RECEIPT SUB-MODAL ──────────────────────────────────────────────── --}}
    <x-modal wire:model="receiptModalOpen" maxWidth="md" focusable>
            <div style="padding:16px 20px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:28px; height:28px; border-radius:7px; background:var(--av-bg); color:var(--av-fg); display:flex; align-items:center; justify-content:center;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <h3 style="margin:0; font-size:14px; font-weight:600; color:var(--ink-1);">Payment Receipt</h3>
                </div>
                <button @click="receiptModalOpen = false"
                    style="appearance:none; border:0; background:transparent; color:var(--ink-3); cursor:pointer; display:flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div>
                        <div style="font:600 9.5px 'Inter'; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Date</div>
                        <div style="font:500 13px var(--mono); color:var(--ink-1);" x-text="receiptTx.datetime ? new Date(receiptTx.datetime).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) : '—'"></div>
                    </div>
                    <div>
                        <div style="font:600 9.5px 'Inter'; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Amount</div>
                        <div style="font:700 16px var(--mono); color:var(--av-fg);">৳ <span x-text="parseFloat(receiptTx.debit ?? 0).toLocaleString('en-US', { minimumFractionDigits:2, maximumFractionDigits:2 })"></span></div>
                    </div>
                    <div>
                        <div style="font:600 9.5px 'Inter'; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Payment Method</div>
                        <div style="font:500 12.5px 'Inter'; color:var(--ink-1);" x-text="(receiptTx.method ?? '').replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase())"></div>
                    </div>
                    <div>
                        <div style="font:600 9.5px 'Inter'; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Payer Name</div>
                        <div style="font:500 12.5px 'Inter'; color:var(--ink-1);" x-text="receiptTx.name || '—'"></div>
                    </div>
                    <div x-show="receiptTx.account && receiptTx.account.name">
                        <div style="font:600 9.5px 'Inter'; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Account</div>
                        <div style="font:500 12.5px 'Inter'; color:var(--ink-1);" x-text="receiptTx.account ? receiptTx.account.name : '—'"></div>
                    </div>
                    <div x-show="receiptTx.notes">
                        <div style="font:600 9.5px 'Inter'; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">Notes</div>
                        <div style="font:13px 'Inter'; color:var(--ink-2);" x-text="receiptTx.notes"></div>
                    </div>
                </div>
            </div>
            <div style="padding:12px 20px; border-top:1px solid var(--rule); display:flex; justify-content:flex-end;">
                <button @click="receiptModalOpen = false"
                    style="appearance:none; border:1px solid var(--rule); background:transparent; color:var(--ink-2);
                           padding:6px 14px; font:500 12px 'Inter'; border-radius:6px; cursor:pointer;">
                    Close
                </button>
            </div>
    </x-modal>

    {{-- ─── ATTACHMENTS SUB-MODAL ──────────────────────────────────────────── --}}
    <x-modal wire:model="attachModalOpen" maxWidth="md" focusable>
            <div style="padding:16px 20px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:28px; height:28px; border-radius:7px; background:var(--sd-bg); color:var(--sd-fg); display:flex; align-items:center; justify-content:center;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    </div>
                    <h3 style="margin:0; font-size:14px; font-weight:600; color:var(--ink-1);">Attachments</h3>
                </div>
                <button @click="attachModalOpen = false"
                    style="appearance:none; border:0; background:transparent; color:var(--ink-3); cursor:pointer; display:flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:16px 20px; display:flex; flex-direction:column; gap:8px; max-height:350px; overflow-y:auto;">
                <template x-if="attachList.length === 0">
                    <div style="text-align:center; padding:20px; color:var(--ink-3); font:13px 'Inter';">No attachments</div>
                </template>
                <template x-for="(fileId, idx) in attachList" :key="idx">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:9px 12px; border:1px solid var(--rule); border-radius:7px; background:var(--canvas);">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:28px; height:28px; border-radius:6px; background:var(--sd-bg); color:var(--sd-fg); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <span style="font:500 12px 'Inter'; color:var(--ink-1);">File #<span x-text="fileId"></span></span>
                        </div>
                        <a :href="'/admin/files/' + fileId" target="_blank"
                            style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2);
                                   padding:4px 9px; font:500 10.5px 'Inter'; border-radius:5px; cursor:pointer; text-decoration:none;
                                   display:inline-flex; align-items:center; gap:4px;">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            View
                        </a>
                    </div>
                </template>
            </div>
            <div style="padding:12px 20px; border-top:1px solid var(--rule); display:flex; justify-content:flex-end;">
                <button @click="attachModalOpen = false"
                    style="appearance:none; border:1px solid var(--rule); background:transparent; color:var(--ink-2);
                           padding:6px 14px; font:500 12px 'Inter'; border-radius:6px; cursor:pointer;">
                    Close
                </button>
            </div>
    </x-modal>
</div>
