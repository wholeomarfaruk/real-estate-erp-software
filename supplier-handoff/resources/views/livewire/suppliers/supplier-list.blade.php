{{--
    resources/views/livewire/suppliers/supplier-list.blade.php
    Star Unity ERP · Supplier list  —  Livewire 3.6 + Alpine.js + Tailwind 4

    Interaction split (per the brief — keep UI snappy, no needless round-trips):
      • Alpine  → modal open/close, row kebab menus, document rows  (instant, client-side)
      • Livewire→ data, filters, pagination, save, status actions   (server state)

    Requires alpinejs (bundled with Livewire 3) and the @php helpers below.
--}}

@php
    // Compact BDT formatter to match the mockup (৳ 842K, ৳ 1.26M, ৳ 0)
    $bdt = function ($n) {
        $a = abs($n);
        if ($a >= 1_000_000) return '৳ ' . rtrim(rtrim(number_format($a / 1_000_000, 2), '0'), '.') . 'M';
        if ($a >= 1_000)     return '৳ ' . round($a / 1000) . 'K';
        return '৳ ' . number_format($a);
    };
    // Balance cell → [css class, formatted amount, tag]. Negative = we owe (payable/overdue).
    $balCell = function ($balance, $overdue = false) use ($bdt) {
        if ($balance < 0)  return [$overdue ? 'over' : 'due', $bdt($balance), $overdue ? 'overdue' : 'payable'];
        if ($balance > 0)  return ['adv', $bdt($balance), 'advance'];
        return ['flat', '৳ 0', 'settled'];
    };
    $avatarAlt = ['alt-1','alt-2','alt-3','alt-4','alt-5'];
@endphp

<div
    class="su-root"
    x-data="{ modalOpen: false }"
    x-on:supplier-saved.window="modalOpen = false"
    x-on:keydown.escape.window="modalOpen = false"
>
<main class="page">

    {{-- ── Breadcrumb ─────────────────────────────────────────────── --}}
    <div class="crumb">
        <a href="#">Purchases</a>
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
            {{-- Button exists regardless of backend readiness; export() is a stub for now --}}
            <button class="btn" wire:click="export" wire:loading.attr="disabled">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export
            </button>
            <button class="btn btn-primary" @click="modalOpen = true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New supplier
            </button>
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
        <div class="list-head grid-cols">
            <div></div>
            <div>Supplier</div>
            <div>Contact</div>
            <div class="num">Invoices</div>
            <div class="num">Balance</div>
            <div style="text-align:right;">Actions</div>
        </div>

        @forelse ($suppliers as $s)
            @php
                $key   = $s->status_key;                              // active | inactive | blocked
                $overdue = ($s->balance < 0) && ($s->unpaid_invoices_count > 0);
                [$balClass, $balAmt, $balTag] = $balCell($s->balance, $overdue);
                $avatar = $key === 'blocked' ? 'blocked' : $avatarAlt[$loop->index % 5];
                $initials = collect(explode(' ', str_replace(['&','.',','], ' ', $s->name)))
                    ->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
            @endphp

            <div class="list-row grid-cols" wire:key="sup-{{ $s->id }}" wire:click="view({{ $s->id }})">
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

                {{-- Kebab menu — Alpine local state, opens instantly, never hits the server --}}
                <div class="row-act" x-data="{ open: false }" @click.stop>
                    <span class="icon" title="View" wire:click="view({{ $s->id }})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                    <span class="icon" title="More" @click="open = !open">
                        <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
                    </span>
                    <div class="menu" x-show="open" x-cloak @click.outside="open = false" x-transition.opacity.duration.120ms>
                        <button wire:click="view({{ $s->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>View detail</button>
                        <button wire:click="edit({{ $s->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit supplier</button>
                        <button wire:click="downloadPo({{ $s->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Download POs</button>
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
                    </div>
                </div>
            </div>
        @empty
            <div class="empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <div class="t">No suppliers match</div>
                <div class="s">Try a different search or clear the filters.</div>
            </div>
        @endforelse

        {{-- ── Footer / pager (custom — matches the mockup) ──────────── --}}
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
     NEW SUPPLIER MODAL  — Alpine controls visibility, Livewire the form
     ════════════════════════════════════════════════════════════════ --}}
