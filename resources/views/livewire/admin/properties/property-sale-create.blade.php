<div
    x-data="{
        dSaleAmount:    $wire.entangle('dSaleAmount'),
        dDiscountAmount:$wire.entangle('dDiscountAmount'),
        dTaxAmount:     $wire.entangle('dTaxAmount'),
        dNetAmount:     $wire.entangle('dNetAmount'),
        dSaleType:      $wire.entangle('dSaleType'),
        dPropertyId:    $wire.entangle('dPropertyId'),
    }"
    x-init="$store.pageName = { name: 'New Sale', slug: 'property-sales' }"
    style="
        --paper:#FCFBF7; --canvas:#F2EFE7;
        --ink-1:#1A1814; --ink-2:#5C5648; --ink-3:#9B9686;
        --rule:#EAE5D9; --accent:#1F3A68;
        --mono:'IBM Plex Mono', ui-monospace, monospace;
        --av-bg:#D2E7D5; --av-fg:#1F5A2C;
        --bk-bg:#F7E6C4; --bk-fg:#7A5418;
        --sd-bg:#D8E4F5; --sd-fg:#1F3D72;
        --rj-bg:#F1D3CE; --rj-fg:#7A2A1E;
        --in-bg:#EFEAE0; --in-fg:#5C5648;
        font-family:'Inter', system-ui, sans-serif;
        color:var(--ink-1); background:var(--canvas);
    "
    class="min-h-screen"
