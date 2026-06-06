{{-- Project Consumption — Tab 3. Matches ui-reference/Project Consumption.html --}}
<div class="prj-page" x-data x-init="$store.pageName = { name: '{{ addslashes($project->name) }} — Consumption', slug: 'projects' }">
<style>
/* teal */
:root { --teal:#0e7490; --teal-bg:#e6f5f8; }

/* KPIs */
.kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:18px; }
.kpi { background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:14px 16px; border-left:4px solid var(--rule); }
.kpi.estimated { border-left-color:var(--accent); }
.kpi.consumed  { border-left-color:var(--teal); }
.kpi.remaining { border-left-color:var(--ok); }
.kpi.over { border-left-color:var(--danger); background:var(--danger-bg); }
.kpi .label { font-size:10.5px; letter-spacing:0.7px; text-transform:uppercase; color:var(--muted); font-weight:600; }
.kpi.over .label { color:var(--danger); }
.kpi .value { font-family:"Instrument Serif",Georgia,serif; font-size:25px; line-height:1; margin-top:8px; color:var(--ink); }
.kpi.over .value { color:var(--danger); }
.kpi .value .cur { font-family:"Inter",sans-serif; font-size:11px; color:var(--muted); vertical-align:5px; margin-right:3px; }
.kpi .meta { font-size:10.5px; color:var(--muted); margin-top:7px; }
.kpi.over .meta { color:var(--danger); }
.kpi .bar { height:5px; background:#eef0f2; border-radius:999px; overflow:hidden; margin-top:10px; }
.kpi .bar .fill { height:100%; border-radius:999px; background:var(--teal); }

/* Alert */
.alert { display:flex; align-items:center; gap:12px; padding:12px 16px; background:var(--danger-bg); border:1px solid var(--danger-bd); border-radius:10px; margin-bottom:16px; }
.alert .a-icon { width:34px; height:34px; border-radius:8px; background:#fff; color:var(--danger); display:grid; place-items:center; flex-shrink:0; }
.alert .a-icon svg { width:17px; height:17px; }
.alert .a-text { flex:1; font-size:12.5px; color:var(--danger); }
.alert .a-text strong { font-weight:700; }

/* filter bar */
.filterbar { background:var(--paper); border:1px solid var(--rule); border-radius:10px 10px 0 0; border-bottom:none; padding:10px 12px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.filterbar .grow { flex:1; }
.search-wrap { position:relative; }
.search-wrap svg { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:var(--muted-2); }
.input { font-family:inherit; font-size:12.5px; padding:7px 10px 7px 30px; border:1px solid var(--rule); border-radius:6px; background:var(--paper); color:var(--ink-2); min-width:200px; }
.input::placeholder { color:var(--muted-2); }

/* table */
.table-wrap { background:var(--paper); border:1px solid var(--rule); border-radius:0 0 10px 10px; overflow:hidden; }
table.cons { width:100%; border-collapse:collapse; }
table.cons thead th { text-align:left; font-size:9.5px; letter-spacing:0.7px; text-transform:uppercase; color:var(--muted); font-weight:600; padding:9px 14px; background:#fafafb; border-bottom:1px solid var(--rule); }
table.cons th.right, table.cons td.right { text-align:right; }
table.cons th.center, table.cons td.center { text-align:center; }
table.cons tbody td { padding:11px 14px; font-size:12.5px; color:var(--ink); border-bottom:1px solid var(--rule-soft); vertical-align:middle; }
table.cons tbody tr.mat-row:hover { background:#fafafb; }
tr.phase-row td { background:#f4f6f8; padding:7px 14px; font-size:9.5px; letter-spacing:0.8px; text-transform:uppercase; color:var(--ink-3); font-weight:700; border-bottom:1px solid var(--rule); }
tr.phase-row .ph-sub { float:right; font-family:"JetBrains Mono",ui-monospace,monospace; color:var(--muted); font-weight:600; }
td.qty { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12px; }
td.unit-col { color:var(--muted); font-size:11.5px; }
td.extra .ex { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12px; font-weight:600; color:var(--danger); }
td.extra .none { color:var(--muted-2); }

/* progress cell */
.prog { display:flex; align-items:center; gap:8px; min-width:120px; }
.prog-track { flex:1; height:7px; background:#eef0f2; border-radius:999px; overflow:hidden; }
.prog-fill { height:100%; border-radius:999px; }
.prog-pct { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:11.5px; font-weight:600; min-width:40px; text-align:right; }

/* status chips */
.cstatus { display:inline-flex; align-items:center; gap:5px; padding:2px 9px; border-radius:999px; font-size:10px; font-weight:600; letter-spacing:0.3px; border:1px solid; white-space:nowrap; }
.cstatus .d { width:5px; height:5px; border-radius:50%; }
.cstatus.not-started { background:#f0f0f1; border-color:var(--rule); color:var(--muted); }
.cstatus.in-progress { background:var(--info-bg); border-color:var(--info-bd); color:var(--info); }
.cstatus.completed   { background:var(--ok-bg); border-color:var(--ok-bd); color:var(--ok); }
.cstatus.over        { background:var(--danger-bg); border-color:var(--danger-bd); color:var(--danger); }
.cstatus.not-started .d { background:var(--muted); }
.cstatus.in-progress .d { background:var(--info); }
.cstatus.completed .d   { background:var(--ok); }
.cstatus.over .d        { background:var(--danger); }

.table-foot { padding:10px 14px; font-size:11.5px; color:var(--muted); background:#fafafb; border-top:1px solid var(--rule); }
</style>

  @include('livewire.admin.projects.partials.project-hero', ['project' => $project, 'showEditButton' => false])
  @include('livewire.admin.projects.partials.tab-bar', ['project' => $project, 'activeTab' => 'consumption'])

  {{-- Toolbar --}}
  <div class="c-toolbar">
    <div class="c-title-wrap">
      <h2>Material Consumption</h2>
      <span class="note">Estimate vs actual usage — sourced from inventory issues</span>
    </div>
    <div style="display:flex;gap:8px;">
      <button class="btn" onclick="window.print()">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Export PDF
      </button>
      <button class="btn">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export Excel
      </button>
    </div>
  </div>

  {{-- Over-consumption alert --}}
  @if($overCount > 0)
  <div class="alert">
    <div class="a-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </div>
    <div class="a-text"><strong>{{ $overCount }} material{{ $overCount > 1 ? 's' : '' }}</strong> have exceeded their estimated quantity — review to control budget overrun.</div>
  </div>
  @endif

  {{-- KPI cards --}}
  <div class="kpis">
    <div class="kpi estimated">
      <div class="label">Estimated Material</div>
      <div class="value">{{ number_format($totalEstimated, 2) }}</div>
      <div class="meta">Units from approved estimate</div>
      <div style="margin-top:6px;font-size:11px;color:var(--muted);padding-top:8px;border-top:1px solid var(--rule-soft);">BDT {{ number_format($totalEstimatedValue, 0) }}</div>
    </div>
    <div class="kpi consumed">
      <div class="label">Consumed</div>
      <div class="value">{{ number_format($totalConsumed, 2) }}</div>
      @if($totalEstimated > 0)
        <div class="bar"><div class="fill" style="width:{{ min(100, round(($totalConsumed/$totalEstimated)*100)) }}%"></div></div>
        <div class="meta">{{ round(($totalConsumed/$totalEstimated)*100, 1) }}% of estimate consumed</div>
      @endif
      <div style="margin-top:6px;font-size:11px;color:var(--muted);padding-top:8px;border-top:1px solid var(--rule-soft);">BDT {{ number_format($totalConsumedValue, 0) }}</div>
    </div>
    <div class="kpi remaining">
      <div class="label">Remaining</div>
      <div class="value">{{ number_format(max(0, $totalEstimated - $totalConsumed), 2) }}</div>
      <div class="meta">Units available</div>
      <div style="margin-top:6px;font-size:11px;color:var(--muted);padding-top:8px;border-top:1px solid var(--rule-soft);">BDT {{ number_format($totalRemainingValue, 0) }}</div>
    </div>
    <div class="kpi over">
      <div class="label">Over Consumption</div>
      <div class="value">{{ number_format($totalOver, 2) }}</div>
      <div class="meta">From {{ $overCount }} over-consumed material{{ $overCount !== 1 ? 's' : '' }}</div>
      <div style="margin-top:6px;font-size:11px;color:var(--danger);padding-top:8px;border-top:1px solid var(--danger-bd);">BDT {{ number_format($totalOverValue, 0) }}</div>
    </div>
  </div>

  {{-- Filter bar --}}
  <div class="filterbar">
    <div class="search-wrap">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" wire:model.live.debounce="search" class="input" placeholder="Search material…" />
    </div>
    <select class="select" wire:model.live="filterPhase">
      <option value="">All phases</option>
      @foreach(\App\Enums\Projects\WorkPhase::cases() as $phase)
        <option value="{{ $phase->value }}">{{ $phase->label() }}</option>
      @endforeach
    </select>
    <select class="select" wire:model.live="filterStatus">
      <option value="">All statuses</option>
      <option value="not_started">Not Started</option>
      <option value="in_progress">In Progress</option>
      <option value="completed">Completed</option>
      <option value="over_consumed">Over Consumed</option>
    </select>
    <div class="grow"></div>
  </div>

  {{-- Table --}}
  <div class="table-wrap">
    @if($rowsByPhase->isEmpty())
      <div style="padding:40px;text-align:center;color:var(--muted);font-style:italic;">
        No material consumption data found. Add an approved estimate and post stock consumptions for this project.
      </div>
    @else
    <table class="cons">
      <thead>
        <tr>
          <th style="width:24%">Material</th>
          <th style="width:8%">Unit</th>
          <th class="right" style="width:12%">Estimated Qty</th>
          <th class="right" style="width:12%">Consumed Qty</th>
          <th class="right" style="width:12%">Remaining Qty</th>
          <th class="right" style="width:9%">Extra Qty</th>
          <th style="width:14%">Progress</th>
          <th class="center" style="width:9%">Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rowsByPhase as $phase => $rows)
          @php $phaseLabel = $phase ? (\App\Enums\Projects\WorkPhase::tryFrom($phase)?->label() ?? $phase) : 'General'; @endphp
          <tr class="phase-row">
            <td colspan="8">{{ $phaseLabel }} <span class="ph-sub">{{ count($rows) }} material{{ count($rows) !== 1 ? 's' : '' }}</span></td>
          </tr>
          @foreach($rows as $row)
            @php
              $st = $row['status'];
              $stClass = str_replace('_', '-', $st);
              $stLabel = match($st) { 'not_started'=>'Not Started','in_progress'=>'In Progress','completed'=>'Completed','over_consumed'=>'Over Consumed',default=>$st };
              $barColor = match($st) { 'completed'=>'var(--ok)','over_consumed'=>'var(--danger)','in_progress'=>'var(--teal)',default=>'var(--muted-2)' };
              $pctColor = $st === 'over_consumed' ? 'var(--danger)' : 'var(--ink)';
            @endphp
            <tr class="mat-row">
              <td>
                <div style="font-weight:500;color:var(--ink);">{{ $row['name'] }}</div>
              </td>
              <td class="unit-col">{{ $row['unit'] }}</td>
              <td class="right qty">{{ number_format($row['est_qty'], 2) }}</td>
              <td class="right qty" style="font-weight:600;">{{ number_format($row['consumed'], 2) }}</td>
              <td class="right qty">{{ number_format($row['remaining'], 2) }}</td>
              <td class="right extra">
                @if($row['extra'] > 0)
                  <span class="ex">+{{ number_format($row['extra'], 2) }}</span>
                @else
                  <span class="none">—</span>
                @endif
              </td>
              <td>
                <div class="prog">
                  <div class="prog-track"><div class="prog-fill" style="width:{{ min(100, $row['pct']) }}%;background:{{ $barColor }}"></div></div>
                  <span class="prog-pct" style="color:{{ $pctColor }}">{{ $row['pct'] }}%</span>
                </div>
              </td>
              <td class="center">
                <span class="cstatus {{ $stClass }}"><span class="d"></span>{{ $stLabel }}</span>
              </td>
            </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
    @php $totalRows = $rowsByPhase->flatten(1)->count(); @endphp
    <div class="table-foot">Showing {{ $totalRows }} material{{ $totalRows !== 1 ? 's' : '' }} · click any row for transaction history</div>
    @endif
  </div>

</div>
