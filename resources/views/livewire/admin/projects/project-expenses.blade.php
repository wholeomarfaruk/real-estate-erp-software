{{-- Project Expenses — Tab 4. Matches ui-reference/Project Expenses.html --}}
<div class="prj-page" x-data x-init="$store.pageName = { name: '{{ addslashes($project->name) }} — Expenses', slug: 'projects' }">
<style>
/* KPIs */
.kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:18px; }
.kpi { background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:14px 16px; border-left:4px solid var(--rule); }
.kpi.total  { border-left-color:var(--accent); }
.kpi.month  { border-left-color:var(--info); }
.kpi.labour { border-left-color:var(--labour); }
.kpi.other  { border-left-color:var(--other); }
.kpi .label { font-size:10.5px; letter-spacing:0.7px; text-transform:uppercase; color:var(--muted); font-weight:600; }
.kpi .value { font-family:"Instrument Serif",Georgia,serif; font-size:25px; line-height:1; margin-top:8px; color:var(--ink); }
.kpi .value .cur { font-family:"Inter",sans-serif; font-size:11px; color:var(--muted); vertical-align:5px; margin-right:3px; }
.kpi .meta { font-size:10.5px; color:var(--muted); margin-top:7px; }

/* Estimate vs actual widget */
.eva { background:var(--paper); border:1px solid var(--rule); border-radius:12px; overflow:hidden; margin-bottom:18px; }
.eva-head { padding:12px 18px; border-bottom:1px solid var(--rule); display:flex; align-items:center; justify-content:space-between; }
.eva-head h3 { font-family:"Instrument Serif",Georgia,serif; font-weight:400; font-size:17px; margin:0; }
.eva-head .sub { font-size:11px; color:var(--muted); }
table.eva-tbl { width:100%; border-collapse:collapse; }
table.eva-tbl th { text-align:left; font-size:9.5px; letter-spacing:0.6px; text-transform:uppercase; color:var(--muted); font-weight:600; padding:8px 18px; background:#fafafb; border-bottom:1px solid var(--rule); }
table.eva-tbl th.right, table.eva-tbl td.right { text-align:right; }
table.eva-tbl td { padding:10px 18px; font-size:12.5px; border-bottom:1px solid var(--rule-soft); }
table.eva-tbl tr:last-child td { border-bottom:none; }
table.eva-tbl .cat { font-weight:500; color:var(--ink); display:flex; align-items:center; gap:8px; }
table.eva-tbl .mono { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12px; }
.util { display:flex; align-items:center; gap:8px; justify-content:flex-end; }
.util-track { width:80px; height:6px; background:#eef0f2; border-radius:999px; overflow:hidden; }
.util-fill { height:100%; border-radius:999px; }
.util-pct { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:11px; font-weight:600; min-width:36px; text-align:right; }
.remain-pos { color:var(--ok); font-weight:600; }
.remain-neg { color:var(--danger); font-weight:600; }
.type-tag { font-size:9px; font-weight:600; letter-spacing:0.3px; text-transform:uppercase; padding:1px 6px; border-radius:3px; }
.type-tag.labour { background:var(--labour-bg); color:var(--labour); }
.type-tag.other  { background:var(--other-bg); color:var(--other); }

/* filter bar */
.filterbar { background:var(--paper); border:1px solid var(--rule); border-radius:10px 10px 0 0; border-bottom:none; padding:10px 12px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.filterbar .grow { flex:1; }
.tabs { display:flex; gap:2px; background:#f3f4f6; padding:3px; border-radius:8px; }
.tab { font-size:12px; padding:6px 10px; border-radius:6px; cursor:pointer; color:var(--ink-3); display:inline-flex; align-items:center; gap:6px; border:0; background:transparent; font-family:inherit; }
.tab:hover { color:var(--ink); }
.tab.active { background:var(--paper); color:var(--ink); box-shadow:0 1px 2px rgba(0,0,0,0.08); font-weight:600; }
.tab .count { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:10.5px; padding:1px 6px; border-radius:999px; background:rgba(0,0,0,0.05); color:var(--ink-2); font-weight:600; }
.tab.active .count { background:var(--accent); color:#fff; }
.search-wrap { position:relative; }
.search-wrap svg { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:var(--muted-2); }
.input { font-family:inherit; font-size:12.5px; padding:7px 10px 7px 30px; border:1px solid var(--rule); border-radius:6px; background:var(--paper); color:var(--ink-2); min-width:190px; }
.input::placeholder { color:var(--muted-2); }

/* table */
.table-wrap { background:var(--paper); border:1px solid var(--rule); border-radius:0 0 10px 10px; overflow:hidden; }
table.exp { width:100%; border-collapse:collapse; }
table.exp thead th { text-align:left; font-size:9.5px; letter-spacing:0.7px; text-transform:uppercase; color:var(--muted); font-weight:600; padding:9px 14px; background:#fafafb; border-bottom:1px solid var(--rule); }
table.exp th.right, table.exp td.right { text-align:right; }
table.exp th.center, table.exp td.center { text-align:center; }
table.exp tbody td { padding:11px 14px; font-size:12.5px; color:var(--ink); border-bottom:1px solid var(--rule-soft); vertical-align:middle; }
table.exp tbody tr:hover { background:#fafafb; }
.exp-no { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:11px; font-weight:600; color:var(--ink); }
.exp-date { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:10px; color:var(--muted); margin-top:2px; }
.cat-cell .inv { font-size:10.5px; color:var(--muted); margin-top:2px; font-family:"JetBrains Mono",ui-monospace,monospace; }
td.amt { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12.5px; font-weight:600; }
.estatus { display:inline-flex; align-items:center; gap:5px; padding:2px 9px; border-radius:999px; font-size:10px; font-weight:600; letter-spacing:0.3px; border:1px solid; }
.estatus .d { width:5px; height:5px; border-radius:50%; }
.estatus.posted   { background:var(--ok-bg); border-color:var(--ok-bd); color:var(--ok); }
.estatus.approved { background:var(--ok-bg); border-color:var(--ok-bd); color:var(--ok); }
.estatus.pending  { background:var(--warn-bg); border-color:var(--warn-bd); color:var(--warn); }
.estatus.draft    { background:#f0f0f1; border-color:var(--rule); color:var(--muted); }
.estatus.posted .d   { background:var(--ok); }
.estatus.approved .d { background:var(--ok); }
.estatus.pending .d  { background:var(--warn); }
.estatus.draft .d    { background:var(--muted); }
.table-foot { padding:10px 14px; font-size:11.5px; color:var(--muted); background:#fafafb; border-top:1px solid var(--rule); display:flex; justify-content:space-between; align-items:center; }
</style>

  @include('livewire.admin.projects.partials.project-hero', ['project' => $project, 'showEditButton' => false])
  @include('livewire.admin.projects.partials.tab-bar', [
      'project' => $project,
      'activeTab' => 'expenses',
      'expensesCount' => $expenses->total() ?: null,
  ])

  {{-- Toolbar --}}
  <div class="c-toolbar">
    <div class="c-title-wrap">
      <h2>Expenses</h2>
      <span class="note">Labour &amp; other project costs</span>
    </div>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('admin.accounts.expenses.create', ['project_id' => $project->id]) }}" class="btn" style="background:var(--accent);color:#fff;border-color:var(--accent);">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add New Expense
      </a>
      <button class="btn" onclick="window.print()">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>PDF
      </button>
      <button class="btn">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Excel
      </button>
    </div>
  </div>

  {{-- KPI cards --}}
  <div class="kpis">
    <div class="kpi total">
      <div class="label">Total Expense</div>
      <div class="value"><span class="cur">BDT</span>{{ number_format($totalAmount, 2) }}</div>
      <div class="meta">{{ $expenses->total() }} expense entries · all time</div>
    </div>
    <div class="kpi month">
      <div class="label">This Month</div>
      <div class="value"><span class="cur">BDT</span>{{ number_format($thisMonth, 2) }}</div>
      <div class="meta">{{ now()->format('F Y') }}</div>
    </div>
    <div class="kpi labour">
      <div class="label">Labour Expense</div>
      <div class="value"><span class="cur">BDT</span>{{ number_format($labourTotal, 2) }}</div>
      <div class="meta">{{ $totalAmount > 0 ? round(($labourTotal/$totalAmount)*100, 1) : 0 }}% of total</div>
    </div>
    <div class="kpi other">
      <div class="label">Other Expense</div>
      <div class="value"><span class="cur">BDT</span>{{ number_format($otherTotal, 2) }}</div>
      <div class="meta">{{ $totalAmount > 0 ? round(($otherTotal/$totalAmount)*100, 1) : 0 }}% of total</div>
    </div>
  </div>

  {{-- Estimate vs Actual widget --}}
  @if($categories->isNotEmpty())
  <div class="eva">
    <div class="eva-head">
      <h3>Estimate vs Actual — by Category</h3>
      <span class="sub">Against approved estimate</span>
    </div>
    <table class="eva-tbl">
      <thead>
        <tr>
          <th style="width:30%">Category</th>
          <th class="right" style="width:18%">Estimate</th>
          <th class="right" style="width:18%">Actual</th>
          <th class="right" style="width:18%">Remaining</th>
          <th class="right" style="width:16%">Utilisation</th>
        </tr>
      </thead>
      <tbody>
        @foreach($categories as $cat)
          @php
            $isLabour = str_contains(strtolower($cat->name), 'labour') || str_contains(strtolower($cat->name), 'labor');
            $catType = $isLabour ? 'labour' : 'other';
            $actual = (float) ($actualByCategory[$cat->id] ?? 0);
            $est = 0;
            $rem = -$actual;
            $util = 0;
            $isOver = $actual > $est && $est > 0;
          @endphp
          @if($actual > 0)
          <tr>
            <td><div class="cat"><span class="type-tag {{ $catType }}">{{ ucfirst($catType) }}</span>{{ $cat->name }}</div></td>
            <td class="right mono">{{ $est > 0 ? number_format($est, 2) : '—' }}</td>
            <td class="right mono">{{ number_format($actual, 2) }}</td>
            <td class="right">
              @if($est > 0)
                <span class="{{ $rem >= 0 ? 'remain-pos' : 'remain-neg' }} mono">{{ $rem >= 0 ? '+' : '−' }}{{ number_format(abs($rem), 2) }}</span>
              @else
                <span style="color:var(--muted-2)">—</span>
              @endif
            </td>
            <td class="right">
              @if($est > 0)
                @php $util = min(100, round(($actual/$est)*100)); @endphp
                <div class="util">
                  <div class="util-track"><div class="util-fill" style="width:{{ $util }}%;background:{{ $isOver ? 'var(--danger)' : 'var(--labour)' }}"></div></div>
                  <span class="util-pct" style="{{ $isOver ? 'color:var(--danger)' : '' }}">{{ $util }}%</span>
                </div>
              @else
                <span style="color:var(--muted-2)">—</span>
              @endif
            </td>
          </tr>
          @endif
        @endforeach
      </tbody>
    </table>
  </div>
  @endif

  {{-- Filter bar --}}
  <div class="filterbar" x-data="{ tab: 'all' }">
    <div class="tabs">
      <button class="tab" :class="tab === 'all' ? 'active' : ''" @click="tab='all'; $wire.set('filterType',''); $wire.set('filterStatus','')">All <span class="count">{{ $expenses->total() }}</span></button>
      <button class="tab" :class="tab === 'labour' ? 'active' : ''" @click="tab='labour'; $wire.set('filterStatus',''); $wire.set('filterType','labour')">Labour</button>
      <button class="tab" :class="tab === 'pending' ? 'active' : ''" @click="tab='pending'; $wire.set('filterType',''); $wire.set('filterStatus','pending')">Pending</button>
      <button class="tab" :class="tab === 'completed' ? 'active' : ''" @click="tab='completed'; $wire.set('filterType',''); $wire.set('filterStatus','completed')">Completed</button>
    </div>
    <div class="grow"></div>
    <div class="search-wrap">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" wire:model.live.debounce="search" class="input" placeholder="Search expense, vendor, invoice…" />
    </div>
  </div>

  {{-- Pending (requested, not yet posted to ledger) banner --}}
  @if(($pendingTotal ?? 0) > 0)
    <div style="display:flex;align-items:center;gap:10px;background:#fef9e7;border:1px solid #f3e3a8;border-radius:10px;padding:11px 15px;margin-bottom:14px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#a16207" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <span style="font-size:12.5px;color:#a16207;">
        <strong>BDT {{ number_format($pendingTotal, 2) }}</strong> in expense requests are awaiting banking approval and will appear here once completed.
      </span>
    </div>
  @endif

  {{-- Table --}}
  <div class="table-wrap">
    @if($expenses->isEmpty())
      <div style="padding:40px;text-align:center;color:var(--muted);font-style:italic;">
        No posted expenses yet for this project. Expenses appear here after they are completed in Banking.
      </div>
    @else
    <table class="exp">
      <thead>
        <tr>
          <th style="width:120px">Date</th>
          <th style="width:24%">Category</th>
          <th>Description</th>
          <th style="width:100px">Phase</th>
          <th>Ledger Account</th>
          <th class="right" style="width:130px">Amount</th>
        </tr>
      </thead>
      <tbody>
        @foreach($expenses as $expense)
        <tr>
          <td>
            <div class="exp-date" style="font-size:12px;">{{ optional($expense->datetime)->format('d M Y') ?? '—' }}</div>
          </td>
          <td class="cat-cell">
            <span class="ct-badge other"><span class="d"></span>{{ ucfirst($expense->type?->value ?? '—') }}</span>
          </td>
          <td>{{ $expense->name ?? $expense->notes ?? '—' }}</td>
          <td style="font-size:11px;"><span style="display:inline-block;padding:2px 8px;background:#f0f0f1;border-radius:4px;color:var(--muted);">{{ $expense->phase }}</span></td>
          <td>{{ $expense->account?->name ?? '—' }}</td>
          <td class="right amt">BDT {{ number_format($expense->lines->sum('credit'), 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="table-foot">
      <span>Showing {{ $expenses->firstItem() }}–{{ $expenses->lastItem() }} of {{ $expenses->total() }} entries</span>
      @if($expenses->hasPages())
        <div>{{ $expenses->links() }}</div>
      @endif
    </div>
    @endif
  </div>

</div>