>

    {{-- ─── HEADER ─────────────────────────────────────────────────────── --}}
    <div style="padding:28px 24px 0;" class="flex items-start justify-between gap-6 flex-wrap">
        <div>
            <div style="font-size:11.5px; color:var(--ink-3); font-family:var(--mono); display:flex; gap:6px; align-items:center; margin-bottom:8px;">
                <span>Real Estate</span>
                <span style="opacity:.5">/</span>
                <a href="{{ route('admin.properties.sales.index') }}" style="color:var(--ink-3); text-decoration:none;">Property Sales</a>
                <span style="opacity:.5">/</span>
                <span style="color:var(--ink-1);">New Sale</span>
            </div>
            <div style="font-size:24px; font-weight:600; letter-spacing:-.01em;">New Property Sale</div>
            <div style="margin-top:4px; font-size:13px; color:var(--ink-2);">Record a new property sale agreement.</div>
        </div>
        <div style="display:flex; gap:8px; flex-shrink:0;">
            <a href="{{ route('admin.properties.sales.index') }}"
                style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2);
                       padding:7px 14px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                       display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Sales
            </a>
        </div>
    </div>

    {{-- ─── BODY ────────────────────────────────────────────────────────── --}}
    <div style="padding:24px; max-width:900px; display:flex; flex-direction:column; gap:18px;">

        {{-- 1. Sale Type --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:16px;">
                <h3 style="margin:0; font-size:14px; font-weight:600;">Sale Type</h3>
                <span style="font:11px var(--mono); color:var(--rj-fg);">required</span>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                @foreach(['property_sale' => ['label'=>'Property Sale','icon'=>'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'], 'land_share' => ['label'=>'Land Share','icon'=>'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'], 'rent' => ['label'=>'Rent','icon'=>'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z']] as $val => $meta)
                    <label
                        style="display:flex; flex-direction:column; align-items:center; gap:8px; padding:16px 12px; border-radius:9px; cursor:pointer; text-align:center;
                               border:2px solid {{ $dSaleType === $val ? 'var(--accent)' : 'var(--rule)' }};
                               background:{{ $dSaleType === $val ? 'rgba(31,58,104,.06)' : 'transparent' }};
                               transition:border-color .15s, background .15s;">
                        <input type="radio" wire:model.live="dSaleType" value="{{ $val }}" class="sr-only">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $dSaleType === $val ? 'var(--accent)' : 'var(--ink-3)' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $meta['icon'] }}"/></svg>
                        <span style="font:600 13px 'Inter', sans-serif; color:{{ $dSaleType === $val ? 'var(--accent)' : 'var(--ink-2)' }};">{{ $meta['label'] }}</span>
                    </label>
                @endforeach
            </div>
            @error('dSaleType') <p style="margin-top:8px; font-size:11.5px; color:var(--rj-fg);">{{ $message }}</p> @enderror
        </div>

        {{-- 2. Property & Customer --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:16px;">
                <h3 style="margin:0; font-size:14px; font-weight:600;">Property &amp; Customer</h3>
                <span style="font:11px var(--mono); color:var(--rj-fg);">required</span>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                {{-- Property --}}
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                        Property <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <select wire:model.live="dPropertyId"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font:13px 'Inter', sans-serif;">
                        <option value="">— Select property —</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}">{{ $property->name }} ({{ $property->code }})</option>
                        @endforeach
                    </select>
                    @error('dPropertyId') <p style="margin-top:5px; font-size:11.5px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>

                {{-- Unit --}}
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                        Unit <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <select wire:model="dPropertyUnitId"
                        @if(!$dPropertyId) disabled @endif
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule);
                               background:{{ $dPropertyId ? 'var(--paper)' : 'var(--canvas)' }};
                               color:var(--ink-1); padding:10px 14px; border-radius:7px; font:13px 'Inter', sans-serif;
                               opacity:{{ $dPropertyId ? '1' : '.5' }};">
                        <option value="">{{ $dPropertyId ? '— Select unit —' : '— Select property first —' }}</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">
                                {{ $unit->code ?? $unit->unit_number }}
                                ({{ ucfirst($unit->type ?? $unit->unit_type ?? '') }},
                                {{ ucfirst($unit->status ?? '') }})
                            </option>
                        @endforeach
                    </select>
                    @error('dPropertyUnitId') <p style="margin-top:5px; font-size:11.5px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>

                {{-- Customer (full width) --}}
                <div style="grid-column:span 2;">
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                        Customer <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <select wire:model="dCustomerId"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font:13px 'Inter', sans-serif;">
                        <option value="">— Select customer —</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->customer_id }})</option>
                        @endforeach
                    </select>
                    @error('dCustomerId') <p style="margin-top:5px; font-size:11.5px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- 3. Dates --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <h3 style="margin:0 0 16px; font-size:14px; font-weight:600;">Dates</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Sale Date</label>
                    <input wire:model="dSaleDate" type="date"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Contract Date</label>
                    <input wire:model="dContractDate" type="date"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                </div>
            </div>
        </div>

        {{-- 4. Financial --}}
        <div style="background:#F5F2E8; border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:16px;">
                <h3 style="margin:0; font-size:14px; font-weight:600;">Financial Details</h3>
                <span style="font:11px var(--mono); color:var(--ink-3);">BDT (৳)</span>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                        Sale Amount <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <input wire:model.blur="dSaleAmount" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                    @error('dSaleAmount') <p style="margin-top:5px; font-size:11.5px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Discount</label>
                    <input wire:model.blur="dDiscountAmount" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Tax</label>
                    <input wire:model.blur="dTaxAmount" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                </div>
            </div>
            <div style="margin-top:16px; padding:14px 18px; background:var(--paper); border:2px solid var(--accent); border-radius:9px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:2px;">Net Amount</div>
                    <div style="font:11.5px 'Inter', sans-serif; color:var(--ink-3);">Sale − Discount + Tax</div>
                </div>
                <div style="font:700 28px var(--mono); color:var(--accent); font-variant-numeric:tabular-nums;">
                    ৳ {{ number_format((float)$dNetAmount, 2) }}
                </div>
            </div>
        </div>

        {{-- 5. Sale Details --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <h3 style="margin:0 0 16px; font-size:14px; font-weight:600;">Sale Details</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Payment Terms (days)</label>
                    <input wire:model="dPaymentTerms" type="number" min="0" placeholder="e.g. 30"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                        Payment Status <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <select wire:model="dPaymentStatus"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font:13px 'Inter', sans-serif;">
                        <option value="pending">Pending</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    @error('dPaymentStatus') <p style="margin-top:5px; font-size:11.5px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                        Status <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <select wire:model="dStatus"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font:13px 'Inter', sans-serif;">
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="on_hold">On Hold</option>
                    </select>
                    @error('dStatus') <p style="margin-top:5px; font-size:11.5px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- 6. Sales Rep & Notes --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <h3 style="margin:0 0 16px; font-size:14px; font-weight:600;">Sales Representative &amp; Notes</h3>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Sales Representative</label>
                    <input wire:model="dSalesRepresentative" type="text" placeholder="Name of the sales agent"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Notes</label>
                    <textarea wire:model="dNotes" placeholder="Internal notes about this sale…" rows="4"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font:13px 'Inter', sans-serif; resize:vertical; min-height:90px;"></textarea>
                </div>
            </div>
        </div>

        {{-- Validation Errors --}}
        @if($errors->any())
            <div style="background:var(--rj-bg); border:1px solid rgba(122,42,30,.2); border-radius:10px; padding:16px 20px;">
                <ul style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:5px;">
                    @foreach($errors->all() as $error)
                        <li style="font:500 12px 'Inter', sans-serif; color:var(--rj-fg); display:flex; align-items:center; gap:7px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Action row --}}
        <div style="display:flex; justify-content:flex-end; align-items:center; gap:10px; padding-bottom:60px;">
            <a href="{{ route('admin.properties.sales.index') }}"
                style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2);
                       padding:9px 20px; font:500 13px 'Inter', sans-serif; border-radius:7px; cursor:pointer;
                       display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
                Cancel
            </a>
            <button wire:click="save"
                wire:loading.attr="disabled"
                style="appearance:none; border:1px solid var(--accent); background:var(--accent); color:#fff;
                       padding:9px 24px; font:600 13px 'Inter', sans-serif; border-radius:7px; cursor:pointer;
                       display:inline-flex; align-items:center; gap:8px;">
                <span wire:loading.remove wire:target="save" style="display:inline-flex; align-items:center; gap:8px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Save Sale
                </span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>

    </div>
</div>