<div class="scrim" x-show="modalOpen" x-cloak
     x-transition.opacity.duration.200ms
     @click.self="modalOpen = false">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="supModalTitle"
         x-data="{ docs: [] }"
         x-transition:enter="" x-transition:enter-start="" >
        <div class="modal-head">
            <div>
                <h3 id="supModalTitle">New supplier</h3>
                <div class="sub">Code auto-generated · SUP-{{ str_pad($nextCode, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
            <button class="close" @click="modalOpen = false" aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="modal-body">

            {{-- Basic --}}
            <section class="section">
                <div class="section-title">
                    <h4><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21V8l9-5 9 5v13"/><path d="M9 21V12h6v9"/></svg></span>Basic information</h4>
                    <span class="hint">required *</span>
                </div>
                <div class="grid-2">
                    <div class="span-2">
                        <label class="field-label">Supplier name <span class="req">*</span></label>
                        <input class="input" wire:model="form.name" placeholder="e.g. Meghna Cement & Aggregates Ltd." />
                        @error('form.name') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="field-label">Code</label>
                        <input class="input mono" value="SUP-{{ str_pad($nextCode, 6, '0', STR_PAD_LEFT) }}" readonly />
                    </div>
                    <div>
                        <label class="field-label">Status</label>
                        <div class="seg-status">
                            <input type="radio" id="ss_active"   value="active"   wire:model="form.status" />
                            <label for="ss_active" class="active"><span class="dot"></span>Active</label>
                            <input type="radio" id="ss_inactive" value="inactive" wire:model="form.status" />
                            <label for="ss_inactive" class="inactive"><span class="dot"></span>Inactive</label>
                            <input type="radio" id="ss_blocked"  value="blocked"  wire:model="form.status" />
                            <label for="ss_blocked" class="blocked"><span class="dot"></span>Blocked</label>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Contact --}}
            <section class="section">
                <div class="section-title">
                    <h4><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>Contact</h4>
                </div>
                <div class="grid-2">
                    <div>
                        <label class="field-label">Contact person</label>
                        <input class="input" wire:model="form.contact_person" placeholder="Full name" />
                    </div>
                    <div>
                        <label class="field-label">Email</label>
                        <input class="input" type="email" wire:model="form.email" placeholder="accounts@supplier.com" />
                        @error('form.email') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="field-label">Phone <span class="req">*</span></label>
                        <input class="input mono" wire:model="form.phone" placeholder="+880 1XXX XXXXXX" />
                        @error('form.phone') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="field-label">Alternate phone</label>
                        <input class="input mono" wire:model="form.alternate_phone" placeholder="optional" />
                    </div>
                    <div class="span-2">
                        <label class="field-label">Address</label>
                        <textarea class="textarea" wire:model="form.address" placeholder="House / Road / Area, City"></textarea>
                    </div>
                </div>
            </section>

            {{-- Compliance --}}
            <section class="section">
                <div class="section-title">
                    <h4><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z"/></svg></span>Compliance</h4>
                    <span class="hint">tax & licence</span>
                </div>
                <div class="grid-3">
                    <div>
                        <label class="field-label">Trade licence no.</label>
                        <input class="input mono" wire:model="form.trade_license_no" placeholder="TRAD/2024/…" />
                    </div>
                    <div>
                        <label class="field-label">TIN no.</label>
                        <input class="input mono" wire:model="form.tin_no" />
                    </div>
                    <div>
                        <label class="field-label">BIN no.</label>
                        <input class="input mono" wire:model="form.bin_no" />
                    </div>
                </div>
            </section>

            {{-- Media — attachment IDs --}}
            <section class="section">
                <div class="section-title">
                    <h4><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4.5-4.5L9 18"/></svg></span>Profile &amp; cover</h4>
                    <span class="hint">stored as attachment IDs</span>
                </div>
                <div class="media-row">
                    {{-- Wire these to your uploader; on success set form.image_id / form.cover_image_id --}}
                    <div class="uploader">
                        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></svg></div>
                        <div class="t">Profile image</div>
                        <div class="s">→ image_id · sq, max 2MB</div>
                    </div>
                    <div class="uploader cover">
                        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4.5-4.5L9 18"/></svg></div>
                        <div class="t">Cover image</div>
                        <div class="s">→ cover_image_id · wide</div>
                    </div>
                </div>
            </section>

            {{-- Documents — JSON array of FILE IDs only. Alpine handles the row UI;
                 push the IDs into form.documents (Livewire) before save.            --}}
            <section class="section" x-data="{
                    files: [],
                    attach() {
                        // TODO: replace with a real file picker → upload → returns id.
                        const id = 40000 + Math.floor(Math.random() * 9999);
                        this.files.push({ id, name: 'Document-' + id + '.pdf' });
                        this.sync();
                    },
                    remove(i) { this.files.splice(i, 1); this.sync(); },
                    sync() { $wire.set('form.documents', this.files.map(f => f.id)); }
                }">
                <div class="section-title">
                    <h4><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>Documents</h4>
                    <span class="hint">json: [ file ids ]</span>
                </div>
                <div class="doc-list">
                    <template x-for="(f, i) in files" :key="f.id">
                        <div class="doc-row">
                            <div class="fic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                            <div class="meta">
                                <div class="nm" x-text="f.name"></div>
                                <div class="id" x-text="'file_id: ' + f.id"></div>
                            </div>
                            <button class="rm" type="button" title="Remove" @click="remove(i)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                        </div>
                    </template>
                    <p x-show="files.length === 0" style="font:11px var(--mono); color:var(--ink-3); margin:0;">No documents attached yet.</p>
                </div>
                <button class="doc-add" type="button" @click="attach()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Attach document
                </button>
            </section>

            {{-- Notes --}}
            <section class="section">
                <div class="section-title"><h4>Notes</h4></div>
                <textarea class="textarea" wire:model="form.notes" placeholder="Internal notes about this supplier…" style="min-height:64px;"></textarea>
            </section>

        </div>

        <footer class="modal-foot">
            <span class="note">Fields marked * are required</span>
            <div class="right">
                <button class="btn" type="button" @click="modalOpen = false">Cancel</button>
                <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save supplier</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        </footer>
    </div>
</div>

</div>
