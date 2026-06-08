{{-- Supplier ▸ Purchase Orders (module 3) --}}
@php $full = fn ($n) => '৳ ' . number_format(abs((float)$n)); @endphp

<div>
<x-supplier.shell :supplier="$supplier" active="orders">

    <div class="mini-stats">
        <div class="mini-stat"><div class="l">Total POs</div><div class="v">{{ $this->stats['total'] }}</div></div>
        <div class="mini-stat"><div class="l">Open</div><div class="v blue">{{ $this->stats['open_count'] }}</div></div>
        <div class="mini-stat"><div class="l">Received</div><div class="v green">{{ $this->stats['received_count'] }}</div></div>
        <div class="mini-stat"><div class="l">Order value</div><div class="v">{{ $full($this->stats['order_value']) }}</div></div>
    </div>

    <div class="card">
        <div class="tbl-toolbar">
            <div class="tbl-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input placeholder="Search PO no, remarks…" />
            </div>
            <button class="btn btn-sm">All statuses ▾</button>
            <button class="btn btn-sm">Export</button>
        </div>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>PO no.</th><th>Date</th><th>Store</th>
                        <th class="num">Order value</th><th class="num">Due</th><th>Status</th><th class="num">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->orders as $r)
                        @php
                            $statusValue = $r->status instanceof \App\Enums\Inventory\PurchaseOrderStatus
                                ? $r->status->value : (string) $r->status;
                            $statusLabel = $r->status instanceof \App\Enums\Inventory\PurchaseOrderStatus
                                ? $r->status->label() : ucfirst(str_replace('_', ' ', $statusValue));
                            $pillClass = match($statusValue) {
                                'received', 'completed'        => 'received',
                                'approved', 'partially_received' => 'ordered',
                                'draft', 'pending_engineer',
                                'pending_chairman', 'pending_accounts' => 'draft',
                                'rejected', 'cancelled'        => 'cancelled',
                                default                        => 'draft',
                            };
                        @endphp
                        <tr wire:key="po-{{ $r->id }}">
                            <td><div class="t-ref">{{ $r->po_no }}</div></td>
                            <td style="text-align:left;">{{ $r->order_date?->format('Y-m-d') }}</td>
                            <td><span class="t-sub">{{ $r->store?->name ?? '—' }}</span></td>
                            <td class="num t-strong">{{ $full($r->actual_purchase_amount) }}</td>
                            <td class="num {{ (float)$r->due_amount > 0 ? 'amt-due' : '' }}">
                                {{ (float)$r->due_amount > 0 ? $full($r->due_amount) : '—' }}
                            </td>
                            <td><span class="pill {{ $pillClass }}"><span class="dot"></span>{{ $statusLabel }}</span></td>
                            <td class="act-cell">
                                <div style="display:inline-flex; gap:6px; justify-content:flex-end;">
                                    <button class="pay-btn view" wire:click="view({{ $r->id }}, '{{ $r->po_no }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>View
                                    </button>
                                    <button class="dl-btn" wire:click="downloadPdf({{ $r->id }}, '{{ $r->po_no }}')" title="Download PDF">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>PDF
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center; padding:32px; color:var(--ink-3);">No purchase orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="tbl-foot">
            <span>Showing <b>{{ $this->orders->firstItem() ?? 0 }}–{{ $this->orders->lastItem() ?? 0 }}</b> of <b>{{ $this->orders->total() }}</b> purchase orders</span>
            <div>{{ $this->orders->links() }}</div>
        </div>
    </div>

</x-supplier.shell>
</div>
