{{-- Supplier ▸ Advance Payments (module 4) --}}
@php $full = fn ($n) => '৳ ' . number_format(abs((float)$n)); @endphp

<div>
<x-supplier.shell :supplier="$supplier" active="advance">

    <div class="mini-stats">
        <div class="mini-stat"><div class="l">Advances paid</div><div class="v">{{ $this->stats['total'] }}</div></div>
        <div class="mini-stat"><div class="l">Total advanced</div><div class="v">{{ $full($this->stats['total_amount']) }}</div></div>
        <div class="mini-stat"><div class="l">Adjusted</div><div class="v">{{ $full(($this->stats['total_amount'] ?? 0) - ($this->stats['available_amount'] ?? 0)) }}</div></div>
        <div class="mini-stat"><div class="l">Open advance</div><div class="v {{ ($this->stats['available_amount'] ?? 0) > 0 ? 'green' : '' }}">{{ $full($this->stats['available_amount'] ?? 0) }}</div></div>
    </div>

    <div class="card">
        <div class="tbl-toolbar">
            <div class="tbl-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input placeholder="Search advance ref, PO…" />
            </div>
            <button class="btn btn-sm">All ▾</button>
        </div>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Advance ref</th><th>Date</th><th>Against PO</th>
                        <th class="num">Amount</th><th class="num">Adjusted</th><th class="num">Available</th>
                        <th>Method</th><th>Released by</th>
                        <th>Status</th><th class="num">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->advances as $r)
                        @php
                            // Each row is a posted advance transaction (ledger source of truth).
                            $amount    = (float) $r->lines->sum('debit');   // advance (Dr) movement
                            $remaining = round($r->remainingAdvance(), 2);
                            $adjusted  = round($amount - $remaining, 2);
                            $isUsed    = $remaining <= 0;
                            $isPartial = $adjusted > 0 && $remaining > 0;

                            [$pillClass, $statusLabel] = match (true) {
                                $isUsed    => ['adjusted', 'Fully used'],
                                $isPartial => ['partial',  'Partly used'],
                                default    => ['paid',     'Available'],
                            };

                            $poNo = $this->poNumbers[$r->id] ?? null;
                        @endphp
                        <tr wire:key="adv-{{ $r->id }}">
                            <td><div class="t-ref">{{ $r->reference_no ?: ('ADV-' . str_pad($r->id, 4, '0', STR_PAD_LEFT)) }}</div></td>
                            <td style="text-align:left;">{{ $r->datetime?->format('Y-m-d') }}</td>
                            <td><span class="t-sub">{{ $poNo ?? '—' }}</span></td>
                            <td class="num t-strong">{{ $full($amount) }}</td>
                            <td class="num">{{ $adjusted > 0 ? $full($adjusted) : '—' }}</td>
                            <td class="num {{ $remaining > 0 ? 'amt-paid' : '' }}">
                                {{ $full($remaining) }}
                            </td>
                            <td>{{ $r->method ? ucfirst(str_replace('_', ' ', $r->method)) : '—' }}</td>
                            <td>{{ $r->creator?->name ?? '—' }}</td>
                            <td><span class="pill {{ $pillClass }}"><span class="dot"></span>{{ $statusLabel }}</span></td>
                            <td class="act-cell">
                                <div style="display:inline-flex; gap:6px; justify-content:flex-end;">
                                    <button class="dl-btn" wire:click="downloadPdf({{ $r->id }})" title="Download PDF">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>PDF
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" style="text-align:center; padding:32px; color:var(--ink-3);">No advance payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="tbl-foot">
            <span>Showing <b>{{ $this->advances->firstItem() ?? 0 }}–{{ $this->advances->lastItem() ?? 0 }}</b> of <b>{{ $this->advances->total() }}</b> advances</span>
            <div>{{ $this->advances->links() }}</div>
        </div>
    </div>

</x-supplier.shell>
</div>
