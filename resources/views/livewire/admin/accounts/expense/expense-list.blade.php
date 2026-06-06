{{-- Expense List — reads the transactions ledger (type = expense) --}}
<div class="prj-page" x-data x-init="$store.pageName = { name: 'Expenses', slug: 'accounts' }">
<style>
:root{
  --ink:#14181f; --ink-2:#2a2f3a; --muted:#6b7280; --muted-2:#9aa0a6;
  --rule:#e4e4e7; --rule-soft:#ececec; --paper:#fff; --canvas:#f6f6f7;
  --accent:#0d2a4a; --accent-soft:#eaf0f8; --ok:#1f6f43;
}
.exp-wrap{ font-family:"Inter",system-ui,sans-serif;color:var(--ink); }
.exp-head{ display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px; }
.exp-head h2{ font-family:"Instrument Serif",Georgia,serif;font-weight:400;font-size:26px;margin:0; }
.exp-head .sub{ font-size:12px;color:var(--muted);margin-top:2px; }
.btn{ font-family:inherit;font-size:12.5px;font-weight:500;padding:9px 15px;border-radius:7px;border:1px solid var(--rule);background:var(--paper);color:var(--ink-2);cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none; }
.btn.primary{ background:var(--accent);color:#fff;border-color:var(--accent); }
.btn .ic{ width:14px;height:14px; }

.kpis{ display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:16px;max-width:520px; }
.kpi{ background:var(--paper);border:1px solid var(--rule);border-radius:10px;padding:14px 16px;border-left:4px solid var(--accent); }
.kpi .l{ font-size:10.5px;letter-spacing:.6px;text-transform:uppercase;color:var(--muted);font-weight:600; }
.kpi .v{ font-family:"Instrument Serif",Georgia,serif;font-size:24px;margin-top:6px; }
.kpi .v .cur{ font-family:"Inter",sans-serif;font-size:11px;color:var(--muted);margin-right:3px; }

.filters{ background:var(--paper);border:1px solid var(--rule);border-radius:10px 10px 0 0;border-bottom:none;padding:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center; }
.inp{ font-family:inherit;font-size:12.5px;padding:8px 11px;border:1px solid var(--rule);border-radius:7px;background:var(--paper);color:var(--ink); }
.inp:focus{ outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft); }
.grow{ flex:1; }

.table-wrap{ background:var(--paper);border:1px solid var(--rule);border-radius:0 0 10px 10px;overflow:hidden; }
table.t{ width:100%;border-collapse:collapse; }
table.t thead th{ text-align:left;font-size:9.5px;letter-spacing:.6px;text-transform:uppercase;color:var(--muted);font-weight:600;padding:10px 14px;background:#fafafb;border-bottom:1px solid var(--rule); }
table.t th.r,table.t td.r{ text-align:right; }
table.t tbody td{ padding:11px 14px;font-size:12.5px;border-bottom:1px solid var(--rule-soft); }
table.t tbody tr:hover{ background:#fafafb; }
.mono{ font-family:"JetBrains Mono",ui-monospace,monospace;font-size:12px; }
.ref-chip{ display:inline-block;font-size:10px;padding:2px 8px;border-radius:999px;background:var(--accent-soft);color:var(--accent);border:1px solid #c2dcf3; }
.foot{ padding:12px 14px;font-size:11.5px;color:var(--muted);background:#fafafb;border-top:1px solid var(--rule); }
</style>

<div class="exp-wrap">
  <div class="exp-head">
    <div>
      <h2>Expenses</h2>
      <div class="sub">Posted expense transactions from the ledger. New expenses are routed through banking approval before appearing here.</div>
    </div>
    <a href="{{ route('admin.accounts.expenses.create') }}" class="btn primary">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Expense
    </a>
  </div>

  {{-- KPIs --}}
  <div class="kpis">
    <div class="kpi">
      <div class="l">Total Expense Transactions</div>
      <div class="v">{{ number_format($kpi->cnt ?? 0) }}</div>
    </div>
    <div class="kpi">
      <div class="l">Total Posted Amount</div>
      <div class="v"><span class="cur">BDT</span>{{ number_format($kpi->total ?? 0, 2) }}</div>
    </div>
  </div>

  {{-- Filters --}}
  <div class="filters">
    <input type="text" wire:model.live.debounce="search" class="inp grow" placeholder="Search name or notes…" />
    <select wire:model.live="categoryFilter" class="inp">
      <option value="">All categories</option>
      @foreach($expenseCategories as $c)
        <option value="{{ $c->id }}">{{ $c->name }}</option>
      @endforeach
    </select>
    <input type="date" wire:model.live="dateFrom" class="inp flatpickr-only-date" />
    <input type="date" wire:model.live="dateTo" class="inp flatpickr-only-date" />
  </div>

  {{-- Table --}}
  <div class="table-wrap">
    <table class="t">
      <thead>
        <tr>
          <th>Date</th>
          <th>Description</th>
          <th>Category</th>
          <th>Reference</th>
          <th>Ledger Account</th>
          <th class="r">Amount</th>
        </tr>
      </thead>
      <tbody>
        @forelse($expenses as $txn)
          @php
            $ref = null;
            if ($txn->reference_type === 'banking_payment_request' && $bprs->has($txn->reference_id)) {
                $src = $bprs->get($txn->reference_id)->sourceable;
                $ref = $src?->name ?? null;
            }
          @endphp
          <tr>
            <td class="mono">{{ optional($txn->datetime)->format('d M Y') }}</td>
            <td>{{ $txn->name ?? $txn->notes ?? '—' }}</td>
            <td>{{ $txn->transactionCategory?->name ?? '—' }}</td>
            <td>@if($ref)<span class="ref-chip">{{ $ref }}</span>@else <span style="color:var(--muted-2)">—</span> @endif</td>
            <td>{{ $txn->account?->name ?? '—' }}</td>
            <td class="r mono" style="font-weight:600;">{{ number_format($txn->credit, 2) }}</td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted);font-style:italic;">
            No expense transactions yet. Create an expense and complete it in Banking to see it here.
          </td></tr>
        @endforelse
      </tbody>
    </table>
    @if($expenses->hasPages())
      <div class="foot">{{ $expenses->links() }}</div>
    @endif
  </div>
</div>
</div>
