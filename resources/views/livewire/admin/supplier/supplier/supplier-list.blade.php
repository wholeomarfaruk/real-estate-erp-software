{{--
    resources/views/livewire/admin/supplier/supplier/supplier-list.blade.php
    Star Unity ERP · Supplier list  —  Livewire 3.6 + Alpine.js + Tailwind 4 / suppliers.css

    Interaction split:
      Alpine  → modal open/close, row kebab menus, document rows  (instant, client-side)
      Livewire→ data, filters, pagination, save, status actions   (server state)
--}}

@php
    $bdt = function ($n) {
        $a = abs($n);
        if ($a >= 1_000_000) return '৳ ' . rtrim(rtrim(number_format($a / 1_000_000, 2), '0'), '.') . 'M';
        if ($a >= 1_000)     return '৳ ' . round($a / 1000) . 'K';
        return '৳ ' . number_format($a);
    };
    $balCell = function ($balance, $overdue = false) use ($bdt) {
        if ($balance < 0)  return [$overdue ? 'over' : 'due', $bdt($balance), $overdue ? 'overdue' : 'payable'];
        if ($balance > 0)  return ['adv', $bdt($balance), 'advance'];
        return ['flat', '৳ 0', 'settled'];
    };
    $avatarAlt = ['alt-1','alt-2','alt-3','alt-4','alt-5'];
@endphp

<div
    class="su-root"
    x-data="{}"
    x-on:keydown.escape.window="$wire.closeModal()"
