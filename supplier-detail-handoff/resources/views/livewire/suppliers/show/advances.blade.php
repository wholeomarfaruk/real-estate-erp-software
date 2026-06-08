{{-- Supplier ▸ Advance Payments (module 4). --}}
@php $full = fn ($n) => '৳ ' . number_format(abs($n)); @endphp

<div>
<x-supplier.shell :supplier="$supplier" active="advance">

    <div class="mini-stats">
        <div class="mini-stat"><div class="l">Advances paid</div><div class="v">7</div></div>
        <div class="mini-stat"><div class="l">Total advanced</div><div class="v">৳ 3.85M</div></div>
        <div class="mini-stat"><div class="l">Adjusted</div><div class="v green">৳ 3.85M</div></div>
        <div class="mini-stat"><div class="l">Open advance</div><div class="v">৳ 0</div></div>
    </div>

    <div class="card">
        <div class="tbl-toolbar">
            <div class="tbl-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input placeholder="Search advance ref, PO…" />
            </div>
            <button class="btn btn-sm">All ▾</button>
            <button class="btn btn-sm btn-primary">New advance</button>
        </div>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Advance ref</th><th>Date</th><th>Against PO</th>
                        <th class="num">Amount</th><th class="num">Adjusted</th><th class="num">Balance</th>
                        <th>Status</th><th class="num">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->advances as $r)
                        <tr wire:key="adv-{{ $r['ref'] }}">
                            <td><div class="t-ref">{{ $r['ref'] }}</div></td>
                            <td class="num" style="text-align:left;">{{ $r['date'] }}</td>
                            <td><span class="t-sub">{{ $r['po'] }}</span></td>
                            <td class="num t-strong">{{ $full($r['amt']) }}</td>
                            <td class="num amt-paid">{{ $r['adj'] ? $full($r['adj']) : '—' }}</td>
                            <td class="num {{ $r['bal']>0?'amt-due':'' }}">{{ $r['bal'] ? $full($r['bal']) : '৳ 0' }}</td>
                            <td><span class="pill {{ $r['status']==='adjusted'?'adjusted':'open-adv' }}"><span class="dot"></span>{{ $r['bal']>0?'Open':'Adjusted' }}</span></td>
                            <td class="act-cell">
                                <div style="display:inline-flex; gap:6px; justify-content:flex-end;">
                                    <button class="dl-btn" wire:click="downloadPdf('{{ $r['ref'] }}')" title="Download PDF — {{ $r['ref'] }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>PDF
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="tbl-foot">
            <span>Showing <b>7</b> of <b>7</b> advances</span>
            <span>Page 1 / 1</span>
        </div>
    </div>

</x-supplier.shell>
</div>
