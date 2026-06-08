{{-- Supplier ▸ Advance Payments (module 4) --}}
@php $full = fn ($n) => '৳ ' . number_format(abs((float)$n)); @endphp

<div>
<x-supplier.shell :supplier="$supplier" active="advance">

    <div class="mini-stats">
        <div class="mini-stat"><div class="l">Advances paid</div><div class="v">{{ $this->stats['total'] }}</div></div>
        <div class="mini-stat"><div class="l">Total advanced</div><div class="v">{{ $full($this->stats['total_amount']) }}</div></div>
        <div class="mini-stat"><div class="l">Status</div><div class="v green">All recorded</div></div>
        <div class="mini-stat"><div class="l">Open advance</div><div class="v">৳ 0</div></div>
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
                        <th class="num">Amount</th><th>Method</th><th>Released by</th>
                        <th>Status</th><th class="num">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->advances as $r)
                        @php
                            $pillClass = match($r->status) {
                                'completed' => 'adjusted',
                                'pending'   => 'ordered',
                                'rejected'  => 'cancelled',
                                default     => 'draft',
                            };
                            $statusLabel = match($r->status) {
                                'completed' => 'Completed',
                                'pending'   => 'Pending',
                                'rejected'  => 'Rejected',
                                default     => ucfirst($r->status ?? '—'),
                            };
                        @endphp
                        <tr wire:key="adv-{{ $r->id }}">
                            <td><div class="t-ref">ADV-{{ str_pad($r->id, 4, '0', STR_PAD_LEFT) }}</div></td>
                            <td style="text-align:left;">{{ $r->release_date?->format('Y-m-d') }}</td>
                            <td><span class="t-sub">{{ $r->purchaseOrder?->po_no ?? '—' }}</span></td>
                            <td class="num t-strong">{{ $full($r->amount) }}</td>
                            <td>{{ $r->method ? ucfirst(str_replace('_', ' ', $r->method)) : '—' }}</td>
                            <td>{{ $r->releaser?->name ?? '—' }}</td>
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
                        <tr><td colspan="8" style="text-align:center; padding:32px; color:var(--ink-3);">No advance payments found.</td></tr>
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
