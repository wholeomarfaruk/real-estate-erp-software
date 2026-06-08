{{-- Supplier ▸ Invoices (module 2) --}}
@php $full = fn ($n) => '৳ ' . number_format(abs((float)$n)); @endphp

<div x-data="{ open: false }"
     x-on:pay-modal-open.window="open = true"
     x-on:pay-modal-close.window="open = false"
     x-on:keydown.escape.window="open = false">
<x-supplier.shell :supplier="$supplier" active="invoices">

    <div class="mini-stats">
        <div class="mini-stat"><div class="l">Total invoices</div><div class="v">{{ $this->stats['total'] ?? 0 }}</div></div>
        <div class="mini-stat"><div class="l">Billed value</div><div class="v">৳ {{ number_format(($this->stats['billed'] ?? 0) / 1000000, 2) }}M</div></div>
        <div class="mini-stat"><div class="l">Outstanding</div><div class="v amber">{{ $full($this->stats['due'] ?? 0) }}</div></div>
        <div class="mini-stat"><div class="l">Paid</div><div class="v green">{{ $full($this->stats['paid'] ?? 0) }}</div></div>
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
                    @forelse ($this->invoices as $r)
                        @php
                            $statusValue = $r->status instanceof \App\Enums\Inventory\PurchaseInvoiceStatus
                                ? $r->status->value : (string) $r->status;
                            $statusLabel = $r->status instanceof \App\Enums\Inventory\PurchaseInvoiceStatus
                                ? $r->status->label() : ucfirst(str_replace('_', ' ', $statusValue));
                            $isDue = (float) $r->due_amount > 0;
                            $pillClass = match($statusValue) {
                                'paid'           => 'paid',
                                'partially_paid' => 'partial',
                                'approved'       => 'unpaid',
                                'pending'        => 'draft',
                                'cancelled'      => 'cancelled',
                                default          => 'draft',
                            };
                        @endphp
                        <tr wire:key="inv-{{ $r->id }}">
                            <td><div class="t-ref">{{ $r->invoice_no }}</div></td>
                            <td style="text-align:left;">{{ $r->invoice_date?->format('Y-m-d') }}</td>
                            <td><span class="t-sub">{{ $r->purchaseOrder?->po_no ?? '—' }}</span></td>
                            <td class="num t-strong">{{ $full($r->total_amount) }}</td>
                            <td class="num amt-paid">{{ (float)$r->paid_amount > 0 ? $full($r->paid_amount) : '—' }}</td>
                            <td class="num {{ $isDue ? ($statusValue === 'partially_paid' ? 'amt-ovd' : 'amt-due') : '' }}">
                                {{ $isDue ? $full($r->due_amount) : '—' }}
                            </td>
                            <td><span class="pill {{ $pillClass }}"><span class="dot"></span>{{ $statusLabel }}</span></td>
                            <td class="act-cell">
                                <div style="display:inline-flex; gap:6px; justify-content:flex-end;">
                                    @if ($isDue)
                                        <button class="pay-btn pay" wire:click="openPay({{ $r->id }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Pay now
                                        </button>
                                    @else
                                        <button class="pay-btn view" wire:click="openPay({{ $r->id }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>Details
                                        </button>
                                    @endif
                                    <a class="dl-btn" href="{{ route('admin.inventory.purchase-invoices.pdf', $r->id) }}" target="_blank" title="Download PDF">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center; padding:32px; color:var(--ink-3);">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="tbl-foot">
            <span>Showing <b>{{ $this->invoices->firstItem() ?? 0 }}–{{ $this->invoices->lastItem() ?? 0 }}</b> of <b>{{ $this->invoices->total() }}</b> invoices</span>
            <div>{{ $this->invoices->links() }}</div>
        </div>
    </div>

</x-supplier.shell>

