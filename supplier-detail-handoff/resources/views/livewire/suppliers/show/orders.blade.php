{{-- Supplier ▸ Purchase Orders (module 3). --}}
@php $full = fn ($n) => '৳ ' . number_format(abs($n)); @endphp

<div>
<x-supplier.shell :supplier="$supplier" active="orders">

    <div class="mini-stats">
        <div class="mini-stat"><div class="l">Total POs</div><div class="v">63</div></div>
        <div class="mini-stat"><div class="l">Open</div><div class="v blue">3</div></div>
        <div class="mini-stat"><div class="l">Received</div><div class="v green">60</div></div>
        <div class="mini-stat"><div class="l">Order value</div><div class="v">৳ 19.1M</div></div>
    </div>

    <div class="card">
        <div class="tbl-toolbar">
            <div class="tbl-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input placeholder="Search PO no, item, amount…" />
            </div>
            <button class="btn btn-sm">All statuses ▾</button>
            <button class="btn btn-sm">Export</button>
        </div>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>PO no.</th><th>Date</th><th>Items</th>
                        <th class="num">Order value</th><th>Delivery</th><th>Status</th><th class="num">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->orders as $r)
                        <tr wire:key="po-{{ $r['no'] }}">
                            <td><div class="t-ref">{{ $r['no'] }}</div></td>
                            <td class="num" style="text-align:left;">{{ $r['date'] }}</td>
                            <td>{{ $r['items'] }}</td>
                            <td class="num t-strong">{{ $full($r['val']) }}</td>
                            <td class="num" style="text-align:left;">{{ $r['delivery'] }}</td>
                            <td><span class="pill {{ $r['status'] }}"><span class="dot"></span>{{ ucfirst($r['status']) }}</span></td>
                            <td class="act-cell">
                                <div style="display:inline-flex; gap:6px; justify-content:flex-end;">
                                    <button class="pay-btn view" wire:click="view('{{ $r['no'] }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>View
                                    </button>
                                    <button class="dl-btn" wire:click="downloadPdf('{{ $r['no'] }}')" title="Download PDF — {{ $r['no'] }}">
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
            <span>Showing <b>7</b> of <b>63</b> purchase orders</span>
            <span>Page 1 / 9</span>
        </div>
    </div>

</x-supplier.shell>
</div>
