<div
    x-data="{
        saleType:       $wire.entangle('dSaleType'),
        isScheduled:    $wire.entangle('dIsScheduled'),
        dNetAmount:     $wire.entangle('dNetAmount'),
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
        font-family:'Inter', system-ui, sans-serif;
        color:var(--ink-1); background:var(--canvas);
    "
    class="min-h-screen"
>

    {{-- ─── HEADER ──────────────────────────────────────────────────────────── --}}
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
            <div style="margin-top:4px; font-size:13px; color:var(--ink-2);">Record a new property sale or rent agreement.</div>
        </div>
        <a href="{{ route('admin.properties.sales.index') }}"
            style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2);
                   padding:7px 14px; font:500 12px 'Inter', sans-serif; border-radius:6px;
                   display:inline-flex; align-items:center; gap:6px; text-decoration:none; flex-shrink:0;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back
        </a>
    </div>

    {{-- ─── BODY ────────────────────────────────────────────────────────────── --}}
    <div style="padding:24px; max-width:860px; display:flex; flex-direction:column; gap:18px;">

        {{-- ══ 1. SALE TYPE ══ --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:16px;">
                <h3 style="margin:0; font-size:14px; font-weight:600;">Sale Type</h3>
                <span style="font:11px var(--mono); color:var(--rj-fg);">required</span>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; max-width:420px;">
                @foreach(['sale' => ['icon'=>'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z','label'=>'Property Sale','desc'=>'Flat, shop, office, land sale'],
                           'rent' => ['icon'=>'M12 2v6m0 0H6m6 0h6m-6 8v6m-6-6h12','label'=>'Rent / Lease','desc'=>'Monthly rent agreement']] as $val => $meta)
                    <label
                        @click="saleType = '{{ $val }}'; $wire.set('dSaleType', '{{ $val }}')"
                        style="display:flex; flex-direction:column; gap:6px; padding:14px 16px; border-radius:9px; cursor:pointer;
                               border:2px solid {{ $dSaleType === $val ? 'var(--accent)' : 'var(--rule)' }};
                               background:{{ $dSaleType === $val ? 'rgba(31,58,104,.06)' : 'var(--canvas)' }};">
                        <input type="radio" wire:model.live="dSaleType" value="{{ $val }}" class="sr-only">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="{{ $dSaleType === $val ? 'var(--accent)' : 'var(--ink-3)' }}"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="{{ $meta['icon'] }}"/>
                            </svg>
                            <span style="font:600 13px 'Inter', sans-serif; color:{{ $dSaleType === $val ? 'var(--accent)' : 'var(--ink-1)' }};">{{ $meta['label'] }}</span>
                        </div>
                        <div style="font:11.5px 'Inter', sans-serif; color:var(--ink-3); padding-left:24px;">{{ $meta['desc'] }}</div>
                    </label>
                @endforeach
            </div>
            @error('dSaleType') <p style="margin-top:8px; font-size:11.5px; color:var(--rj-fg);">{{ $message }}</p> @enderror
        </div>

        {{-- ══ 2. PROPERTY & CUSTOMER ══ --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:16px;">
                <h3 style="margin:0; font-size:14px; font-weight:600;">Property &amp; Customer</h3>
                <span style="font:11px var(--mono); color:var(--rj-fg);">required</span>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
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
                            @php
                                $purposeLabel = match($unit->purpose) {
                                    'sell' => ' · For Sale',
                                    'rent' => ' · For Rent',
                                    default => '',
                                };
                                $isDisabled = ($dSaleType === 'sale' && $unit->purpose === 'rent')
                                           || ($dSaleType === 'rent' && $unit->purpose === 'sell');
                            @endphp
                            <option value="{{ $unit->id }}" @disabled($isDisabled)>
                                {{ $unit->code }}
                                ({{ ucfirst($unit->type ?? '') }}, {{ ucfirst($unit->status ?? '') }}){{ $purposeLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('dPropertyUnitId') <p style="margin-top:5px; font-size:11.5px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>
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

        {{-- ══ 3a. SALE FINANCIAL (sale only) ══ --}}
        <div x-show="saleType === 'sale'" x-cloak
            style="background:#F5F2E8; border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:16px;">
                <h3 style="margin:0; font-size:14px; font-weight:600;">Financial Details</h3>
                <span style="font:11px var(--mono); color:var(--ink-3);">BDT (৳)</span>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:14px;">
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                        Sale Amount <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <input wire:model.blur="dSaleAmount" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                    @error('dSaleAmount') <p style="margin-top:4px; font-size:11.5px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Discount</label>
                    <input wire:model.blur="dDiscountAmount" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Tax</label>
                    <input wire:model.blur="dTaxAmount" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                </div>
            </div>
            {{-- Net Amount display --}}
            <div style="padding:14px 18px; background:var(--paper); border:2px solid var(--accent); border-radius:9px; display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                <div>
                    <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:2px;">Net Amount</div>
                    <div style="font:11.5px 'Inter', sans-serif; color:var(--ink-3);">Sale − Discount + Tax</div>
                </div>
                <div style="font:700 26px var(--mono); color:var(--accent); font-variant-numeric:tabular-nums;">
                    ৳ <span x-text="parseFloat(dNetAmount || 0).toLocaleString('en-BD', {minimumFractionDigits:2})"></span>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Down Payment %</label>
                    <div style="position:relative;">
                        <input wire:model.blur="dDownPaymentPercentage" type="number" min="0" max="100" step="0.01" placeholder="0.00"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 36px 10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                        <span style="position:absolute; right:12px; top:50%; transform:translateY(-50%); font:600 12px var(--mono); color:var(--ink-3); pointer-events:none;">%</span>
                    </div>
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Down Payment (৳)</label>
                    <input wire:model.blur="dDownPaymentAmount" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                    <p style="margin-top:4px; font:11px 'Inter', sans-serif; color:var(--ink-3);">Edit % or ৳ — both stay in sync</p>
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Payment Terms (days)</label>
                    <input wire:model="dPaymentTerms" type="number" min="0" placeholder="e.g. 30"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                </div>
            </div>
        </div>

        {{-- ══ 3b. RENT DETAILS (rent only) ══ --}}
        <div x-show="saleType === 'rent'" x-cloak
            style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <h3 style="margin:0 0 16px; font-size:14px; font-weight:600;">Rent Details</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Rent Start Date</label>
                    <input wire:model="dRentStartDate" type="date"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" class="flatpickr-only-date" />
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Rent End Date</label>
                    <input wire:model="dRentEndDate" type="date"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" class="flatpickr-only-date" />
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Security Deposit (৳)</label>
                    <input wire:model="dSecurityDepositAmount" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Monthly Rent (৳)</label>
                    <input wire:model="dScheduleAmount" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                    <p style="margin-top:4px; font:11px 'Inter', sans-serif; color:var(--ink-3);">Used for auto-generating rent schedules</p>
                </div>
                {{-- Renewal --}}
                <div style="grid-column:span 2; padding:14px 16px; background:var(--canvas); border-radius:8px; display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" wire:model.live="dIsRenewal" style="width:16px; height:16px; accent-color:var(--accent);">
                        <span style="font:500 13px 'Inter', sans-serif;">This is a renewal</span>
                    </label>
                    @if($dIsRenewal)
                        <div style="display:flex; align-items:center; gap:8px;">
                            <label style="font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); white-space:nowrap;">Renewal Date</label>
                            <input wire:model="dRenewalDate" type="date"
                                style="appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:8px 12px; border-radius:7px; font-family:var(--mono); font-size:13px;" class="flatpickr-only-date" />
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══ 4. KEY DATES ══ --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <h3 style="margin:0 0 16px; font-size:14px; font-weight:600;">Key Dates</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div x-show="saleType === 'sale'" x-cloak>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Sale Date</label>
                    <input wire:model="dSaleDate" type="date"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" class="flatpickr-only-date" />
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Contract Date</label>
                    <input wire:model="dContractDate" type="date"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font-family:var(--mono); font-size:13px;" class="flatpickr-only-date" />
                </div>
            </div>
        </div>

        {{-- ══ 5. PAYMENT SCHEDULE ══ --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
            {{-- Header toggle --}}
            <div style="padding:16px 24px; display:flex; justify-content:space-between; align-items:center;"
                :style="isScheduled ? 'border-bottom:1px solid var(--rule)' : ''">
                <div>
                    <h3 style="margin:0; font-size:14px; font-weight:600;">Auto Payment Schedule</h3>
                    <div style="margin-top:3px; font:12px 'Inter', sans-serif; color:var(--ink-3);">
                        <span x-show="saleType === 'sale'">Auto-generate installment schedule</span>
                        <span x-show="saleType === 'rent'" x-cloak>Auto-generate monthly rent schedule</span>
                    </div>
                </div>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" wire:model.live="dIsScheduled"
                        style="width:17px; height:17px; accent-color:var(--accent); cursor:pointer;">
                    <span style="font:500 13px 'Inter', sans-serif;" x-text="isScheduled ? 'Enabled' : 'Disabled'"></span>
                </label>
            </div>

            {{-- Schedule settings (visible when enabled) --}}
            <div x-show="isScheduled" x-cloak style="padding:20px 24px; display:flex; flex-direction:column; gap:16px; background:rgba(31,58,104,.025);">

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:12px;">
                    {{-- Schedule Type --}}
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                            Frequency <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <select wire:model.live="dScheduleType"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    {{-- Schedule Day --}}
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Day of Month</label>
                        <input wire:model.live="dScheduleDay" type="number" min="1" max="28" placeholder="5"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                        @error('dScheduleDay') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                    {{-- Count --}}
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                            Count <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <input wire:model.live="dScheduleCount" type="number" min="1" max="360" placeholder="12"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                        @error('dScheduleCount') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                    {{-- Amount per period --}}
                    <div>
                        <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                            Amount/Period (৳) <span style="color:var(--rj-fg)">*</span>
                        </label>
                        <input wire:model.live="dScheduleAmount" type="number" min="0" step="0.01" placeholder="0.00"
                            style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:var(--mono); font-size:13px;" />
                        <p style="margin-top:4px; font:11px 'Inter', sans-serif; color:var(--ink-3);">Auto: (Net − Down Payment) ÷ Count</p>
                        @error('dScheduleAmount') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Start date --}}
                <div style="max-width:240px;">
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                        Schedule Start Date <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <input wire:model.live="dScheduleStartDate" type="date"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" class="flatpickr-only-date" />
                    @error('dScheduleStartDate') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>

                {{-- ── LIVE PREVIEW ── --}}
                @if(!empty($schedulePreview))
                    <div style="border:1px solid var(--rule); border-radius:8px; overflow:hidden;">
                        <div style="padding:10px 16px; background:var(--canvas); border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                            <span style="font:600 11px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-2);">
                                Schedule Preview — {{ count($schedulePreview) }} entries
                            </span>
                            <span style="font:600 12px var(--mono); color:var(--accent);">
                                Total: ৳ {{ number_format(array_sum(array_column($schedulePreview, 'amount')), 2) }}
                            </span>
                        </div>
                        <div style="max-height:260px; overflow-y:auto;">
                            <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                                <thead style="position:sticky; top:0; background:var(--paper);">
                                    <tr>
                                        <th style="padding:8px 14px; text-align:left; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">#</th>
                                        <th style="padding:8px 14px; text-align:left; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Description</th>
                                        <th style="padding:8px 14px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Due Date</th>
                                        <th style="padding:8px 14px; text-align:right; font:600 10px 'Inter', sans-serif; letter-spacing:.07em; text-transform:uppercase; color:var(--ink-3); border-bottom:1px solid var(--rule);">Amount (৳)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($schedulePreview as $row)
                                        <tr style="border-bottom:1px solid var(--rule);">
                                            <td style="padding:8px 14px; font-family:var(--mono); color:var(--ink-3);">{{ str_pad($row['seq'], 2, '0', STR_PAD_LEFT) }}</td>
                                            <td style="padding:8px 14px; font-weight:500;">{{ $row['label'] }}</td>
                                            <td style="padding:8px 14px; text-align:right; font-family:var(--mono);">{{ \Carbon\Carbon::parse($row['due_date'])->format('d M Y') }}</td>
                                            <td style="padding:8px 14px; text-align:right; font-family:var(--mono); font-variant-numeric:tabular-nums;">{{ number_format($row['amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif($dIsScheduled && $dScheduleStartDate && $dScheduleCount)
                    <div style="padding:16px; text-align:center; font-size:12.5px; color:var(--ink-3); background:var(--canvas); border-radius:8px;">
                        <span wire:loading wire:target="dScheduleCount,dScheduleDay,dScheduleStartDate,dScheduleType">Generating preview…</span>
                        <span wire:loading.remove>No preview — check schedule settings.</span>
                    </div>
                @endif

            </div>
        </div>

        {{-- ══ 6. SALE STATUS & NOTES ══ --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:20px 24px;">
            <h3 style="margin:0 0 16px; font-size:14px; font-weight:600;">Status &amp; Notes</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
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
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">
                        Sale Status <span style="color:var(--rj-fg)">*</span>
                    </label>
                    <select wire:model="dStatus"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font:13px 'Inter', sans-serif;">
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="on_hold">On Hold</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Sales Representative</label>
                    <input wire:model="dSalesRepresentative" type="text" placeholder="Name of the sales agent"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                </div>
                <div>
                    <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Notes</label>
                    <textarea wire:model="dNotes" placeholder="Internal notes…" rows="2"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:10px 14px; border-radius:7px; font:13px 'Inter', sans-serif; resize:vertical;"></textarea>
                </div>
            </div>
        </div>

        {{-- Errors --}}
        @if($errors->any())
            <div style="background:var(--rj-bg); border:1px solid rgba(122,42,30,.2); border-radius:10px; padding:14px 20px;">
                <ul style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:4px;">
                    @foreach($errors->all() as $error)
                        <li style="font:500 12px 'Inter', sans-serif; color:var(--rj-fg); display:flex; align-items:center; gap:7px;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ── ACTION ROW ── --}}
        <div style="display:flex; justify-content:flex-end; align-items:center; gap:10px; padding-bottom:80px;">
            <a href="{{ route('admin.properties.sales.index') }}"
                style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2);
                       padding:9px 20px; font:500 13px 'Inter', sans-serif; border-radius:7px;
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
