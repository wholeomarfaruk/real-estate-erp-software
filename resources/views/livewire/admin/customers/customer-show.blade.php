<div
    x-data
    x-init="$store.pageName = { name: 'Customer Detail', slug: 'customers' }"
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
        padding:28px 24px 80px;
    "
    class="min-h-screen"
>

    {{-- ─── BREADCRUMB ─────────────────────────────────────────────────── --}}
    <div style="font-size:11.5px; color:var(--ink-3); font-family:var(--mono); display:flex; gap:6px; align-items:center; margin-bottom:14px;">
        <span style="color:var(--ink-3);">CRM</span>
        <span style="opacity:.5">/</span>
        <a href="{{ route('admin.crm.customers.index') }}" style="color:var(--ink-3); text-decoration:none;"
            class="hover:text-[var(--ink-1)] transition-colors">Customers</a>
        <span style="opacity:.5">/</span>
        <span style="color:var(--ink-1);">{{ $customer->customer_id }} · {{ $customer->name }}</span>
    </div>

    {{-- ─── HERO ───────────────────────────────────────────────────────── --}}
    <section style="background:var(--paper); border:1px solid var(--rule); border-radius:12px;
                    overflow:hidden; margin-bottom:18px;
                    display:grid; grid-template-columns: 220px 1fr 280px;">

        {{-- Photo col --}}
        <div style="border-right:1px solid var(--rule); padding:24px; display:flex; flex-direction:column; align-items:center; gap:12px;
                    background:linear-gradient(180deg, var(--canvas) 0%, var(--paper) 100%);">
            <div style="width:140px; height:140px; border-radius:50%;
                        background:linear-gradient(135deg, #8a5a3a, #5c3a25);
                        border:4px solid var(--paper);
                        box-shadow: 0 0 0 1px var(--rule), 0 12px 28px -16px rgba(0,0,0,.3);
                        display:flex; align-items:center; justify-content:center;
                        color:#fff; font:600 42px var(--mono); letter-spacing:.04em; position:relative;">
                {{ $customer->initials() }}
                @if($customer->kyc_status === 'verified')
                    <span style="position:absolute; right:5px; bottom:5px;
                                 width:36px; height:36px; border-radius:50%;
                                 background:var(--av-fg); color:#fff;
                                 display:flex; align-items:center; justify-content:center;
                                 border:3px solid var(--paper);"
                        title="KYC Verified">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                @endif
            </div>
            <div style="font:11px var(--mono); color:var(--ink-3); text-align:center; text-transform:uppercase; letter-spacing:.08em;">
                Since {{ $customer->created_at->format('Y-m-d') }}
            </div>
            @if($canEdit)
                <a href="{{ route('admin.crm.customers.index') }}"
                    style="display:inline-flex; align-items:center; gap:6px; appearance:none; border:1px solid var(--rule);
                           background:var(--paper); color:var(--ink-1); padding:5px 10px; font:500 11.5px 'Inter', sans-serif;
                           border-radius:6px; cursor:pointer; text-decoration:none;"
                    class="hover:bg-black/[.03] transition-colors">
                    Edit customer
                </a>
            @endif
        </div>

        {{-- Main info col --}}
        <div style="padding:24px 28px; display:flex; flex-direction:column;">
            <div style="font:500 11px var(--mono); color:var(--ink-3); text-transform:uppercase; letter-spacing:.08em;">
                {{ $customer->customer_id }} · Customer since {{ $customer->created_at->format('Y-m-d') }}
            </div>
            <div style="margin-top:6px; font-size:28px; font-weight:600; letter-spacing:-.01em; line-height:1.15;">
                {{ $customer->name }}
            </div>
            @if($customer->address || $customer->district)
                <div style="margin-top:6px; font-size:13px; color:var(--ink-2);">
                    {{ implode(', ', array_filter([$customer->address, $customer->district, $customer->division])) }}
                </div>
            @endif

            {{-- Pills --}}
            <div style="margin-top:14px; display:flex; gap:6px; flex-wrap:wrap;">
                {{-- Type --}}
                @if($customer->type === 'individual')
                    <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; background:var(--rt-bg); color:var(--rt-fg); font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                        <span style="width:5px; height:5px; border-radius:50%; background:var(--rt-fg);"></span>Individual
                    </span>
                @else
                    <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; background:var(--sd-bg); color:var(--sd-fg); font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                        <span style="width:5px; height:5px; border-radius:50%; background:var(--sd-fg);"></span>Company
                    </span>
                @endif
                {{-- KYC --}}
                @php
                    $kycMap = ['verified' => ['var(--av-bg)', 'var(--av-fg)'], 'pending' => ['var(--bk-bg)', 'var(--bk-fg)'], 'rejected' => ['var(--rj-bg)', 'var(--rj-fg)']];
                    [$kbg, $kfg] = $kycMap[$customer->kyc_status] ?? $kycMap['pending'];
                @endphp
                <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; background:{{ $kbg }}; color:{{ $kfg }}; font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                    <span style="width:5px; height:5px; border-radius:50%; background:{{ $kfg }};"></span>
                    KYC {{ ucfirst($customer->kyc_status) }}
                </span>
                {{-- Status --}}
                @php
                    $stMap = ['active' => ['var(--av-bg)', 'var(--av-fg)'], 'inactive' => ['var(--in-bg)', 'var(--in-fg)'], 'suspended' => ['var(--rj-bg)', 'var(--rj-fg)']];
                    [$sbg, $sfg] = $stMap[$customer->status] ?? $stMap['inactive'];
                @endphp
                <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; background:{{ $sbg }}; color:{{ $sfg }}; font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                    <span style="width:5px; height:5px; border-radius:50%; background:{{ $sfg }};"></span>
                    {{ ucfirst($customer->status) }}
                </span>
            </div>

            {{-- Fact grid --}}
            <div style="margin-top:18px; display:grid; grid-template-columns:repeat(3,1fr); gap:0; border:1px solid var(--rule); border-radius:8px; overflow:hidden;">
                @php
                    $facts = [
                        ['Phone', $customer->phone, true],
                        ['Alt. phone', $customer->phone_alt ?: '—', true],
                        ['Email', $customer->email ?: '—', false],
                        ['Date of birth', $customer->date_of_birth?->format('Y-m-d') ?? '—', true],
                        ['Gender', $customer->gender ? ucfirst(str_replace('_', ' ', $customer->gender)) : '—', false],
                        ['Father\'s name', $customer->father_name ?: '—', false],
                        ['District', $customer->district ?: '—', false],
                        ['Postal', $customer->postal_code ?: '—', true],
                        ['Address', $customer->address ?: '—', false],
                    ];
                @endphp
                @foreach($facts as $i => [$lbl, $val, $mono])
                    @php
                        $col = ($i % 3) + 1;
                        $row = intdiv($i, 3) + 1;
                        $totalRows = ceil(count($facts) / 3);
                        $borderR = $col < 3 ? '1px solid var(--rule)' : '0';
                        $borderB = $row < $totalRows ? '1px solid var(--rule)' : '0';
                    @endphp
                    <div style="padding:11px 14px; background:var(--paper); border-right:{{ $borderR }}; border-bottom:{{ $borderB }};">
                        <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">{{ $lbl }}</div>
                        <div style="margin-top:3px; {{ $mono ? "font:500 13px var(--mono);" : "font:500 13px 'Inter', sans-serif;" }}">{{ $val }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Side actions col --}}
        <div style="padding:24px; border-left:1px solid var(--rule); background:rgba(0,0,0,.012); display:flex; flex-direction:column; gap:14px;">
            <div>
                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:6px;">Quick actions</div>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    @if($canEdit)
                        <a href="{{ route('admin.crm.customers.index') }}"
                            style="display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--paper); border:1px solid var(--rule); border-radius:8px; cursor:pointer; text-decoration:none; color:var(--ink-1);"
                            class="hover:bg-black/[.018] hover:border-[var(--ink-3)] transition-colors">
                            <span style="width:32px; height:32px; border-radius:7px; background:var(--in-bg); color:var(--in-fg); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </span>
                            <span style="font:500 12.5px 'Inter', sans-serif; flex:1;">Edit details</span>
                            <span style="color:var(--ink-3);">›</span>
                        </a>
                    @endif
                    <div style="display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--paper); border:1px solid var(--rule); border-radius:8px; cursor:pointer; opacity:.6;">
                        <span style="width:32px; height:32px; border-radius:7px; background:var(--in-bg); color:var(--in-fg); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                        </span>
                        <span style="font:500 12.5px 'Inter', sans-serif; flex:1;">New booking</span>
                        <span style="color:var(--ink-3);">›</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--paper); border:1px solid var(--rule); border-radius:8px; cursor:pointer; opacity:.6;">
                        <span style="width:32px; height:32px; border-radius:7px; background:var(--in-bg); color:var(--in-fg); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><line x1="12" y1="12" x2="12.01" y2="12"/></svg>
                        </span>
                        <span style="font:500 12.5px 'Inter', sans-serif; flex:1;">Record payment</span>
                        <span style="color:var(--ink-3);">›</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--paper); border:1px solid var(--rule); border-radius:8px; cursor:pointer; opacity:.6;">
                        <span style="width:32px; height:32px; border-radius:7px; background:var(--in-bg); color:var(--in-fg); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </span>
                        <span style="font:500 12.5px 'Inter', sans-serif; flex:1;">Upload attachment</span>
                        <span style="color:var(--ink-3);">›</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── KPI STRIP ──────────────────────────────────────────────────── --}}
    <section style="display:grid; grid-template-columns:repeat(5,1fr); gap:1px;
                    background:var(--rule); border:1px solid var(--rule); border-radius:10px;
                    overflow:hidden; margin-bottom:24px;">
        <div style="background:var(--paper); padding:14px 16px;">
            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Holdings</div>
            <div style="margin-top:5px; font:600 22px var(--mono); font-variant-numeric:tabular-nums;">0</div>
            <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">units / properties</div>
        </div>
        <div style="background:var(--paper); padding:14px 16px;">
            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Total value</div>
            <div style="margin-top:5px; font:600 22px var(--mono);">৳ 0</div>
            <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">across all units</div>
        </div>
        <div style="background:var(--paper); padding:14px 16px;">
            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Paid</div>
            <div style="margin-top:5px; font:600 22px var(--mono); color:var(--av-fg);">৳ 0</div>
            <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">settled</div>
        </div>
        <div style="background:var(--paper); padding:14px 16px;">
            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Due</div>
            <div style="margin-top:5px; font:600 22px var(--mono); color:var(--bk-fg);">৳ 0</div>
            <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">outstanding</div>
        </div>
        <div style="background:var(--paper); padding:14px 16px;">
            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Documents</div>
            <div style="margin-top:5px; font:600 22px var(--mono);">{{ $customer->doc_no ? 1 : 0 }}</div>
            <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">on file</div>
        </div>
    </section>

    {{-- ─── 2-COL: HOLDINGS + ACCOUNT ─────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:1.45fr 1fr; gap:18px; margin-bottom:18px;">

        {{-- Property holdings --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:12px; overflow:hidden;">
            <div style="padding:14px 20px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:14px; font-weight:600;">Property holdings</h3>
                <span style="font:11px var(--mono); color:var(--ink-3);">No holdings yet</span>
            </div>
            <div style="padding:48px 20px; text-align:center; color:var(--ink-3); font:13px 'Inter', sans-serif;">
                <div style="margin-bottom:8px; opacity:.5;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 21V8l9-5 9 5v13"/><path d="M9 21V12h6v9"/></svg>
                </div>
                No property holdings on record for this customer.
            </div>
        </div>

        {{-- Account & transactions --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:12px; overflow:hidden;">
            <div style="padding:14px 20px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:14px; font-weight:600;">Account &amp; transactions</h3>
            </div>
            <div style="padding:48px 20px; text-align:center; color:var(--ink-3); font:13px 'Inter', sans-serif;">
                <div style="margin-bottom:8px; opacity:.5;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="2"/><line x1="12" y1="12" x2="12.01" y2="12"/></svg>
                </div>
                No transactions recorded for this customer.
            </div>
        </div>
    </div>

    {{-- ─── 2-COL: IDENTITY/DOCS + ACTIVITY ───────────────────────────── --}}
    <div style="display:grid; grid-template-columns:1.45fr 1fr; gap:18px; margin-bottom:18px;">

        {{-- Identity & documents --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:12px; overflow:hidden;">
            <div style="padding:14px 20px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:14px; font-weight:600;">Identity &amp; documents</h3>
            </div>
            <div style="padding:18px 20px;">
                @if($customer->doc_type)
                    <div style="display:flex; gap:14px; align-items:center; padding:12px; border:1px solid var(--rule); border-radius:9px; background:rgba(0,0,0,.012);">
                        <div style="width:64px; height:64px; border-radius:7px; flex-shrink:0;
                                    background:linear-gradient(135deg, #8a7a5a, #5c4f38);
                                    display:flex; align-items:center; justify-content:center; color:#fff; position:relative;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" opacity=".7"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M14 9h4M14 13h4M3 18h18"/></svg>
                            @if($customer->kyc_status === 'verified')
                                <span style="position:absolute; right:4px; bottom:4px; width:16px; height:16px; border-radius:50%; background:var(--av-fg); color:#fff; display:flex; align-items:center; justify-content:center; font-size:8px; border:1.5px solid var(--paper);">✓</span>
                            @endif
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font:600 13px 'Inter', sans-serif;">{{ strtoupper(str_replace('_', ' ', $customer->doc_type)) }}</div>
                            <div style="margin-top:2px; font:11.5px var(--mono); color:var(--ink-2);">{{ $customer->doc_no }}</div>
                            <div style="margin-top:6px; display:flex; gap:8px; font:10.5px var(--mono); color:var(--ink-3);">
                                @php
                                    $chipBg = $customer->kyc_status === 'verified' ? 'var(--av-bg)' : 'var(--in-bg)';
                                    $chipFg = $customer->kyc_status === 'verified' ? 'var(--av-fg)' : 'var(--in-fg)';
                                @endphp
                                <span style="padding:1px 7px; border-radius:3px; background:{{ $chipBg }}; color:{{ $chipFg }}; font:600 9px 'Inter', sans-serif; letter-spacing:.06em; text-transform:uppercase;">
                                    {{ ucfirst($customer->kyc_status) }}
                                </span>
                                @if($customer->doc_issue_date)
                                    <span>Issued {{ $customer->doc_issue_date->format('Y-m-d') }}</span>
                                @endif
                                @if($customer->doc_expiry_date)
                                    <span>· Expires {{ $customer->doc_expiry_date->format('Y-m-d') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div style="padding:32px; text-align:center; color:var(--ink-3); font:13px 'Inter', sans-serif;">
                        No identity document on file.
                    </div>
                @endif

                {{-- Company section --}}
                @if($customer->type === 'company' && $customer->company_name)
                    <div style="margin-top:14px; padding:12px; border:1px solid var(--rule); border-radius:9px; background:rgba(0,0,0,.012);">
                        <div style="font:600 11px 'Inter', sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3); margin-bottom:8px;">Company info</div>
                        <div style="font:600 13px 'Inter', sans-serif;">{{ $customer->company_name }}</div>
                        @if($customer->company_registration_no)
                            <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">Reg: {{ $customer->company_registration_no }}</div>
                        @endif
                        @if($customer->company_tax_id)
                            <div style="margin-top:2px; font:11px var(--mono); color:var(--ink-2);">TIN: {{ $customer->company_tax_id }}</div>
                        @endif
                    </div>
                @endif

                {{-- Source --}}
                @if($customer->source)
                    <div style="margin-top:14px; display:flex; align-items:center; gap:8px;">
                        <span style="font:600 10px 'Inter', sans-serif; letter-spacing:.06em; text-transform:uppercase; color:var(--ink-3);">Source:</span>
                        <span style="font:500 12.5px 'Inter', sans-serif;">{{ ucfirst(str_replace('_', ' ', $customer->source)) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Activity & notes --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:12px; overflow:hidden;">
            <div style="padding:14px 20px; border-bottom:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:14px; font-weight:600;">Activity &amp; notes</h3>
            </div>
            <div style="padding:18px 20px;">
                @if($customer->notes)
                    <div style="padding:14px 16px; background:rgba(217, 185, 90, 0.08);
                                border:1px solid #E8D9B0; border-radius:9px;
                                font:13px 'Inter', sans-serif; color:var(--ink-1); line-height:1.6; position:relative;">
                        <div style="position:absolute; top:-4px; left:14px; background:var(--paper); padding:0 4px; font-size:14px;">📌</div>
                        {{ $customer->notes }}
                    </div>
                @endif

                <div style="margin-top:18px;">
                    <div style="font:600 11px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:10px;">Recent activity</div>
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        @if($customer->updatedByUser && $customer->updated_at != $customer->created_at)
                            <div style="display:flex; gap:12px;">
                                <div style="width:32px; height:32px; border-radius:50%; flex-shrink:0; background:var(--bk-bg); color:var(--bk-fg); display:flex; align-items:center; justify-content:center;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </div>
                                <div style="flex:1;">
                                    <div style="font:500 13px 'Inter', sans-serif;"><b>{{ $customer->updatedByUser->name }}</b> updated record</div>
                                    <div style="margin-top:2px; font:11px var(--mono); color:var(--ink-3);">{{ $customer->updated_at->format('Y-m-d H:i') }}</div>
                                </div>
                            </div>
                        @endif
                        <div style="display:flex; gap:12px;">
                            <div style="width:32px; height:32px; border-radius:50%; flex-shrink:0; background:var(--rt-bg); color:var(--rt-fg); display:flex; align-items:center; justify-content:center;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div style="flex:1;">
                                <div style="font:500 13px 'Inter', sans-serif;">
                                    <b>{{ $customer->createdByUser?->name ?? 'System' }}</b> created customer record
                                </div>
                                <div style="margin-top:2px; font:11px var(--mono); color:var(--ink-3);">{{ $customer->created_at->format('Y-m-d H:i') }} · {{ $customer->customer_id }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── BOTTOM TABS ─────────────────────────────────────────────────── --}}
    <div x-data="{ bottomTab: 'bookings' }">
        <div style="display:flex; background:var(--paper); border:1px solid var(--rule); border-radius:10px 10px 0 0; padding:0 8px;">
            @foreach(['bookings' => 'Bookings', 'invoices' => 'Invoices', 'leases' => 'Leases', 'metadata' => 'Metadata'] as $tab => $label)
                <button @click="bottomTab = '{{ $tab }}'"
                    style="appearance:none; background:transparent; border:0; cursor:pointer;
                           padding:14px 16px; font:500 13px 'Inter', sans-serif;
                           border-bottom:2px solid transparent; margin-bottom:-1px;"
                    :style="bottomTab === '{{ $tab }}'
                        ? 'color:var(--ink-1); border-bottom-color:var(--ink-1); font-weight:600;'
                        : 'color:var(--ink-2);'">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div style="background:var(--paper); border:1px solid var(--rule); border-top:0; border-radius:0 0 10px 10px; padding:18px 20px;">

            {{-- Bookings tab --}}
            <div x-show="bottomTab === 'bookings'">
                <div style="text-align:center; padding:32px 0; color:var(--ink-3); font:13px 'Inter', sans-serif;">
                    No bookings on record for this customer.
                </div>
            </div>

            {{-- Invoices tab --}}
            <div x-show="bottomTab === 'invoices'">
                <div style="text-align:center; padding:32px 0; color:var(--ink-3); font:13px 'Inter', sans-serif;">
                    No invoices issued to this customer.
                </div>
            </div>

            {{-- Leases tab --}}
            <div x-show="bottomTab === 'leases'">
                <div style="text-align:center; padding:32px 0; color:var(--ink-3); font:13px 'Inter', sans-serif;">
                    No active leases for this customer.
                </div>
            </div>

            {{-- Metadata tab --}}
            <div x-show="bottomTab === 'metadata'">
                <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:14px;">
                    @foreach([
                        ['Created by',     $customer->createdByUser?->name ?? '—', false],
                        ['Created at',     $customer->created_at->format('Y-m-d H:i'), true],
                        ['Modified by',    $customer->updatedByUser?->name ?? '—', false],
                        ['Modified at',    $customer->updated_at->format('Y-m-d H:i'), true],
                        ['Profile image FK', $customer->profile_image_id ? 'files.id = ' . $customer->profile_image_id : '—', true],
                        ['Document FK',    $customer->doc_file_id ? 'files.id = ' . $customer->doc_file_id : '—', true],
                    ] as [$label, $value, $mono])
                        <div>
                            <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:4px;">{{ $label }}</div>
                            <div style="{{ $mono ? "font:500 12px var(--mono);" : "font:500 13px 'Inter', sans-serif;" }}">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

</div>