{{-- PAYMENT MODAL --}}
<div class="scrim" :class="{ 'open': open }" x-cloak x-on:click.self="open = false">
    @php $inv = $this->activeInvoice; @endphp
    <div class="modal" role="dialog" aria-modal="true">
        @if ($inv)
            @php
                $statusValue = $inv->status instanceof \App\Enums\Inventory\PurchaseInvoiceStatus
                    ? $inv->status->value : (string) $inv->status;
                $isDue = (float) $inv->due_amount > 0;
                $payReqs = $inv->bankingPaymentRequests ?? collect();
                $pendingCount = $payReqs->whereIn('status', ['pending','approved','released'])->count();
            @endphp
            <div class="modal-head">
                <div>
                    <div class="mh-id">{{ $inv->invoice_no }} · {{ $inv->invoice_date?->format('Y-m-d') }}</div>
                    <h3>{{ $isDue ? 'Record payment' : 'Payment details' }}</h3>
                    <div class="mh-sub">{{ $supplier->name }}@if($inv->purchaseOrder) · against {{ $inv->purchaseOrder->po_no }}@endif</div>
                </div>
                <button class="close" wire:click="closePay" aria-label="Close">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="modal-body">
                <div class="pay-summary">
                    <div class="cell"><div class="l">Invoice amount</div><div class="v">{{ $full($inv->total_amount) }}</div></div>
                    <div class="cell"><div class="l">Paid to date</div><div class="v green">{{ $full($inv->paid_amount) }}</div></div>
                    <div class="cell"><div class="l">Balance due</div><div class="v amber">{{ $isDue ? $full($inv->due_amount) : '৳ 0' }}</div></div>
                </div>

                @if ($isDue)
                    <section class="section">
                        <h4>
                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                            New payment request
                            @if ($pendingCount > 0)
                                <span style="margin-left:8px; font:400 11px 'Inter'; color:var(--amber); background:var(--amber-bg,#fffbeb); border:1px solid var(--amber-border,#fcd34d); border-radius:20px; padding:2px 8px;">
                                    {{ $pendingCount }} pending
                                </span>
                            @endif
                        </h4>
                        <div class="grid-2m">
                            <div>
                                <label class="field-label">Amount <span style="color:var(--rj-fg)">*</span></label>
                                <input class="input mono" wire:model="payAmount" placeholder="0" />
                                @error('payAmount') <div style="margin-top:5px;font:500 11px 'Inter';color:var(--rj-fg);">{{ $message }}</div> @enderror
                                <div class="quick-amts">
                                    <button type="button" wire:click="fillFull">Full due · {{ $full($inv->due_amount) }}</button>
                                    <button type="button" wire:click="fillHalf">Half · {{ $full(round((float)$inv->due_amount / 2, 2)) }}</button>
                                </div>
                            </div>
                            <div>
                                <label class="field-label">Payment date <span style="color:var(--rj-fg)">*</span></label>
                                <input class="input mono" type="date" wire:model="payDate" />
                                @error('payDate') <div style="margin-top:5px;font:500 11px 'Inter';color:var(--rj-fg);">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label">Bank account <span style="color:var(--rj-fg)">*</span></label>
                                <select class="select" wire:model="payBankId">
                                    <option value="">— Select bank account —</option>
                                    @foreach ($this->bankAccounts as $ba)
                                        <option value="{{ $ba->id }}">{{ $ba->bank_name }} ({{ strtoupper($ba->type) }}) · {{ $ba->ac_number }}</option>
                                    @endforeach
                                </select>
                                @error('payBankId') <div style="margin-top:5px;font:500 11px 'Inter';color:var(--rj-fg);">{{ $message }}</div> @enderror
                            </div>
                            <div class="span-2m">
                                <label class="field-label">Expense category</label>
                                <select class="select" wire:model="payCategoryId">
                                    <option value="">— Supplier Bill (select sub-category) —</option>
                                    @foreach ($this->expenseCategories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="field-label">Method <span style="color:var(--rj-fg)">*</span></label>
                                <select class="select" wire:model="payMethod">
                                    @foreach ($this->entryMethods as $em)
                                        <option value="{{ $em->value }}">{{ $em->label() }}</option>
                                    @endforeach
                                </select>
                                @error('payMethod') <div style="margin-top:5px;font:500 11px 'Inter';color:var(--rj-fg);">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label">Reference / TXN no.</label>
                                <input class="input mono" wire:model="payReference" placeholder="Cheque no., TXN ID, etc." />
                                @error('payReference') <div style="margin-top:5px;font:500 11px 'Inter';color:var(--rj-fg);">{{ $message }}</div> @enderror
                            </div>
                            <div class="span-2m">
                                <label class="field-label">Notes</label>
                                <input class="input" wire:model="payNotes" placeholder="Optional note for this payment request" />
                                @error('payNotes') <div style="margin-top:5px;font:500 11px 'Inter';color:var(--rj-fg);">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </section>
                @endif

                <section class="section">
                    <h4>
                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                        Payment requests
                    </h4>
                    <div class="pmt-list">
                        @forelse ($payReqs as $pr)
                            @php
                                $prPill = match($pr->status) {
                                    'completed' => ['paid',      'Completed'],
                                    'approved'  => ['partial',   'Approved'],
                                    'released'  => ['unpaid',    'Released'],
                                    'pending'   => ['draft',     'Pending'],
                                    'rejected'  => ['cancelled', 'Rejected'],
                                    default     => ['draft', ucfirst($pr->status ?? '?')],
                                };
                                $prIcon = match($pr->status) {
                                    'completed' => '<polyline points="20 6 9 17 4 12"/>',
                                    'rejected'  => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
                                    default     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
                                };
                            @endphp
                            <div class="pmt-item" wire:key="pr-{{ $pr->id }}">
                                <div class="pmt-ic @if($pr->status === 'rejected') style="background:var(--rj-bg,#fef2f2);" @endif">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="@if($pr->status === 'rejected') var(--rj-fg,#ef4444) @elseif($pr->status === 'completed') var(--grn) @else var(--amber) @endif" stroke-width="2">{!! $prIcon !!}</svg>
                                </div>
                                <div class="pmt-main">
                                    <div class="t">
                                        {{ $full($pr->amount) }}
                                        @if($pr->bankAccount) · {{ $pr->bankAccount->bank_name }} @endif
                                        <span class="pill {{ $prPill[0] }}" style="font-size:10px; padding:2px 8px; vertical-align:middle; margin-left:6px;">
                                            <span class="dot"></span>{{ $prPill[1] }}
                                        </span>
                                    </div>
                                    <div class="s">
                                        Req by {{ $pr->requestedBy?->name ?? '—' }}
                                        · {{ $pr->created_at?->format('Y-m-d') }}
                                        @if ($pr->payment_date)
                                            · Pay date: {{ $pr->payment_date->format('Y-m-d') }}
                                        @endif
                                        @if ($pr->transactionCategory)
                                            · {{ $pr->transactionCategory->name }}
                                        @endif
                                        @if ($pr->external_data['method'] ?? null)
                                            · {{ \App\Enums\Accounts\EntryMethod::tryFrom($pr->external_data['method'])?->label() ?? $pr->external_data['method'] }}
                                        @endif
                                        @if ($pr->external_data['reference'] ?? null)
                                            · Ref: {{ $pr->external_data['reference'] }}
                                        @endif
                                        @if ($pr->status === 'completed' && $pr->completedBy)
                                            · Completed by {{ $pr->completedBy->name }} on {{ $pr->completed_at?->format('Y-m-d') }}
                                        @endif
                                        @if ($pr->status === 'rejected' && $pr->rejection_reason)
                                            · Reason: {{ $pr->rejection_reason }}
                                        @endif
                                        @if ($pr->notes)
                                            · {{ $pr->notes }}
                                        @endif
                                    </div>
                                </div>
                                <div class="pmt-amt">
                                    <div class="a">{{ $full($pr->amount) }}</div>
                                    @if($pr->request_no)<div class="ref">{{ $pr->request_no }}</div>@endif
                                </div>
                            </div>
                        @empty
                            <div class="pmt-empty">No payment requests yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <footer class="modal-foot">
                <span class="note">
                    @if ($isDue)
                        Payment request goes to Banking for approval → release → completion
                    @else
                        This invoice is fully settled
                    @endif
                </span>
                <div class="right">
                    <button class="btn" wire:click="closePay">{{ $isDue ? 'Cancel' : 'Close' }}</button>
                    @if ($isDue)
                        <button class="btn btn-primary" wire:click="recordPayment" wire:loading.attr="disabled" wire:target="recordPayment">
                            <span wire:loading.remove wire:target="recordPayment">Send payment request</span>
                            <span wire:loading wire:target="recordPayment">Sending…</span>
                        </button>
                    @endif
                </div>
            </footer>
        @endif
    </div>
</div>

</div>
