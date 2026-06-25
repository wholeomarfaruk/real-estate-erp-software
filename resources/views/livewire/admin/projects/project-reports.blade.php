{{-- Project Reports — Tab 5. Matches ui-reference/Project Reports.html --}}
<div class="prj-page" x-data x-init="$store.pageName = { name: '{{ addslashes($project->name) }} — Reports', slug: 'projects' }">
<style>
/* KPIs */
.kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:18px; }
.kpi { background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:15px 17px; }
.kpi.hero-kpi { background:var(--accent); color:#fff; border-color:var(--accent); }
.kpi .label { font-size:10.5px; letter-spacing:0.7px; text-transform:uppercase; color:var(--muted); font-weight:600; }
.kpi.hero-kpi .label { color:rgba(255,255,255,0.7); }
.kpi .value { font-family:"Instrument Serif",Georgia,serif; font-size:27px; line-height:1; margin-top:8px; color:var(--ink); }
.kpi.hero-kpi .value { color:#fff; }
.kpi .value .cur { font-family:"Inter",sans-serif; font-size:11px; color:var(--muted); vertical-align:5px; margin-right:3px; }
.kpi.hero-kpi .value .cur { color:rgba(255,255,255,0.7); }
.kpi .meta { font-size:10.5px; color:var(--muted); margin-top:8px; }
.kpi.hero-kpi .meta { color:rgba(255,255,255,0.7); }
.kpi .bar { height:6px; background:#eef0f2; border-radius:999px; overflow:hidden; margin-top:10px; }
.kpi.hero-kpi .bar { background:rgba(255,255,255,0.2); }
.kpi .bar .fill { height:100%; border-radius:999px; background:var(--accent); }
.kpi.hero-kpi .bar .fill { background:#fff; }

/* grids */
.grid-2 { display:grid; grid-template-columns:340px 1fr; gap:16px; margin-bottom:18px; align-items:start; }
.panel { background:var(--paper); border:1px solid var(--rule); border-radius:12px; overflow:hidden; }
.panel-head { padding:13px 18px; border-bottom:1px solid var(--rule); display:flex; align-items:center; justify-content:space-between; }
.panel-head h3 { font-family:"Instrument Serif",Georgia,serif; font-weight:400; font-size:17px; margin:0; }
.panel-head .sub { font-size:11px; color:var(--muted); }
.panel-body { padding:18px; }

/* Donut */
.donut-wrap { display:flex; flex-direction:column; align-items:center; }
.donut { width:170px; height:170px; border-radius:50%; position:relative; }
.donut::after { content:""; position:absolute; inset:30px; background:var(--paper); border-radius:50%; }
.donut-center { position:absolute; inset:0; display:grid; place-items:center; z-index:2; text-align:center; }
.donut-center .dc-val { font-family:"Instrument Serif",Georgia,serif; font-size:18px; color:var(--ink); line-height:1; }
.donut-center .dc-lbl { font-size:9px; letter-spacing:0.6px; text-transform:uppercase; color:var(--muted); margin-top:3px; }
.legend { width:100%; margin-top:18px; display:flex; flex-direction:column; gap:8px; }
.leg-row { display:flex; align-items:center; gap:8px; font-size:12px; }
.leg-dot { width:10px; height:10px; border-radius:3px; flex-shrink:0; }
.leg-name { color:var(--ink-2); flex:1; }
.leg-val { font-family:"JetBrains Mono",ui-monospace,monospace; font-weight:600; color:var(--ink); }
.leg-pct { font-family:"JetBrains Mono",ui-monospace,monospace; color:var(--muted); font-size:11px; min-width:42px; text-align:right; }

/* Estimate vs Actual bars */
.eva-bars { display:flex; flex-direction:column; gap:18px; }
.eb-top { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:7px; }
.eb-name { font-size:13px; font-weight:600; color:var(--ink); display:flex; align-items:center; gap:8px; }
.eb-name .dot { width:9px; height:9px; border-radius:3px; }
.eb-figures { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:11.5px; color:var(--muted); }
.eb-figures strong { color:var(--ink); font-weight:600; }
.eb-track { height:24px; background:#f0f1f3; border-radius:6px; position:relative; overflow:hidden; }
.eb-actual { height:100%; border-radius:6px 0 0 6px; display:flex; align-items:center; padding-left:10px; }
.eb-actual .pct { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:11px; font-weight:700; color:#fff; }
.eb-foot { display:flex; justify-content:space-between; margin-top:5px; font-size:10.5px; }
.eb-foot .rem { color:var(--ok); font-weight:600; font-family:"JetBrains Mono",ui-monospace,monospace; }
.eb-foot .rem.neg { color:var(--danger); }
.eb-foot .est-lbl { color:var(--muted); }
.legend-inline { display:flex; gap:16px; align-items:center; }
.li { display:inline-flex; align-items:center; gap:6px; font-size:11px; color:var(--muted); }
.li .d { width:9px; height:9px; border-radius:3px; }

/* Phase table */
table.rep { width:100%; border-collapse:collapse; }
table.rep th { text-align:left; font-size:9.5px; letter-spacing:0.6px; text-transform:uppercase; color:var(--muted); font-weight:600; padding:9px 18px; background:#fafafb; border-bottom:1px solid var(--rule); }
table.rep th.right, table.rep td.right { text-align:right; }
table.rep td { padding:11px 18px; font-size:12.5px; border-bottom:1px solid var(--rule-soft); }
table.rep tbody tr:last-child td { border-bottom:none; }
table.rep .ph-name { font-weight:600; color:var(--ink); }
table.rep .mono { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12px; }
table.rep tfoot td { padding:12px 18px; border-top:2px solid var(--ink); font-weight:700; font-size:13px; }
.mini-util { display:inline-flex; align-items:center; gap:7px; }
.mini-track { width:64px; height:6px; background:#eef0f2; border-radius:999px; overflow:hidden; }
.mini-fill { height:100%; border-radius:999px; }
.util-num { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:11px; font-weight:600; min-width:34px; text-align:right; }
.pos { color:var(--ok); font-weight:600; }
.neg { color:var(--danger); font-weight:600; }

/* Trend chart */
.trend { display:flex; align-items:flex-end; gap:18px; height:200px; padding:10px 8px 0; }
.tbar-wrap { flex:1; display:flex; flex-direction:column; align-items:center; gap:8px; height:100%; justify-content:flex-end; }
.tbar-stack { width:100%; max-width:46px; display:flex; flex-direction:column; justify-content:flex-end; border-radius:5px 5px 0 0; overflow:hidden; }
.tseg { width:100%; }
.tseg.material { background:var(--material); }
.tseg.labour   { background:var(--labour); }
.tseg.other    { background:var(--other); }
.tbar-lbl { font-size:10.5px; color:var(--muted); font-weight:500; }
.tbar-val { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:10px; color:var(--ink-2); font-weight:600; }
</style>

  @include('livewire.admin.projects.partials.project-hero', ['project' => $project, 'showEditButton' => false])
  @include('livewire.admin.projects.partials.tab-bar', ['project' => $project, 'activeTab' => 'reports'])

  {{-- Toolbar --}}
  <div class="c-toolbar">
    <div class="c-title-wrap">
      <h2>Cost Report</h2>
      <span class="note">Consolidated project cost analysis · as of {{ now()->format('d M Y') }}</span>
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

  {{-- KPI strip --}}
  @php
    $spentPct = $approvedBudget > 0 ? round(($totalSpent / $approvedBudget) * 100, 1) : 0;
    $isOver = $budgetDiff > 0;
  @endphp
  <div class="kpis">
    <div class="kpi hero-kpi">
      <div class="label">Approved Budget</div>
      <div class="value"><span class="cur">BDT</span>{{ number_format($approvedBudget, 2) }}</div>
      <div class="meta">From approved estimate</div>
    </div>
    <div class="kpi">
      <div class="label">Total Spent</div>
      <div class="value"><span class="cur">BDT</span>{{ number_format($totalSpent, 2) }}</div>
      @if($approvedBudget > 0)
        <div class="bar"><div class="fill" style="width:{{ min(100, $spentPct) }}%"></div></div>
      @endif
      <div class="meta">{{ $spentPct }}% of budget</div>
    </div>
    <div class="kpi">
      <div class="label">Remaining Budget</div>
      <div class="value"><span class="cur">BDT</span>{{ number_format(abs($remaining), 2) }}</div>
      <div class="meta">{{ $approvedBudget > 0 ? (100 - $spentPct) . '% available' : '—' }}</div>
    </div>
    <div class="kpi">
      <div class="label">Budget Difference</div>
      <div class="value" style="{{ $isOver ? 'color:var(--danger)' : 'color:var(--ok)' }}">{{ $isOver ? '+' : '−' }} <span class="cur">BDT</span>{{ number_format(abs($budgetDiff), 2) }}</div>
      <div class="meta">{{ $isOver ? 'Over budget · review needed' : 'Within budget · on track' }}</div>
    </div>
  </div>

  {{-- Cost composition + Estimate vs Actual --}}
  @php
    $total = $actualMaterialCost + $actualOther;
    $matPct = $total > 0 ? round(($actualMaterialCost/$total)*100, 1) : 0;
    $othPct = $total > 0 ? round(($actualOther/$total)*100, 1) : 0;
    $donutGradient = "conic-gradient(var(--material) 0% {$matPct}%, var(--other) {$matPct}% 100%)";

    $matActPct = $estMaterial > 0 ? round(($actualMaterialCost/$estMaterial)*100) : 0;
    $othActPct = $estOther > 0 ? round(($actualOther/$estOther)*100) : 0;
  @endphp
  <div class="grid-2">
    {{-- Donut --}}
    <div class="panel">
      <div class="panel-head"><h3>Cost Composition</h3></div>
      <div class="panel-body">
        <div class="donut-wrap">
          <div class="donut" style="background:{{ $total > 0 ? $donutGradient : '#eef0f2' }}">
            <div class="donut-center">
              <div class="dc-val">BDT {{ $total > 0 ? number_format($total/10000000, 1) . 'cr' : '0' }}</div>
              <div class="dc-lbl">Total Spent</div>
            </div>
          </div>
          <div class="legend">
            <div class="leg-row">
              <span class="leg-dot" style="background:var(--material)"></span>
              <span class="leg-name">Material</span>
              <span class="leg-val">{{ number_format($actualMaterialCost, 2) }}</span>
              <span class="leg-pct">{{ $matPct }}%</span>
            </div>
            <div class="leg-row">
              <span class="leg-dot" style="background:var(--other)"></span>
              <span class="leg-name">Expenses</span>
              <span class="leg-val">{{ number_format($actualOther, 2) }}</span>
              <span class="leg-pct">{{ $othPct }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Estimate vs Actual bars --}}
    <div class="panel">
      <div class="panel-head">
        <h3>Estimate vs Actual</h3>
        <div class="legend-inline">
          <span class="li"><span class="d" style="background:var(--accent)"></span>Actual spent</span>
          <span class="li"><span style="border-left:2px dashed var(--muted-2);height:11px;display:inline-block;"></span>Estimate</span>
        </div>
      </div>
      <div class="panel-body">
        <div class="eva-bars">
          @foreach([
            ['label'=>'Material','color'=>'var(--material)','actual'=>$actualMaterialCost,'est'=>$estMaterial],
            ['label'=>'Expenses','color'=>'var(--other)','actual'=>$actualOther,'est'=>$estOther],
          ] as $row)
            @php
              $w = $row['est'] > 0 ? round(($row['actual']/$row['est'])*100) : 0;
              $rem = $row['est'] - $row['actual'];
              $isO = $row['actual'] > $row['est'] && $row['est'] > 0;
            @endphp
            <div class="eb">
              <div class="eb-top">
                <span class="eb-name"><span class="dot" style="background:{{ $row['color'] }}"></span>{{ $row['label'] }}</span>
                <span class="eb-figures"><strong>{{ number_format($row['actual'], 2) }}</strong> / {{ number_format($row['est'], 2) }}</span>
              </div>
              <div class="eb-track">
                <div class="eb-actual" style="width:{{ min(100, $w) }}%;background:{{ $isO ? 'var(--danger)' : $row['color'] }}">
                  @if($w > 10)<span class="pct">{{ $w }}%</span>@endif
                </div>
              </div>
              <div class="eb-foot">
                <span class="est-lbl">Estimate: BDT {{ number_format($row['est'], 2) }}</span>
                <span class="rem {{ $rem < 0 ? 'neg' : '' }}">{{ $rem >= 0 ? 'Remaining ' : 'Over ' }}{{ number_format(abs($rem), 2) }}</span>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- Phase-wise cost --}}
  <div class="panel" style="margin-bottom:18px;">
    <div class="panel-head"><h3>Phase-wise Cost Summary</h3><span class="sub">Estimate vs actual by construction phase</span></div>
    <table class="rep">
      <thead>
        <tr>
          <th style="width:24%">Phase</th>
          <th class="right">Estimate</th>
          <th class="right">Material</th>
          <th class="right">Other</th>
          <th class="right">Actual Total</th>
          <th class="right" style="width:14%">Utilisation</th>
        </tr>
      </thead>
      <tbody>
        @foreach($phaseRows as $row)
          @php $util = $row['estimated'] > 0 ? round(($row['actual']/$row['estimated'])*100) : 0; @endphp
          <tr>
            <td class="ph-name">{{ $row['phase'] }}</td>
            <td class="right mono">{{ $row['estimated'] > 0 ? number_format($row['estimated'], 2) : '—' }}</td>
            <td class="right mono">{{ $row['material_actual'] > 0 ? number_format($row['material_actual'], 2) : '—' }}</td>
            <td class="right mono">{{ $row['other_actual'] > 0 ? number_format($row['other_actual'], 2) : '—' }}</td>
            <td class="right mono">{{ $row['actual'] > 0 ? number_format($row['actual'], 2) : '—' }}</td>
            <td class="right">
              <span class="mini-util">
                <span class="mini-track"><span class="mini-fill" style="width:{{ min(100,$util) }}%;background:{{ $util > 90 ? 'var(--danger)' : ($util > 60 ? 'var(--accent)' : 'var(--muted-2)') }}"></span></span>
                <span class="util-num">{{ $util }}%</span>
              </span>
            </td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td>Total</td>
          <td class="right mono">{{ number_format($approvedBudget, 2) }}</td>
          <td class="right mono">{{ number_format($actualMaterialCost, 2) }}</td>
          <td class="right mono">{{ number_format($actualOther, 2) }}</td>
          <td class="right mono">{{ number_format($totalSpent, 2) }}</td>
          <td class="right"><span class="util-num" style="min-width:auto">{{ $spentPct }}%</span></td>
        </tr>
      </tfoot>
    </table>
  </div>

  {{-- Monthly spend trend --}}
  @if($monthlySpend->isNotEmpty())
  <div class="grid-2" style="grid-template-columns:1fr 420px;">
    <div class="panel">
      <div class="panel-head">
        <h3>Monthly Spend Trend</h3>
        <div class="legend-inline">
          <span class="li"><span class="d" style="background:var(--material)"></span>Material</span>
          <span class="li"><span class="d" style="background:var(--labour)"></span>Labour</span>
          <span class="li"><span class="d" style="background:var(--other)"></span>Other</span>
        </div>
      </div>
      <div class="panel-body">
        @php $maxM = max($monthlySpend->values()->max(), 1); @endphp
        <div class="trend">
          @foreach($monthlySpend as $month => $amount)
            @php
              $barH = round(($amount / $maxM) * 100);
              $label = \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M');
              $valLabel = number_format($amount/10000000, 1) . 'cr';
            @endphp
            <div class="tbar-wrap" title="{{ $month }}: BDT {{ number_format($amount, 2) }}">
              <span class="tbar-val">{{ $valLabel }}</span>
              <div class="tbar-stack" style="height:{{ max(4, $barH) }}%">
                <div class="tseg material" style="height:70%"></div>
                <div class="tseg labour" style="height:20%"></div>
                <div class="tseg other" style="height:10%"></div>
              </div>
              <span class="tbar-lbl">{{ $label }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Budget Difference</h3></div>
      <div style="display:flex;flex-direction:column;gap:0;">
        @if($actualMaterialCost > 0 || $actualOther > 0)
          @foreach([
            ['label'=>'Material Cost','detail'=>'From inventory issues','amount'=>$actualMaterialCost,'type'=>'under'],
            ['label'=>'Expenses','detail'=>'From expense records','amount'=>$actualOther,'type'=>'under'],
          ] as $vr)
          <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--rule-soft);">
            <div style="width:32px;height:32px;border-radius:8px;display:grid;place-items:center;flex-shrink:0;background:var(--ok-bg);color:var(--ok);">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
            </div>
            <div style="flex:1;">
              <div style="font-size:12.5px;font-weight:600;color:var(--ink);">{{ $vr['label'] }}</div>
              <div style="font-size:10.5px;color:var(--muted);margin-top:1px;">{{ $vr['detail'] }}</div>
            </div>
            <span style="font-family:'JetBrains Mono',ui-monospace,monospace;font-size:13px;font-weight:700;color:var(--ok);">BDT {{ number_format($vr['amount'], 2) }}</span>
          </div>
          @endforeach
        @else
          <div style="padding:40px;text-align:center;color:var(--muted);font-style:italic;">No spending data yet.</div>
        @endif
      </div>
    </div>
  </div>
  @endif

</div>
