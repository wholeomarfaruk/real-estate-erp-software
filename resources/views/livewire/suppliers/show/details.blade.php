{{-- Supplier ▸ Details (module 1) --}}
@php
    $bdt = fn ($n) => '৳ ' . number_format(abs($n));
    $totalPurchased = $totalPurchased ?? 0;
    $totalPaid      = $totalPaid ?? 0;
    $totalDue       = $totalDue ?? 0;
    $paidPct        = $totalPurchased > 0 ? round(($totalPaid / $totalPurchased) * 100) : 0;
    $duePct         = $totalPurchased > 0 ? round(($totalDue / $totalPurchased) * 100) : 0;
    $ovdPct         = max(0, 100 - $paidPct - $duePct);
@endphp

<div>
<x-supplier.shell :supplier="$supplier" active="details">

    {{-- Row 1: account summary + relations --}}
    <div class="grid-wrap">
        <div class="card">
            <div class="card-head">
                <h3><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>Payable account</h3>
                <span class="hint">net position</span>
            </div>
            <div class="card-body">
                <div class="acct-bar">
                    <span class="paid" style="width:{{ $paidPct }}%;">Paid {{ $paidPct }}%</span>
                    <span class="due"  style="width:{{ $duePct }}%;">Due {{ $duePct }}%</span>
                    @if ($ovdPct > 0)
                    <span class="ovd"  style="width:{{ $ovdPct }}%;">{{ $ovdPct }}%</span>
                    @endif
                </div>
                <div class="acct-legend">
                    <span><i style="background:var(--av-fg)"></i>Paid {{ $bdt($totalPaid) }}</span>
                    <span><i style="background:var(--bk-fg)"></i>Due {{ $bdt($totalDue) }}</span>
                </div>
                <div class="summary-grid">
                    <div class="summary-cell">
                        <div class="summary-lbl">Billed (lifetime)</div>
                        <div class="summary-val">{{ $bdt($totalPurchased) }}</div>
                        <div class="summary-sub">{{ $supplier->purchase_invoices_count }} invoices</div>
                    </div>
                    <div class="summary-cell">
                        <div class="summary-lbl">Outstanding</div>
                        <div class="summary-val amber">{{ $bdt($totalDue) }}</div>
                        <div class="summary-sub">unpaid balance</div>
                    </div>
                    <div class="summary-cell">
                        <div class="summary-lbl">Net balance</div>
                        <div class="summary-val {{ $totalDue > 0 ? 'red' : 'green' }}">
                            {{ $totalDue > 0 ? '−' : '' }}{{ $bdt($totalDue) }}
                        </div>
                        <div class="summary-sub">{{ $totalDue > 0 ? 'we owe supplier' : 'settled' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h3><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg></span>Linked records</h3>
            </div>
            <div class="card-body">
                <div class="rel-grid">
                    <a class="rel" href="{{ route('admin.supplier.suppliers.show.invoices', $supplier) }}" wire:navigate>
                        <div class="ic inv"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                        <div class="m"><div class="v">{{ $supplier->purchase_invoices_count }}</div><div class="l">Invoices</div></div>
                        <span class="arr"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                    </a>
                    <a class="rel" href="{{ route('admin.supplier.suppliers.show.orders', $supplier) }}" wire:navigate>
                        <div class="ic po"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
                        <div class="m"><div class="v">{{ $supplier->purchase_orders_count }}</div><div class="l">Purchase orders</div></div>
                        <span class="arr"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                    </a>
                    <a class="rel" href="{{ route('admin.supplier.suppliers.show.advances', $supplier) }}" wire:navigate>
                        <div class="ic adv"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
                        <div class="m"><div class="v">{{ $supplier->purchase_funds_count }}</div><div class="l">Advances</div></div>
                        <span class="arr"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                    </a>
                    <div class="rel">
                        <div class="ic ret"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg></div>
                        <div class="m"><div class="v">{{ $supplier->purchase_returns_count }}</div><div class="l">Returns</div></div>
                        <span class="arr"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                    </div>
                    <div class="rel">
                        <div class="ic rcv"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
                        <div class="m"><div class="v">{{ $supplier->stock_receives_count }}</div><div class="l">Stock receives</div></div>
                        <span class="arr"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                    </div>
                    <div class="rel">
                        <div class="ic led"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></div>
                        <div class="m"><div class="v">{{ $ledgerCount }}</div><div class="l">Ledger entries</div></div>
                        <span class="arr"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 2: purchase trend graph --}}
    @if (count($trend) > 0)
    <div class="card" style="margin-bottom:16px;"
         x-data="supplierTrend(@js($trend))" x-init="draw()">
        <div class="card-head">
            <h3><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>Purchase &amp; payment trend</h3>
            <div class="right"><span class="hint">last 12 months</span></div>
        </div>
        <div class="card-body">
            <div class="chart-legend">
                <span><i style="background:var(--sd-fg)"></i>Purchased</span>
                <span><i style="background:var(--av-fg)"></i>Paid</span>
                <span><i style="background:var(--bk-fg); border-radius:50%;"></i>Outstanding due</span>
            </div>
            <div class="chart-wrap" x-ref="chart"></div>
        </div>
    </div>
    @endif

    {{-- Row 3: contact + docs + notes --}}
    <div class="grid-wrap">
        <div class="card">
            <div class="card-head">
                <h3><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>Contact &amp; compliance</h3>
                <a href="{{ route('admin.supplier.suppliers.edit', $supplier) }}" wire:navigate class="btn btn-sm">Edit</a>
            </div>
            <div class="card-body">
                <div class="fact-grid">
                    @if ($supplier->contact_person)
                    <div class="fact"><div class="fact-lbl">Contact person</div><div class="fact-val">{{ $supplier->contact_person }}</div></div>
                    @endif
                    @if ($supplier->phone)
                    <div class="fact"><div class="fact-lbl">Phone</div><div class="fact-val mono"><a href="tel:{{ $supplier->phone }}">{{ $supplier->phone }}</a></div></div>
                    @endif
                    @if ($supplier->alternate_phone)
                    <div class="fact"><div class="fact-lbl">Alt. phone</div><div class="fact-val mono">{{ $supplier->alternate_phone }}</div></div>
                    @endif
                    @if ($supplier->email)
                    <div class="fact"><div class="fact-lbl">Email</div><div class="fact-val"><a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a></div></div>
                    @endif
                    @if ($supplier->address)
                    <div class="fact" style="grid-column:span 2;"><div class="fact-lbl">Address</div><div class="fact-val">{{ $supplier->address }}</div></div>
                    @endif
                    @if ($supplier->trade_license_no)
                    <div class="fact"><div class="fact-lbl">Trade licence</div><div class="fact-val mono">{{ $supplier->trade_license_no }}</div></div>
                    @endif
                    @if ($supplier->tin_no)
                    <div class="fact"><div class="fact-lbl">TIN no.</div><div class="fact-val mono">{{ $supplier->tin_no }}</div></div>
                    @endif
                    @if ($supplier->bin_no)
                    <div class="fact"><div class="fact-lbl">BIN no.</div><div class="fact-val mono">{{ $supplier->bin_no }}</div></div>
                    @endif
                </div>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:16px;">
            @if (count($documents) > 0)
            <div class="card">
                <div class="card-head">
                    <h3><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>Documents</h3>
                    <span class="hint">{{ count($documents) }} files</span>
                </div>
                <div class="card-body">
                    <div class="docs">
                        @foreach ($documents as $fileId)
                            <div class="doc-row">
                                <div class="doc-thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                                <div class="doc-info">
                                    <div class="doc-title">Document</div>
                                    <div class="doc-no">file_id: {{ $fileId }}</div>
                                </div>
                                <div class="doc-actions"><button class="btn btn-sm">View</button></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if ($supplier->notes)
            <div class="card">
                <div class="card-head"><h3>Notes</h3></div>
                <div class="card-body">
                    <div class="notes">
                        {{ $supplier->notes }}
                        @if ($supplier->updater)
                            <span class="meta">Updated by {{ $supplier->updater->name }} · {{ $supplier->updated_at?->format('Y-m-d') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

</x-supplier.shell>

@script
<script>
Alpine.data('supplierTrend', (data) => ({
    data,
    draw() {
        const d = this.data, W = 1000, H = 280, padL = 44, padR = 16, padB = 34, padT = 12;
        const plotW = W - padL - padR, plotH = H - padT - padB;
        const maxV = Math.max(...d.map(r => r.purchased), 0.1);
        const maxVRounded = Math.ceil(maxV / 0.5) * 0.5;
        const x = i => padL + (i + 0.5) * (plotW / d.length);
        const bw = (plotW / d.length) * 0.34;
        const y = v => padT + plotH - (v / maxVRounded) * plotH;
        let g = '';
        for (let t = 0; t <= maxVRounded; t += maxVRounded / 5){
            const yy = y(t);
            g += `<line class="grid-line" x1="${padL}" y1="${yy}" x2="${W-padR}" y2="${yy}"/>`;
            g += `<text class="axis-text" x="${padL-8}" y="${yy+3}" text-anchor="end">৳${t.toFixed(1)}M</text>`;
        }
        d.forEach((row, i) => {
            const cx = x(i), pxP = cx - bw - 1, pxPaid = cx + 1;
            g += `<rect class="bar-purchase" x="${pxP}" y="${y(row.purchased)}" width="${bw}" height="${plotH + padT - y(row.purchased)}" rx="2"><title>${row.m} · purchased ৳${row.purchased}M</title></rect>`;
            g += `<rect class="bar-paid" x="${pxPaid}" y="${y(row.paid)}" width="${bw}" height="${plotH + padT - y(row.paid)}" rx="2"><title>${row.m} · paid ৳${row.paid}M</title></rect>`;
            g += `<text class="axis-text" x="${cx}" y="${H-padB+18}" text-anchor="middle">${row.m}</text>`;
        });
        const duePts = d.map((row,i) => [x(i), y(row.due)]);
        g += `<path class="due-line" d="${duePts.map((p,i)=>(i===0?'M':'L')+p[0]+' '+p[1]).join(' ')}"/>`;
        duePts.forEach(p => { g += `<circle class="due-dot" cx="${p[0]}" cy="${p[1]}" r="3"/>`; });
        this.$refs.chart.innerHTML = `<svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Purchase and payment trend">${g}</svg>`;
    }
}));
</script>
@endscript
</div>