>
<main class="page">

    {{-- ── Breadcrumb ─────────────────────────────────────────────── --}}
    <div class="crumb">
        <a href="{{ route('admin.dashboard') }}" wire:navigate>Dashboard</a>
        <span class="sep">/</span>
        <span class="crumb-now">Suppliers</span>
    </div>

    {{-- ── Header ─────────────────────────────────────────────────── --}}
    <div class="page-head">
        <div>
            <div class="page-title">Suppliers</div>
            <div class="page-sub">Vendors and material suppliers — payables, invoices and compliance in one place.</div>
        </div>
        <div class="right">
            <button class="btn" wire:click="export" wire:loading.attr="disabled">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export
            </button>
            @can('supplier.create')
            <button class="btn btn-primary" wire:click="openCreate">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New supplier
            </button>
            @endcan
        </div>
    </div>

    {{-- ── KPI strip ──────────────────────────────────────────────── --}}
    @php $st = $this->stats; @endphp
    <section class="kpi-strip">
        <div class="kpi">
            <div class="kpi-lbl">Total suppliers</div>
            <div class="kpi-val">{{ number_format($st['total']) }}</div>
            <div class="kpi-foot">all vendors on record</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Active / Inactive / Blocked</div>
            <div class="kpi-val" style="font-size:18px;">
                <span style="color:var(--av-fg)">{{ $st['active'] }}</span>
                <span style="color:var(--ink-3); font-size:13px;">/</span>
                <span style="color:var(--bk-fg)">{{ $st['inactive'] }}</span>
                <span style="color:var(--ink-3); font-size:13px;">/</span>
                <span style="color:var(--rj-fg)">{{ $st['blocked'] }}</span>
            </div>
            @php $tot = max($st['total'], 1); @endphp
            <div class="stack">
                <span style="width:{{ $st['active']   / $tot * 100 }}%; background:var(--av-fg);"></span>
                <span style="width:{{ $st['inactive'] / $tot * 100 }}%; background:var(--bk-fg);"></span>
                <span style="width:{{ $st['blocked']  / $tot * 100 }}%; background:var(--rj-fg);"></span>
            </div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Total payable (due)</div>
            <div class="kpi-val" style="color:var(--bk-fg)">{{ $bdt($st['payable']) }}</div>
            <div class="kpi-foot">owed to suppliers</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Advance paid</div>
            <div class="kpi-val" style="color:var(--av-fg)">{{ $bdt($st['advance']) }}</div>
            <div class="kpi-foot">held by suppliers</div>
        </div>
        <div class="kpi">
            <div class="kpi-lbl">Purchase invoices</div>
            <div class="kpi-val">{{ number_format($st['invoices']) }}</div>
            <div class="kpi-foot">across all suppliers</div>
        </div>
    </section>

    {{-- ── Filters ────────────────────────────────────────────────── --}}
    <section class="filters">
        <div class="search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search by code, name, contact, phone, email or address…" />
        </div>

        <div class="pill-group">
            <button wire:click="setStatus('all')"      @class(['active' => $statusFilter === 'all'])>All <span class="cnt">{{ $st['total'] }}</span></button>
            <button wire:click="setStatus('active')"   @class(['active' => $statusFilter === 'active'])>Active <span class="cnt">{{ $st['active'] }}</span></button>
            <button wire:click="setStatus('inactive')" @class(['active' => $statusFilter === 'inactive'])>Inactive <span class="cnt">{{ $st['inactive'] }}</span></button>
            <button wire:click="setStatus('blocked')"  @class(['active' => $statusFilter === 'blocked'])>Blocked <span class="cnt">{{ $st['blocked'] }}</span></button>
        </div>

        <select class="select-inline" wire:model.live="balanceFilter">
            <option value="all">All balances</option>
            <option value="due">Has due</option>
            <option value="advance">Has advance</option>
            <option value="settled">Settled</option>
        </select>

        <select class="select-inline" wire:model.live="sortBy">
            <option value="recent">Newest first</option>
            <option value="due">Highest due</option>
            <option value="invoices">Most invoices</option>
            <option value="name">Name A–Z</option>
        </select>
    </section>

    {{-- ── List ───────────────────────────────────────────────────── --}}
    <section class="list-block">
      <div class="list-scroll">
        <div class="list-head grid-cols" style="min-width:720px;">
            <div></div>
            <div>Supplier</div>
            <div>Contact</div>
            <div class="num">Invoices</div>
            <div class="num">Balance</div>
            <div style="text-align:right;">Actions</div>
        </div>

        @forelse ($suppliers as $s)
            @php
                $key     = $s->status_key;
                $overdue = ($s->balance < 0) && ($s->unpaid_invoices_count > 0);
                [$balClass, $balAmt, $balTag] = $balCell($s->balance, $overdue);
                $avatar  = $key === 'blocked' ? 'blocked' : $avatarAlt[$loop->index % 5];
                $initials = collect(explode(' ', str_replace(['&','.',','], ' ', $s->name)))
                    ->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
            @endphp

            <div class="list-row grid-cols" wire:key="sup-{{ $s->id }}" style="min-width:720px;">
                <div><div class="sup-avatar {{ $avatar }}">{{ strtoupper($initials) }}</div></div>

                <div>
                    <div class="sup-name">{{ $s->name }}</div>
                    <div class="sup-code">{{ $s->code }} · since {{ $s->created_at?->format('Y-m-d') }}</div>
                    <div class="sup-addr">{{ $s->address ?: '—' }}</div>
                </div>

                <div class="sup-contact">
                    <span class="person">{{ $s->contact_person ?: '—' }}</span>
                    <span class="mono">{{ $s->phone ?: '—' }}</span>
                    @if ($s->alternate_phone)
                        <span class="mono alt">{{ $s->alternate_phone }}</span>
                    @endif
                </div>

                <div class="inv c-inv">
                    <div class="num">{{ $s->purchase_invoices_count }}</div>
                    <div class="sub">
                        @if ($s->unpaid_invoices_count) <b>{{ $s->unpaid_invoices_count }} unpaid</b> @else all paid @endif
                    </div>
                </div>

                <div>
                    <div class="bal {{ $balClass }}">
                        <div class="amt">{{ $balAmt }}</div>
                        <div class="tag">{{ $balTag }}</div>
                    </div>
                    <div style="text-align:right; margin-top:6px;">
                        <span class="pill {{ $key }}"><span class="dot"></span>{{ $key }}</span>
                    </div>
                </div>

                {{-- Kebab menu — Alpine local state, opens instantly --}}
                <div class="row-act" x-data="{ open: false }" @click.stop>
                    @can('supplier.view')
                    <span class="icon" title="View" wire:click="view({{ $s->id }})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                    @endcan
                    <span class="icon" title="More" @click="open = !open">
                        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
                    </span>
                    <div class="menu" x-show="open" x-cloak @click.outside="open = false" x-transition.opacity.duration.120ms>
                        @can('supplier.view')
                        <button wire:click="view({{ $s->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>View detail</button>
                        <button wire:click="downloadPo({{ $s->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Download POs</button>
                        @endcan
                        @can('supplier.edit')
                        <button wire:click="edit({{ $s->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit supplier</button>
                        @endcan
                        @can('supplier.status.change')
                        <div class="div"></div>
                        @if ($key === 'active')
                            <button wire:click="toggleActive({{ $s->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>Deactivate</button>
                        @else
                            <button wire:click="toggleActive({{ $s->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>Activate</button>
                        @endif
                        @if ($key === 'blocked')
                            <button wire:click="unblock({{ $s->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>Unblock</button>
                        @else
                            <button class="danger" wire:click="block({{ $s->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Block supplier</button>
                        @endif
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="empty" style="min-width:720px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <div class="t">No suppliers match</div>
                <div class="s">Try a different search or clear the filters.</div>
            </div>
        @endforelse
      </div>{{-- /.list-scroll --}}

        {{-- ── Footer / pager ──────────────────────────────────────── --}}
        <div class="list-foot">
            <span>
                Showing <b>{{ $suppliers->firstItem() ?? 0 }}–{{ $suppliers->lastItem() ?? 0 }}</b>
                of <b>{{ number_format($suppliers->total()) }}</b> suppliers
            </span>
            @if ($suppliers->hasPages())
                <div class="pager">
                    <button wire:click="previousPage" @disabled($suppliers->onFirstPage())>‹</button>
                    @foreach ($suppliers->getUrlRange(max(1, $suppliers->currentPage() - 1), min($suppliers->lastPage(), $suppliers->currentPage() + 1)) as $page => $url)
                        <button wire:click="gotoPage({{ $page }})" @class(['active' => $page == $suppliers->currentPage()])>{{ $page }}</button>
                    @endforeach
                    <button wire:click="nextPage" @disabled(! $suppliers->hasMorePages())>›</button>
                </div>
            @endif
        </div>
    </section>
</main>

{{-- ════════════════════════════════════════════════════════════════
     SUPPLIER FORM MODAL — extracted partial, shared with the detail page.
     ════════════════════════════════════════════════════════════════ --}}
@include('livewire.admin.supplier.supplier.partials.supplier-form-modal')

</div>
