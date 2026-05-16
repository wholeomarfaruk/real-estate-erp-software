<div
    x-data="{
        drawerOpen: $wire.entangle('drawerOpen'),
        activeTab: $wire.entangle('activeTab'),
        dType: $wire.entangle('dType'),
        step: $wire.entangle('step'),
    }"
    x-init="$store.pageName = { name: 'Customers', slug: 'customers' }"
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
                <span>CRM</span>
                <span style="opacity:.5">/</span>
                <span style="color:var(--ink-1)">Customers</span>
            </div>
            <div style="font-size:24px; font-weight:600; letter-spacing:-.01em;">Customers</div>
            <div style="margin-top:4px; font-size:13px; color:var(--ink-2);">All individuals and companies on record — with KYC, documents and contact info.</div>
        </div>
        <div class="flex gap-2">
            @can('customer.create')
                <button wire:click="openCreate"
                    style="appearance:none; border:1px solid var(--ink-1); background:var(--ink-1); color:var(--paper);
                           padding:7px 14px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                           display:inline-flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    New customer
                </button>
            @endcan
        </div>
    </div>

    <div style="padding:20px 24px 80px;">

        {{-- ─── KPI STRIP ───────────────────────────────────────────────── --}}
        <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:1px;
                    background:var(--rule); border:1px solid var(--rule); border-radius:10px;
                    overflow:hidden; margin-bottom:20px;">
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Total customers</div>
                <div style="margin-top:5px; font:600 22px var(--mono); font-variant-numeric:tabular-nums;">{{ $kpi['total'] }}</div>
                <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">on record</div>
            </div>
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">KYC Verified</div>
                <div style="margin-top:5px; font:600 22px var(--mono); color:var(--av-fg);">{{ $kpi['verified'] }}</div>
                <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">documents cleared</div>
            </div>
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">KYC Pending</div>
                <div style="margin-top:5px; font:600 22px var(--mono); color:var(--bk-fg);">{{ $kpi['pending'] }}</div>
                <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">awaiting review</div>
            </div>
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Active</div>
                <div style="margin-top:5px; font:600 22px var(--mono); color:var(--av-fg);">{{ $kpi['active'] }}</div>
                <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">status active</div>
            </div>
            <div style="background:var(--paper); padding:14px 18px;">
                <div style="font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3);">Inactive / Suspended</div>
                <div style="margin-top:5px; font:600 22px var(--mono); color:var(--in-fg);">{{ $kpi['inactive'] }}</div>
                <div style="margin-top:3px; font:11px var(--mono); color:var(--ink-2);">not active</div>
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
                    placeholder="Search by name, phone, NID, email or customer ID…"
                    style="border:0; background:transparent; outline:none; width:100%; font:13px 'Inter', sans-serif; color:var(--ink-1);" />
            </div>
            {{-- Type pill group --}}
            <div style="display:flex; gap:4px; background:var(--canvas); border-radius:6px; padding:3px;">
                @foreach(['all' => 'All', 'individual' => 'Individual', 'company' => 'Company'] as $val => $label)
                    <button wire:click="$set('filterType', '{{ $val }}')"
                        style="appearance:none; border:0; padding:6px 12px; font:500 12px 'Inter', sans-serif;
                               border-radius:4px; cursor:pointer;
                               background:{{ $filterType === $val ? 'var(--paper)' : 'transparent' }};
                               color:{{ $filterType === $val ? 'var(--ink-1)' : 'var(--ink-2)' }};
                               box-shadow:{{ $filterType === $val ? '0 1px 2px rgba(0,0,0,.05)' : 'none' }};">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            {{-- KYC select --}}
            <select wire:model.live="filterKyc"
                style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1);
                       padding:7px 28px 7px 12px; font:500 12px 'Inter', sans-serif; border-radius:6px;
                       background-image: linear-gradient(45deg, transparent 50%, var(--ink-2) 50%), linear-gradient(135deg, var(--ink-2) 50%, transparent 50%);
                       background-position: calc(100% - 14px) 50%, calc(100% - 10px) 50%;
                       background-size: 4px 4px, 4px 4px; background-repeat: no-repeat;">
                <option value="all">All KYC</option>
                <option value="verified">Verified</option>
                <option value="pending">Pending</option>
                <option value="rejected">Rejected</option>
            </select>
            {{-- Status select --}}
            <select wire:model.live="filterStatus"
                style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1);
                       padding:7px 28px 7px 12px; font:500 12px 'Inter', sans-serif; border-radius:6px;
                       background-image: linear-gradient(45deg, transparent 50%, var(--ink-2) 50%), linear-gradient(135deg, var(--ink-2) 50%, transparent 50%);
                       background-position: calc(100% - 14px) 50%, calc(100% - 10px) 50%;
                       background-size: 4px 4px, 4px 4px; background-repeat: no-repeat;">
                <option value="all">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>

        {{-- ─── LIST ───────────────────────────────────────────────────── --}}
        <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; overflow:hidden;">
            {{-- Header --}}
            <div style="display:grid; grid-template-columns: 60px 1.5fr 1fr 0.9fr 1fr 80px;
                        padding:12px 18px; background:rgba(0,0,0,.012); border-bottom:1px solid var(--rule);
                        font:600 10.5px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-2);">
                <div></div>
                <div>Customer</div>
                <div>Contact</div>
                <div>Document</div>
                <div>Type · KYC · Status</div>
                <div style="text-align:right;">Actions</div>
            </div>

            {{-- Rows --}}
            @forelse($customers as $customer)
                <div style="display:grid; grid-template-columns: 60px 1.5fr 1fr 0.9fr 1fr 80px;
                            padding:14px 18px; border-bottom:1px solid var(--rule); align-items:center;"
                    class="hover:bg-black/[.018] transition-colors">

                    {{-- Avatar --}}
                    <div>
                        <div style="width:40px; height:40px; border-radius:50%;
                                    background:var(--accent); color:var(--paper);
                                    display:flex; align-items:center; justify-content:center;
                                    font:600 14px var(--mono); letter-spacing:.02em;">
                            {{ $customer->initials() }}
                        </div>
                    </div>

                    {{-- Customer --}}
                    <div>
                        <div style="font-size:13.5px; font-weight:600;">
                            <a href="{{ route('admin.crm.customers.show', $customer) }}"
                               style="color:var(--ink-1); text-decoration:none;"
                               class="hover:underline">
                                {{ $customer->name }}
                            </a>
                        </div>
                        <div style="margin-top:2px; font:500 11px var(--mono); color:var(--ink-3); letter-spacing:.04em; text-transform:uppercase;">
                            {{ $customer->customer_id }} · since {{ $customer->created_at->format('Y-m-d') }}
                        </div>
                    </div>

                    {{-- Contact --}}
                    <div style="font-size:12.5px; color:var(--ink-2); display:flex; flex-direction:column; gap:2px;">
                        <span style="font-family:var(--mono); font-size:11px;">{{ $customer->phone }}</span>
                        <span>{{ $customer->email ?: '—' }}</span>
                    </div>

                    {{-- Document --}}
                    <div style="display:flex; flex-direction:column; gap:2px; font:500 11px var(--mono); color:var(--ink-2);">
                        @if($customer->doc_type)
                            <span style="display:inline-flex; padding:1px 6px; border-radius:3px;
                                         background:var(--in-bg); color:var(--in-fg);
                                         font:600 9px 'Inter', sans-serif; letter-spacing:.06em; text-transform:uppercase; width:fit-content;">
                                {{ strtoupper(str_replace('_', ' ', $customer->doc_type)) }}
                            </span>
                        @endif
                        <span>{{ $customer->doc_no ?: '—' }}</span>
                    </div>

                    {{-- Type · KYC · Status --}}
                    <div style="display:flex; flex-direction:column; gap:4px; align-items:flex-start;">
                        {{-- Type --}}
                        @if($customer->type === 'individual')
                            <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px;
                                         background:var(--rt-bg); color:var(--rt-fg);
                                         font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                                <span style="width:5px; height:5px; border-radius:50%; background:var(--rt-fg);"></span>
                                Individual
                            </span>
                        @else
                            <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px;
                                         background:var(--sd-bg); color:var(--sd-fg);
                                         font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                                <span style="width:5px; height:5px; border-radius:50%; background:var(--sd-fg);"></span>
                                Company
                            </span>
                        @endif
                        {{-- KYC --}}
                        @php
                            $kycColors = [
                                'verified' => ['bg' => 'var(--av-bg)', 'fg' => 'var(--av-fg)'],
                                'pending'  => ['bg' => 'var(--bk-bg)', 'fg' => 'var(--bk-fg)'],
                                'rejected' => ['bg' => 'var(--rj-bg)', 'fg' => 'var(--rj-fg)'],
                            ];
                            $kyc = $kycColors[$customer->kyc_status] ?? $kycColors['pending'];
                        @endphp
                        <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px;
                                     background:{{ $kyc['bg'] }}; color:{{ $kyc['fg'] }};
                                     font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                            <span style="width:5px; height:5px; border-radius:50%; background:{{ $kyc['fg'] }};"></span>
                            KYC {{ ucfirst($customer->kyc_status) }}
                        </span>
                        {{-- Status --}}
                        @php
                            $stColors = [
                                'active'    => ['bg' => 'var(--av-bg)', 'fg' => 'var(--av-fg)'],
                                'inactive'  => ['bg' => 'var(--in-bg)', 'fg' => 'var(--in-fg)'],
                                'suspended' => ['bg' => 'var(--rj-bg)', 'fg' => 'var(--rj-fg)'],
                            ];
                            $st = $stColors[$customer->status] ?? $stColors['inactive'];
                        @endphp
                        <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px;
                                     background:{{ $st['bg'] }}; color:{{ $st['fg'] }};
                                     font:600 10px 'Inter', sans-serif; letter-spacing:.04em; text-transform:uppercase;">
                            <span style="width:5px; height:5px; border-radius:50%; background:{{ $st['fg'] }};"></span>
                            {{ ucfirst($customer->status) }}
                        </span>
                    </div>

                    {{-- Actions --}}
                    <div style="text-align:right; display:flex; gap:4px; justify-content:flex-end; color:var(--ink-3);">
                        <a href="{{ route('admin.crm.customers.show', $customer) }}"
                            title="View"
                            style="width:28px; height:28px; border-radius:5px; display:flex; align-items:center; justify-content:center;
                                   cursor:pointer; color:var(--ink-3); text-decoration:none;"
                            class="hover:bg-black/5 hover:text-[var(--ink-1)] transition-colors">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        @can('customer.edit')
                            <button wire:click="openEdit({{ $customer->id }})"
                                title="Edit"
                                style="width:28px; height:28px; border-radius:5px; display:flex; align-items:center; justify-content:center;
                                       cursor:pointer; background:transparent; border:0; color:var(--ink-3);"
                                class="hover:bg-black/5 hover:text-[var(--ink-1)] transition-colors">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                        @endcan
                        @can('customer.delete')
                            <button
                                x-data
                                @click="if(confirm('Delete this customer?')) { $wire.deleteCustomer({{ $customer->id }}) }"
                                title="Delete"
                                style="width:28px; height:28px; border-radius:5px; display:flex; align-items:center; justify-content:center;
                                       cursor:pointer; background:transparent; border:0; color:var(--rj-fg);"
                                class="hover:bg-[var(--rj-bg)] transition-colors">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                            </button>
                        @endcan
                    </div>
                </div>
            @empty
                <div style="padding:48px; text-align:center; color:var(--ink-3); font:13px 'Inter', sans-serif;">
                    No customers found.
                </div>
            @endforelse

            {{-- Pagination footer --}}
            <div style="padding:12px 18px; border-top:1px solid var(--rule); background:rgba(0,0,0,.012);
                        display:flex; justify-content:space-between; align-items:center;
                        font:11.5px var(--mono); color:var(--ink-3);">
                <span>
                    Showing {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }}
                    of {{ $customers->total() }} customers
                </span>
                <div>
                    {{ $customers->links() }}
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
        {{-- Head --}}
        <div style="padding:18px 24px; border-bottom:1px solid var(--rule); background:var(--paper);
                    display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:16px; font-weight:600;">
                    {{ $editingId ? 'Edit Customer' : 'New Customer' }}
                </h3>
                <div style="margin-top:2px; font:500 11px var(--mono); color:var(--ink-3); letter-spacing:.04em; text-transform:uppercase;">
                    {{ $editingId ? 'Update customer information' : 'Fill in identity, contact and KYC details' }}
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

        {{-- Multi-step progress bar --}}
        <div style="padding:20px 28px 16px; background:var(--paper); border-bottom:1px solid var(--rule);">
            @php $stepLabels = ['Identity', 'Contact', 'Documents', 'Notes']; @endphp
            <div style="display:flex; align-items:flex-start;">
                @foreach($stepLabels as $i => $label)
                    @php $n = $i + 1; @endphp
                    {{-- Step node --}}
                    <div style="display:flex; flex-direction:column; align-items:center; gap:5px; flex:0 0 auto; width:60px;">
                        <div style="width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; transition:background .2s, border-color .2s; font:700 12px 'Inter', sans-serif;"
                            :style="step > {{ $n }}
                                ? 'background:var(--av-fg); border:2px solid var(--av-fg); color:#fff;'
                                : step === {{ $n }}
                                    ? 'background:var(--accent); border:2px solid var(--accent); color:#fff;'
                                    : 'background:var(--paper); border:2px solid var(--rule); color:var(--ink-3);'">
                            <span x-show="step > {{ $n }}" style="display:none; line-height:1;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                            <span x-show="step <= {{ $n }}">{{ $n }}</span>
                        </div>
                        <span style="font:500 10px 'Inter', sans-serif; text-align:center; white-space:nowrap; transition:color .2s;"
                            :style="step >= {{ $n }} ? 'color:var(--ink-1); font-weight:600;' : 'color:var(--ink-3);'">
                            {{ $label }}
                        </span>
                    </div>
                    {{-- Connector line --}}
                    @if($i < count($stepLabels) - 1)
                        <div style="flex:1; height:2px; margin-top:14px; border-radius:2px; transition:background .3s;"
                            :style="step > {{ $n }} ? 'background:var(--av-fg);' : 'background:var(--rule);'">
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Body --}}
        <div style="flex:1; overflow-y:auto; padding:24px; display:flex; flex-direction:column; gap:18px;">

            {{-- ─ STEP 1 : IDENTITY ─ --}}
            <div x-show="step === 1" style="display:flex; flex-direction:column; gap:18px;">

                {{-- Customer type --}}
                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:14px;">
                        <h4 style="margin:0; font-size:13px; font-weight:600;">Customer type</h4>
                        <span style="font:11px var(--mono); color:var(--ink-3);">required</span>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                        <label style="display:flex; flex-direction:column; gap:4px; padding:14px; border-radius:8px; cursor:pointer; transition: border-color .12s, background .12s;"
                            :style="dType === 'individual'
                                ? 'border:1px solid var(--rt-fg); background:var(--rt-bg);'
                                : 'border:1px solid var(--rule); background:var(--paper);'">
                            <input type="radio" x-model="dType" value="individual" style="display:none;" />
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;"
                                    :style="dType === 'individual' ? 'background:var(--rt-fg); color:var(--paper);' : 'background:var(--in-bg); color:var(--in-fg);'">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></svg>
                                </span>
                                <span style="font:600 13px 'Inter', sans-serif;">Individual</span>
                            </div>
                            <span style="font-size:11px; color:var(--ink-3); padding-left:40px;">A single person with NID/Passport</span>
                        </label>
                        <label style="display:flex; flex-direction:column; gap:4px; padding:14px; border-radius:8px; cursor:pointer; transition: border-color .12s, background .12s;"
                            :style="dType === 'company'
                                ? 'border:1px solid var(--sd-fg); background:var(--sd-bg);'
                                : 'border:1px solid var(--rule); background:var(--paper);'">
                            <input type="radio" x-model="dType" value="company" style="display:none;" />
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;"
                                    :style="dType === 'company' ? 'background:var(--sd-fg); color:var(--paper);' : 'background:var(--in-bg); color:var(--in-fg);'">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21V8l9-5 9 5v13"/><path d="M9 21V12h6v9"/></svg>
                                </span>
                                <span style="font:600 13px 'Inter', sans-serif;">Company</span>
                            </div>
                            <span style="font-size:11px; color:var(--ink-3); padding-left:40px;">Registered business with TIN/TaxID</span>
                        </label>
                    </div>
                    @error('dType') <p style="margin-top:6px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                </div>

                {{-- Profile placeholder --}}
                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:14px;">
                        <h4 style="margin:0; font-size:13px; font-weight:600;">Profile</h4>
                        <span style="font:11px var(--mono); color:var(--ink-3);">photo optional</span>
                    </div>
                    <div style="display:flex; gap:18px; align-items:center;">
                        <div style="width:88px; height:88px; border-radius:50%;
                                    background:linear-gradient(135deg, #8a7a5a, #5c4f38);
                                    border:3px solid var(--paper); box-shadow: 0 0 0 1px var(--rule);
                                    display:flex; align-items:center; justify-content:center;
                                    color:rgba(255,255,255,.7); flex-shrink:0;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></svg>
                        </div>
                        <div style="font-size:12px; color:var(--ink-2); line-height:1.6;">
                            <b style="color:var(--ink-1); font-weight:600;">Profile image</b><br/>
                            JPG or PNG, max 2 MB. Saved as profile_image_id FK.
                        </div>
                    </div>
                </div>

                {{-- Personal information --}}
                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:14px;">
                        <h4 style="margin:0; font-size:13px; font-weight:600;">Personal information</h4>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Customer ID</label>
                            <input type="text" value="{{ $editingId ? \App\Models\Customer::find($editingId)?->customer_id : 'Auto-generated' }}" readonly
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule);
                                       background:rgba(0,0,0,.025); color:var(--ink-2); padding:9px 12px; border-radius:7px;
                                       font-family:'IBM Plex Mono', monospace; font-size:13px; cursor:not-allowed;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                                Customer name <span style="color:var(--rj-fg)">*</span>
                            </label>
                            <input wire:model="dName" type="text" placeholder="Full name as on NID"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule);
                                       background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                            @error('dName') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Father's name</label>
                            <input wire:model="dFatherName" type="text"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Mother's name</label>
                            <input wire:model="dMotherName" type="text"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Date of birth</label>
                            <input wire:model="dDob" type="date"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Gender</label>
                            <select wire:model="dGender"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                                <option value="">— Select —</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                                <option value="prefer_not_to_say">Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Company details (only if company) --}}
                <div x-show="dType === 'company'"
                    style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:14px;">
                        <h4 style="margin:0; font-size:13px; font-weight:600;">Company details</h4>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                        <div style="grid-column:span 2;">
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Company name</label>
                            <input wire:model="dCompanyName" type="text" placeholder="Legal registered name"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Registration #</label>
                            <input wire:model="dCompanyRegNo" type="text" placeholder="e.g. C-123456"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Tax ID / TIN</label>
                            <input wire:model="dCompanyTaxId" type="text"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─ STEP 2 : CONTACT & ADDRESS ─ --}}
            <div x-show="step === 2" style="display:flex; flex-direction:column; gap:18px;">
                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="margin-bottom:14px;"><h4 style="margin:0; font-size:13px; font-weight:600;">Contact</h4></div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">
                                Phone <span style="color:var(--rj-fg)">*</span>
                            </label>
                            <input wire:model="dPhone" type="text" placeholder="+880 1XXX XXXXXX"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                            @error('dPhone') <p style="margin-top:4px; font-size:11px; color:var(--rj-fg);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Alternate phone</label>
                            <input wire:model="dPhoneAlt" type="text"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        </div>
                        <div style="grid-column:span 2;">
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Email</label>
                            <input wire:model="dEmail" type="email" placeholder="customer@example.com"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                        </div>
                    </div>
                </div>

                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="margin-bottom:14px;"><h4 style="margin:0; font-size:13px; font-weight:600;">Address</h4></div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                        <div style="grid-column:span 2;">
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Address line</label>
                            <textarea wire:model="dAddress" placeholder="House, Road, Area" rows="2"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif; resize:vertical;"></textarea>
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">District</label>
                            <input wire:model="dDistrict" type="text" placeholder="Dhaka"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Division</label>
                            <input wire:model="dDivision" type="text" placeholder="Dhaka"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Postal code</label>
                            <input wire:model="dPostalCode" type="text" placeholder="1212"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─ STEP 3 : DOCUMENTS & KYC ─ --}}
            <div x-show="step === 3" style="display:flex; flex-direction:column; gap:18px;">
                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:14px;">
                        <h4 style="margin:0; font-size:13px; font-weight:600;">Identity document</h4>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Document type</label>
                            <select wire:model="dDocType"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                                <option value="">— Select —</option>
                                <option value="nid">National ID (NID)</option>
                                <option value="passport">Passport</option>
                                <option value="driving_licence">Driving licence</option>
                                <option value="birth_certificate">Birth certificate</option>
                                <option value="trade_licence">Trade licence</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Document number</label>
                            <input wire:model="dDocNo" type="text" placeholder="1234567890"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Issue date</label>
                            <input wire:model="dDocIssueDate" type="date"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Expiry date</label>
                            <input wire:model="dDocExpiryDate" type="date"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        </div>
                    </div>
                </div>

                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="margin-bottom:14px;"><h4 style="margin:0; font-size:13px; font-weight:600;">KYC status</h4></div>
                    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px;">
                        @foreach(['pending' => ['label' => 'Pending', 'bg' => 'var(--bk-bg)', 'fg' => 'var(--bk-fg)'], 'verified' => ['label' => 'Verified', 'bg' => 'var(--av-bg)', 'fg' => 'var(--av-fg)'], 'rejected' => ['label' => 'Rejected', 'bg' => 'var(--rj-bg)', 'fg' => 'var(--rj-fg)']] as $val => $cfg)
                            <label style="display:flex; align-items:center; justify-content:center; gap:6px; padding:9px; border-radius:7px; cursor:pointer; font:600 11px 'Inter', sans-serif; text-transform:uppercase; letter-spacing:.04em; transition: background .12s, border-color .12s;"
                                :style="$wire.dKycStatus === '{{ $val }}'
                                    ? 'background:{{ $cfg['bg'] }}; border:1px solid {{ $cfg['fg'] }}; color:{{ $cfg['fg'] }};'
                                    : 'background:var(--paper); border:1px solid var(--rule); color:var(--ink-2);'">
                                <input type="radio" wire:model="dKycStatus" value="{{ $val }}" style="display:none;" />
                                {{ $cfg['label'] }}
                            </label>
                        @endforeach
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px; margin-top:12px;">
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">KYC date</label>
                            <input wire:model="dKycDate" type="date"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px;" />
                        </div>
                        <div>
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Account status</label>
                            <select wire:model="dStatus"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <div style="grid-column:span 2;">
                            <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Source</label>
                            <select wire:model="dSource"
                                style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif;">
                                <option value="">— Select source —</option>
                                <option value="walk_in">Walk-in</option>
                                <option value="website">Website</option>
                                <option value="referral">Referral</option>
                                <option value="facebook_ad">Facebook ad</option>
                                <option value="property_fair">Property fair</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─ STEP 4 : NOTES, ATTACHMENTS & ACTIVITY ─ --}}
            <div x-show="step === 4" style="display:flex; flex-direction:column; gap:18px;">
                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="margin-bottom:14px;"><h4 style="margin:0; font-size:13px; font-weight:600;">Notes</h4></div>
                    <textarea wire:model="dNotes" placeholder="Internal notes about this customer…" rows="4"
                        style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-1); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif; resize:vertical; min-height:80px;"></textarea>
                </div>
                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="margin-bottom:10px;"><h4 style="margin:0; font-size:13px; font-weight:600;">Additional files</h4></div>
                    <div style="font-size:12px; color:var(--ink-2); line-height:1.6; margin-bottom:10px;">
                        Any extra documents — bank statements, address proof, references.
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:32px;
                                border:1.5px dashed var(--ink-3); border-radius:8px; color:var(--ink-2);
                                font:600 11px 'Inter', sans-serif; gap:8px; cursor:pointer;"
                        class="hover:border-[var(--ink-1)] hover:text-[var(--ink-1)] transition-colors">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Upload files
                    </div>
                </div>
            </div>

            {{-- Activity & Metadata (part of step 4) --}}
            <div x-show="step === 4" style="display:flex; flex-direction:column; gap:18px;">
                <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                    <div style="margin-bottom:14px;"><h4 style="margin:0; font-size:13px; font-weight:600;">Activity log</h4></div>
                    @if($editingId)
                        @php $editingCustomer = \App\Models\Customer::find($editingId); @endphp
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <div style="display:flex; gap:10px; align-items:flex-start;">
                                <div style="width:8px; height:8px; border-radius:50%; background:var(--av-fg); margin-top:7px; flex-shrink:0;"></div>
                                <div>
                                    <div style="font:500 12.5px 'Inter', sans-serif;">
                                        <b>{{ $editingCustomer?->updatedByUser?->name ?? 'System' }}</b> last updated record
                                    </div>
                                    <div style="font:11px var(--mono); color:var(--ink-3); margin-top:1px;">
                                        {{ $editingCustomer?->updated_at?->format('Y-m-d H:i') }}
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex; gap:10px; align-items:flex-start;">
                                <div style="width:8px; height:8px; border-radius:50%; background:var(--ink-3); margin-top:7px; flex-shrink:0;"></div>
                                <div>
                                    <div style="font:500 12.5px 'Inter', sans-serif;">
                                        <b>{{ $editingCustomer?->createdByUser?->name ?? 'System' }}</b> created customer record
                                    </div>
                                    <div style="font:11px var(--mono); color:var(--ink-3); margin-top:1px;">
                                        {{ $editingCustomer?->created_at?->format('Y-m-d H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div style="font:13px 'Inter', sans-serif; color:var(--ink-3); padding:16px 0; text-align:center;">
                            Activity will appear here after the customer is created.
                        </div>
                    @endif
                </div>

                @if($editingId)
                    @php $editingCustomer = \App\Models\Customer::find($editingId); @endphp
                    <div style="background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:18px 20px;">
                        <div style="margin-bottom:14px;"><h4 style="margin:0; font-size:13px; font-weight:600;">Metadata</h4></div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 14px;">
                            <div>
                                <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Created by</label>
                                <input type="text" value="{{ $editingCustomer?->createdByUser?->name ?? '—' }}" readonly
                                    style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:rgba(0,0,0,.025); color:var(--ink-2); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif; cursor:not-allowed;" />
                            </div>
                            <div>
                                <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Created at</label>
                                <input type="text" value="{{ $editingCustomer?->created_at?->format('Y-m-d H:i') ?? '—' }}" readonly
                                    style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:rgba(0,0,0,.025); color:var(--ink-2); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px; cursor:not-allowed;" />
                            </div>
                            <div>
                                <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Modified by</label>
                                <input type="text" value="{{ $editingCustomer?->updatedByUser?->name ?? '—' }}" readonly
                                    style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:rgba(0,0,0,.025); color:var(--ink-2); padding:9px 12px; border-radius:7px; font:13px 'Inter', sans-serif; cursor:not-allowed;" />
                            </div>
                            <div>
                                <label style="display:block; font:600 10px 'Inter', sans-serif; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-3); margin-bottom:5px;">Modified at</label>
                                <input type="text" value="{{ $editingCustomer?->updated_at?->format('Y-m-d H:i') ?? '—' }}" readonly
                                    style="width:100%; appearance:none; outline:none; border:1px solid var(--rule); background:rgba(0,0,0,.025); color:var(--ink-2); padding:9px 12px; border-radius:7px; font-family:'IBM Plex Mono', monospace; font-size:13px; cursor:not-allowed;" />
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>

        {{-- Footer --}}
        <div style="border-top:1px solid var(--rule); background:var(--paper);">

            {{-- Validation error summary (only visible when errors present) --}}
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

            <div style="padding:14px 24px; display:flex; justify-content:space-between; align-items:center;">

                {{-- Left: Cancel (step 1) or Back (steps 2-4) --}}
                <button
                    @click="step === 1 ? $wire.closeDrawer() : step--"
                    style="appearance:none; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2);
                           padding:7px 16px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                           display:inline-flex; align-items:center; gap:6px; transition:background .15s;">
                    <span x-show="step === 1">Cancel</span>
                    <span x-show="step > 1" style="display:none; align-items:center; gap:6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                        Back
                    </span>
                </button>

                {{-- Centre: step dots --}}
                <div style="display:flex; gap:6px; align-items:center;">
                    <div style="width:7px; height:7px; border-radius:50%; transition:background .2s, transform .2s;"
                        :style="step === 1 ? 'background:var(--accent); transform:scale(1.3);' : (step > 1 ? 'background:var(--av-fg);' : 'background:var(--rule);')"></div>
                    <div style="width:7px; height:7px; border-radius:50%; transition:background .2s, transform .2s;"
                        :style="step === 2 ? 'background:var(--accent); transform:scale(1.3);' : (step > 2 ? 'background:var(--av-fg);' : 'background:var(--rule);')"></div>
                    <div style="width:7px; height:7px; border-radius:50%; transition:background .2s, transform .2s;"
                        :style="step === 3 ? 'background:var(--accent); transform:scale(1.3);' : (step > 3 ? 'background:var(--av-fg);' : 'background:var(--rule);')"></div>
                    <div style="width:7px; height:7px; border-radius:50%; transition:background .2s, transform .2s;"
                        :style="step === 4 ? 'background:var(--accent); transform:scale(1.3);' : 'background:var(--rule);'"></div>
                </div>

                {{-- Right: Next (steps 1-3) or Submit (step 4) --}}
                <div style="display:flex; align-items:center;">
                    <button x-show="step < 4"
                        @click="step++"
                        style="appearance:none; border:1px solid var(--ink-1); background:var(--ink-1); color:var(--paper);
                               padding:7px 16px; font:500 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                               display:inline-flex; align-items:center; gap:6px; transition:opacity .15s;">
                        Next
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <button x-show="step === 4" x-cloak
                        wire:click="saveCustomer"
                        wire:loading.attr="disabled"
                        style="appearance:none; border:1px solid var(--accent); background:var(--accent); color:#fff;
                               padding:7px 18px; font:600 12px 'Inter', sans-serif; border-radius:6px; cursor:pointer;
                               display:inline-flex; align-items:center; gap:6px;">
                        <span wire:loading.remove wire:target="saveCustomer" style="display:inline-flex; align-items:center; gap:6px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ $editingId ? 'Update customer' : 'Save customer' }}
                        </span>
                        <span wire:loading wire:target="saveCustomer">Saving…</span>
                    </button>
                </div>

            </div>
        </div>
    </aside>

</div>
