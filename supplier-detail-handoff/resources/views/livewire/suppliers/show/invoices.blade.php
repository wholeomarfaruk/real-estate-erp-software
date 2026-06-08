{{-- Supplier ▸ Invoices (module 2). Table + payment modal. --}}
@php
    $full = fn ($n) => '৳ ' . number_format(abs($n));
@endphp

<div x-data="{ open: false }"
     x-on:pay-modal-open.window="open = true"
     x-on:pay-modal-close.window="open = false"
     x-on:keydown.escape.window="open = false">
<x-supplier.shell :supplier="$supplier" active="invoices">

    <div class="mini-stats">
        <div class="mini-stat"><div class="l">Total invoices</div><div class="v">118</div></div>
        <div class="mini-stat"><div class="l">Billed value</div><div class="v">৳ 18.4M</div></div>
        <div class="mini-stat"><div class="l">Outstanding</div><div class="v amber">৳ 842K</div></div>
        <div class="mini-stat"><div class="l">Overdue</div><div class="v red">৳ 96K</div></div>
    </div>

    <div class="card">
        <div class="tbl-toolbar">
            <div class="tbl-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input placeholder="Search invoice no, PO, amount…" />
            </div>
            <button class="btn btn-sm">All statuses ▾</button>
            <button class="btn btn-sm">This year ▾</button>
            <button class="btn btn-sm">Export</button>
        </div>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Invoice</th><th>Date</th><th>PO ref</th>
                        <th class="num">Amount</th><th class="num">Paid</th><th class="num">Due</th>
                        <th>Status</th><th class="num">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->invoices as $r)
                        <tr wire:key="inv-{{ $r['id'] }}">
                            <td><div class="t-ref">{{ $r['no'] }}</div></td>
                            <td class="num" style="text-align:left;">{{ $r['date'] }}</td>
                            <td><span class="t-sub">{{ $r['po'] }}</span></td>
                            <td class="num t-strong">{{ $full($r['amt']) }}</td>
                            <td class="num amt-paid">{{ $r['paid'] ? $full($r['paid']) : '—' }}</td>
                            <td class="num {{ $r['status']==='partial'?'amt-ovd':'amt-due' }}">{{ $r['due'] ? $full($r['due']) : '—' }}</td>
                            <td><span class="pill {{ $r['status'] }}"><span class="dot"></span>{{ $r['status']==='partial'?'Partial':ucfirst($r['status']) }}</span></td>
                            <td class="act-cell">
                                <div style="display:inline-flex; gap:6px; justify-content:flex-end;">
                                    @if ($r['due'] > 0)
                                        <button class="pay-btn pay" wire:click="openPay({{ $r['id'] }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Pay now
                                        </button>
                                    @else
                                        <button class="pay-btn view" wire:click="openPay({{ $r['id'] }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>Details
                                        </button>
                                    @endif
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
            <span>Showing <b>8</b> of <b>118</b> invoices</span>
            <span>Page 1 / 15</span>
        </div>
    </div>

</x-supplier.shell>

{{-- ─── PAYMENT MODAL (Alpine shows/hides, Livewire supplies data + records) ─── --}}
<div class="scrim" :class="{ 'open': open }" x-cloak x-on:click.self="open = false">
    @php $inv = $this->activeInvoice; @endphp
    <div class="modal" role="dialog" aria-modal="true">
        @if ($inv)
            <div class="modal-head">
                <div>
                    <div class="mh-id">{{ $inv['no'] }} · {{ $inv['date'] }}</div>
                    <h3>{{ $inv['due'] > 0 ? 'Record payment' : 'Payment details' }}</h3>
                    <div class="mh-sub">{{ $supplier->name }} · against {{ $inv['po'] }}</div>
                </div>
                <button class="close" wire:click="closePay" aria-label="Close">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="pay-summary">
                    <div class="cell"><div class="l">Invoice amount</div><div class="v">{{ $full($inv['amt']) }}</div></div>
                    <div class="cell"><div class="l">Paid to date</div><div class="v green">{{ $full($inv['paid']) }}</div></div>
                    <div class="cell"><div class="l">Balance due</div><div class="v amber">{{ $inv['due'] ? $full($inv['due']) : '৳ 0' }}</div></div>
                </div>

                @if ($inv['due'] > 0)
                    <section class="section">
                        <h4><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>New payment</h4>
                        <div class="grid-2m">
                            <div>
                                <label class="field-label">Amount <span style="color:var(--rj-fg)">*</span></label>
                                <input class="input mono" wire:model="payAmount" placeholder="0" />
                                @error('payAmount') <div style="margin-top:5px;font:500 11px 'Inter';color:var(--rj-fg);">{{ $message }}</div> @enderror
                                <div class="quick-amts">
                                    <button type="button" wire:click="$set('payAmount', '{{ $inv['due'] }}')">Full due · {{ $full($inv['due']) }}</button>
                                    <button type="button" wire:click="$set('payAmount', '{{ intval($inv['due']/2) }}')">Half · {{ $full(intval($inv['due']/2)) }}</button>
                                </div>
                            </div>
                            <div>
                                <label class="field-label">Payment date <span style="color:var(--rj-fg)">*</span></label>
                                <input class="input mono" type="date" wire:model="payDate" />
                            </div>
                            <div>
                                <label class="field-label">Method</label>
                                <select class="select" wire:model="payMethod">
                                    <option>Bank transfer</option><option>Cheque</option><option>Cash</option>
                                    <option>Mobile banking</option><option>Adjust from advance</option>
                                </select>
                            </div>
                            <div>
                                <label class="field-label">Reference / TXN</label>
                                <input class="input mono" wire:model="payRef" placeholder="TXN / cheque no." />
                            </div>
                            <div class="span-2m">
                                <label class="field-label">Note</label>
                                <input class="input" wire:model="payNote" placeholder="Optional note for this payment" />
                            </div>
                        </div>
                    </section>
                @endif

                <section class="section">
                    <h4>
                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                        Payment history
                        <span class="badge">{{ count($inv['payments']) }} {{ \Illuminate\Support\Str::plural('payment', count($inv['payments'])) }}</span>
                    </h4>
                    <div class="pmt-list">
                        @forelse ($inv['payments'] as $p)
                            <div class="pmt-item">
                                <div class="pmt-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
                                <div class="pmt-main">
                                    <div class="t">{{ $full($p['amt']) }} · {{ $p['method'] }}</div>
                                    <div class="s">{{ $p['date'] }} · {{ $p['detail'] }} · by {{ $p['by'] }}</div>
                                </div>
                                <div class="pmt-amt"><div class="a">{{ $full($p['amt']) }}</div><div class="ref">{{ $p['ref'] }}</div></div>
                            </div>
                        @empty
                            <div class="pmt-empty">No payments recorded yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <footer class="modal-foot">
                <span class="note">{{ $inv['due'] > 0 ? 'Posts against the supplier ledger' : 'This invoice is fully settled' }}</span>
                <div class="right">
                    <button class="btn" wire:click="closePay">{{ $inv['due'] > 0 ? 'Cancel' : 'Close' }}</button>
                    @if ($inv['due'] > 0)
                        <button class="btn btn-primary" wire:click="recordPayment" wire:loading.attr="disabled" wire:target="recordPayment">
                            <span wire:loading.remove wire:target="recordPayment">Record payment</span>
                            <span wire:loading wire:target="recordPayment">Saving…</span>
                        </button>
                    @endif
                </div>
            </footer>
        @endif
    </div>
</div>

</div>
