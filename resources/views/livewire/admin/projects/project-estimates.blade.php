{{-- Project Estimates — Tab 2. Matches ui-reference/Project Estimates.html --}}
<div class="prj-page" x-data x-init="$store.pageName = { name: '{{ addslashes($project->name) }} — Estimates', slug: 'projects' }">
<style>
/* Estimate-specific styles */
.est-toolbar { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:16px; }
.est-title-wrap { display:flex; align-items:baseline; gap:12px; }
.est-title-wrap h2 { font-family:"Instrument Serif",Georgia,serif; font-weight:400; font-size:24px; margin:0; }
.est-actions { display:flex; gap:8px; }

/* Version cards */
.versions { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
.ver-card { flex:0 0 auto; min-width:200px; background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:12px 14px; cursor:pointer; transition:box-shadow .15s,border-color .15s; position:relative; }
.ver-card:hover { box-shadow:0 3px 12px rgba(0,0,0,0.06); }
.ver-card.active { border-color:var(--accent); box-shadow:0 0 0 2px var(--accent-soft); }
.ver-card .vc-top { display:flex; align-items:center; justify-content:space-between; gap:8px; }
.ver-card .vc-no { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:11px; font-weight:600; color:var(--ink); }
.ver-card .vc-title { font-size:12.5px; color:var(--ink); font-weight:600; margin-top:6px; }
.ver-card .vc-meta { font-size:10.5px; color:var(--muted); margin-top:3px; font-family:"JetBrains Mono",ui-monospace,monospace; }
.ver-card .vc-amt { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:13px; font-weight:600; color:var(--ink); margin-top:8px; }
.ver-chip { font-size:8.5px; font-weight:700; letter-spacing:0.4px; text-transform:uppercase; padding:2px 7px; border-radius:999px; border:1px solid; }
.ver-chip.draft     { background:#f0f0f1; border-color:var(--rule); color:var(--muted); }
.ver-chip.submitted { background:var(--warn-bg); border-color:var(--warn-bd); color:var(--warn); }
.ver-chip.approved  { background:var(--ok-bg); border-color:var(--ok-bd); color:var(--ok); }
.ver-chip.rejected  { background:var(--danger-bg); border-color:var(--danger-bd); color:var(--danger); }
.ver-card .vc-current { display:inline-flex; align-items:center; gap:4px; margin-top:9px; font-size:9px; font-weight:700; letter-spacing:0.4px; text-transform:uppercase; color:var(--ok); background:var(--ok-bg); border:1px solid var(--ok-bd); padding:2px 8px; border-radius:999px; }
.ver-card .vc-current svg { width:9px; height:9px; }
.ver-card .vc-actions { display:flex; gap:4px; margin-top:10px; padding-top:8px; border-top:1px solid var(--rule-soft); }
.ver-card .vc-act { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:6px; border:1px solid var(--rule); background:var(--paper); color:var(--muted); cursor:pointer; transition:all .12s; }
.ver-card .vc-act svg { width:13px; height:13px; }
.ver-card .vc-act:hover { color:var(--accent); border-color:var(--accent); background:var(--accent-soft); }
.ver-card .vc-act.danger:hover { color:var(--danger); border-color:var(--danger-bd); background:var(--danger-bg); }

/* ===== Estimate Builder ===== */
.builder-head { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:16px; }
.builder-head h2 { font-family:"Instrument Serif",Georgia,serif; font-weight:400; font-size:24px; margin:0; }
.builder-card { background:var(--paper); border:1px solid var(--rule); border-radius:12px; overflow:hidden; margin-bottom:16px; }
.builder-card .bc-head { padding:13px 18px; border-bottom:1px solid var(--rule); font-family:"Instrument Serif",Georgia,serif; font-size:17px; background:#fafafb; }
.builder-card .bc-body { padding:18px; }
.field-label { font-size:10.5px; letter-spacing:0.5px; text-transform:uppercase; color:var(--muted); font-weight:600; margin-bottom:5px; display:block; }
.field-input { width:100%; font-family:inherit; font-size:13px; padding:8px 11px; border:1px solid var(--rule); border-radius:7px; background:var(--paper); color:var(--ink); }
.field-input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }
.field-input.error { border-color:var(--danger); }
.field-err { font-size:10.5px; color:var(--danger); margin-top:3px; }

/* line item rows */
.li-table { width:100%; border-collapse:collapse; }
.li-table thead th { font-size:9.5px; letter-spacing:0.6px; text-transform:uppercase; color:var(--muted); font-weight:600; padding:8px 8px; text-align:left; background:#fafafb; border-bottom:1px solid var(--rule); }
.li-table thead th.right { text-align:right; }
.li-table td { padding:8px 6px; border-bottom:1px solid var(--rule-soft); vertical-align:top; }
.li-input { width:100%; font-family:inherit; font-size:12px; padding:6px 8px; border:1px solid var(--rule); border-radius:6px; background:var(--paper); color:var(--ink); }
.li-input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 2px var(--accent-soft); }
.li-input.num { text-align:right; font-family:"JetBrains Mono",ui-monospace,monospace; }
.li-amount { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12px; font-weight:600; text-align:right; padding-top:12px; white-space:nowrap; }
.li-remove { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; border:1px solid var(--rule); background:var(--paper); color:var(--muted-2); cursor:pointer; margin-top:4px; }
.li-remove:hover { color:var(--danger); border-color:var(--danger-bd); background:var(--danger-bg); }
.li-remove svg { width:14px; height:14px; }
.li-phase-group { background:#f4f6f8; padding:6px 10px; font-size:9.5px; letter-spacing:0.6px; text-transform:uppercase; color:var(--ink-3); font-weight:700; }
.add-item-btn { display:inline-flex; align-items:center; gap:6px; font-family:inherit; font-size:12.5px; font-weight:500; padding:9px 14px; border:1px dashed var(--rule); border-radius:8px; background:var(--paper); color:var(--accent); cursor:pointer; margin-top:12px; }
.add-item-btn:hover { border-color:var(--accent); background:var(--accent-soft); }
.add-item-btn svg { width:14px; height:14px; }
.opt-check { display:inline-flex; align-items:center; gap:5px; font-size:11px; color:var(--muted); cursor:pointer; }

/* Summary cards */
.summary { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.sum-card { background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:14px 16px; border-left:4px solid var(--rule); }
.sum-card.material { border-left-color:var(--material); }
.sum-card.labour   { border-left-color:var(--labour); }
.sum-card.other    { border-left-color:var(--overhead); }
.sum-card.grand    { border-left:none; background:var(--accent); color:#fff; }
.sum-card .s-label { font-size:10.5px; letter-spacing:0.7px; text-transform:uppercase; color:var(--muted); font-weight:600; }
.sum-card.grand .s-label { color:rgba(255,255,255,0.7); }
.sum-card .s-val { font-family:"Instrument Serif",Georgia,serif; font-size:24px; line-height:1; margin-top:8px; }
.sum-card .s-val .cur { font-family:"Inter",sans-serif; font-size:11px; color:var(--muted); vertical-align:5px; margin-right:3px; }
.sum-card.grand .s-val .cur { color:rgba(255,255,255,0.7); }
.sum-card .s-meta { font-size:10.5px; color:var(--muted); margin-top:7px; }
.sum-card.grand .s-meta { color:rgba(255,255,255,0.7); }

/* Budget KPIs */
.section-label { font-family:"Instrument Serif",Georgia,serif; font-weight:400; font-size:20px; margin:24px 0 12px; color:var(--ink); }
.budget-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:18px; }
.bk { background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:14px 16px; position:relative; }
.bk .bk-label { font-size:10.5px; letter-spacing:0.7px; text-transform:uppercase; color:var(--muted); font-weight:600; display:flex; align-items:center; gap:6px; }
.bk .bk-val { font-family:"Instrument Serif",Georgia,serif; font-size:25px; line-height:1; margin-top:8px; color:var(--ink); }
.bk .bk-val .cur { font-family:"Inter",sans-serif; font-size:11px; color:var(--muted); vertical-align:5px; margin-right:3px; }
.bk .bk-meta { font-size:10.5px; color:var(--muted); margin-top:7px; }
.bk.estimated { border-left:4px solid var(--accent); }
.bk.consumed  { border-left:4px solid var(--labour); }
.bk.remaining { border-left:4px solid var(--ok); }
.bk.variance  { border-left:4px solid var(--ok); }
.bk.variance.over { border-left-color:var(--danger); background:var(--danger-bg); }
.bk.variance.over .bk-val { color:var(--danger); }
.bk .bk-bar { height:5px; background:#eef0f2; border-radius:999px; overflow:hidden; margin-top:10px; }
.bk .bk-bar .fill { height:100%; border-radius:999px; background:var(--labour); }

/* BOQ table */
.boq-wrap { background:var(--paper); border:1px solid var(--rule); border-radius:12px; overflow:hidden; }
.est-meta-bar { display:flex; gap:24px; padding:14px 18px; background:#fafafb; border-bottom:1px solid var(--rule); flex-wrap:wrap; align-items:center; }
.emb .k { font-size:9.5px; letter-spacing:0.6px; text-transform:uppercase; color:var(--muted); font-weight:600; }
.emb .v { font-size:12.5px; color:var(--ink); font-weight:500; margin-top:2px; }
.emb .v.mono { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12px; }
.lock-flag { display:inline-flex; align-items:center; gap:5px; font-size:10.5px; font-weight:600; color:var(--ok); background:var(--ok-bg); border:1px solid var(--ok-bd); padding:3px 9px; border-radius:999px; }
.lock-flag svg { width:12px; height:12px; }
.boq-head { display:flex; align-items:center; justify-content:space-between; padding:13px 18px; border-bottom:1px solid var(--rule); flex-wrap:wrap; gap:8px; }
.boq-head h3 { font-family:"Instrument Serif",Georgia,serif; font-weight:400; font-size:18px; margin:0; }
.boq-head .filters { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
table.boq { width:100%; border-collapse:collapse; }
table.boq thead th { text-align:left; font-size:9.5px; letter-spacing:0.7px; text-transform:uppercase; color:var(--muted); font-weight:600; padding:9px 14px; background:#fafafb; border-bottom:1px solid var(--rule); }
table.boq th.right, table.boq td.right { text-align:right; }
table.boq th.center, table.boq td.center { text-align:center; }
table.boq tbody td { padding:10px 14px; font-size:12.5px; color:var(--ink); border-bottom:1px solid var(--rule-soft); vertical-align:middle; }
tr.phase-row td { background:#f4f6f8; padding:7px 14px; font-size:9.5px; letter-spacing:0.8px; text-transform:uppercase; color:var(--ink-3); font-weight:700; border-bottom:1px solid var(--rule); }
tr.phase-row .ph-sub { float:right; font-family:"JetBrains Mono",ui-monospace,monospace; color:var(--muted); font-weight:600; letter-spacing:0.3px; }
.item-name { font-weight:500; color:var(--ink); }
.item-name .opt { font-size:9px; font-weight:600; letter-spacing:0.3px; text-transform:uppercase; color:var(--muted); background:#f0f0f1; border:1px solid var(--rule); padding:1px 5px; border-radius:3px; margin-left:6px; }
.item-remark { font-size:10.5px; color:var(--muted); margin-top:2px; }
td.qty, td.rate, td.amount { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12px; }
td.amount { font-weight:600; }
td.unit-col { color:var(--muted); font-size:11.5px; }
tfoot td { padding:12px 14px; font-size:13px; border-top:2px solid var(--ink); }
tfoot .grand-lbl { text-align:right; font-weight:600; color:var(--muted); letter-spacing:0.5px; text-transform:uppercase; font-size:11px; }
tfoot .grand-val { text-align:right; font-family:"JetBrains Mono",ui-monospace,monospace; font-size:16px; font-weight:700; color:var(--ink); }

/* info row */
.info-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px; }
.panel { background:var(--paper); border:1px solid var(--rule); border-radius:12px; overflow:hidden; }
.panel-head { padding:12px 18px; border-bottom:1px solid var(--rule); display:flex; align-items:center; justify-content:space-between; }
.panel-head h3 { font-family:"Instrument Serif",Georgia,serif; font-weight:400; font-size:17px; margin:0; }
.panel-head .vs { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:11px; color:var(--muted); background:#f0f1f3; padding:2px 8px; border-radius:5px; }
.panel-body { padding:8px 18px 14px; }
</style>

  @include('livewire.admin.projects.partials.project-hero', ['project' => $project, 'showEditButton' => false])
  @include('livewire.admin.projects.partials.tab-bar', [
      'project' => $project,
      'activeTab' => 'estimates',
      'estimatesCount' => $estimates->count() ?: null,
  ])

@if(!$showForm)
  <div wire:key="estimates-list-view">
  {{-- Toolbar --}}
  <div class="est-toolbar">
    <div class="est-title-wrap">
      <h2>Estimates</h2>
      <span style="font-size:12px;color:var(--muted);">{{ $estimates->count() }} version{{ $estimates->count() !== 1 ? 's' : '' }} · BOQ-style breakdown</span>
    </div>
    <div class="est-actions">
      @can('project.edit')
        @if($activeEstimate && $activeEstimate->isLocked())
          <button wire:click="duplicateEstimate({{ $activeEstimate->id }})" wire:confirm="Duplicate this estimate as a new version?" class="btn">
            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Duplicate as new version
          </button>
        @endif
        <button wire:click="createEstimate" class="btn primary">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          New Estimate
        </button>
      @endcan
    </div>
  </div>

  @if($estimates->isEmpty())
    <div style="background:var(--paper);border:1px solid var(--rule);border-radius:12px;padding:60px;text-align:center;">
      <svg viewBox="0 0 24 24" fill="none" stroke="#9aa0a6" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" style="width:46px;height:46px;margin:0 auto 14px;"><path d="M9 7h6M9 11h6M9 15h4"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>
      <p style="font-size:14px;color:var(--ink);font-weight:600;margin-bottom:4px;">No estimates yet</p>
      <p style="font-size:12px;color:var(--muted);margin-bottom:18px;">Create a BOQ-style estimate with material, labour &amp; overhead line items.</p>
      @can('project.edit')
        <button wire:click="createEstimate" class="btn primary" style="margin:0 auto;">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Create First Estimate
        </button>
      @endcan
    </div>
  @else

  {{-- Version selector --}}
  <div class="versions">
    @foreach($estimates as $est)
      @php $isActive = $activeEstimate && $activeEstimate->id === $est->id; @endphp
      <div class="ver-card {{ $isActive ? 'active' : '' }}" wire:click="selectEstimate({{ $est->id }})" style="cursor:pointer;">
        <div class="vc-top">
          <span class="vc-no">{{ $est->estimate_no ?? ('EST-V' . $est->version) }}</span>
          <span class="ver-chip {{ $est->status?->value ?? 'draft' }}">{{ $est->status?->label() ?? 'Draft' }}</span>
        </div>
        <div class="vc-title">{{ $est->title ?? 'Estimate V' . $est->version }}</div>
        <div class="vc-meta">v{{ $est->version }} @if($est->estimate_date) · {{ $est->estimate_date->format('d M Y') }} @endif</div>
        <div class="vc-amt">BDT {{ number_format($est->items->sum('estimated_amount'), 2) }}</div>
        @if($est->status?->value === 'approved' && $estimates->where('status', \App\Enums\Projects\EstimateStatus::APPROVED)->sortByDesc('version')->first()?->id === $est->id)
          <span class="vc-current"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Current Approved Version</span>
        @endif
        @can('project.edit')
          @unless($est->isLocked())
            <div class="vc-actions" wire:click.stop>
              <button wire:click.stop="editEstimate({{ $est->id }})" class="vc-act" title="Edit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <button wire:click.stop="deleteEstimate({{ $est->id }})" wire:confirm="Delete this estimate and all its items?" class="vc-act danger" title="Delete">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
              </button>
            </div>
          @endunless
        @endcan
      </div>
    @endforeach
  </div>

  @if($activeEstimate)

  {{-- Attachments section (always accessible, even when locked) --}}
  @if($activeEstimate)
    <x-media-picker-field
      field="estimateAttachments"
      label="Attachments"
      :value="$activeEstimate->attachments ?? []"
      :multiple="true"
      placeholder="Click to upload files"
      :canEdit="auth()->user()->can('project.edit')" />
  @endif

  {{-- Summary cards --}}
  <div class="summary">
    <div class="sum-card material">
      <div class="s-label">Material</div>
      <div class="s-val"><span class="cur">BDT</span>{{ number_format($totals['material'] ?? 0, 2) }}</div>
      <div class="s-meta">{{ $activeEstimate->items->where('cost_type', \App\Enums\Projects\CostType::MATERIAL)->count() }} items</div>
    </div>
    <div class="sum-card labour">
      <div class="s-label">Labour</div>
      <div class="s-val"><span class="cur">BDT</span>{{ number_format($totals['labour'] ?? 0, 2) }}</div>
      <div class="s-meta">{{ $activeEstimate->items->where('cost_type', \App\Enums\Projects\CostType::LABOUR)->count() }} items</div>
    </div>
    <div class="sum-card other">
      <div class="s-label">Other / Overhead</div>
      <div class="s-val"><span class="cur">BDT</span>{{ number_format(($totals['overhead'] ?? 0) + ($totals['indirect'] ?? 0), 2) }}</div>
      <div class="s-meta">{{ $activeEstimate->items->whereIn('cost_type', [\App\Enums\Projects\CostType::OVERHEAD, \App\Enums\Projects\CostType::INDIRECT])->count() }} items</div>
    </div>
    <div class="sum-card grand">
      <div class="s-label">Grand Total</div>
      <div class="s-val"><span class="cur">BDT</span>{{ number_format($totals['grand'] ?? 0, 2) }}</div>
      <div class="s-meta">{{ $activeEstimate->items->count() }} line items @if($activeEstimate->estimate_date) · {{ $activeEstimate->estimate_date->format('d M Y') }} @endif</div>
    </div>
  </div>

  {{-- Budget Monitoring --}}
  @if($approvedBudget > 0)
  @php
    $spentPct  = $approvedBudget > 0 ? round(($totalSpent / $approvedBudget) * 100, 1) : 0;
    $remAmt    = $approvedBudget - $totalSpent;
    $diff      = $totalSpent - $approvedBudget;
    $isOver    = $diff > 0;
  @endphp
  <div class="section-label">Budget Monitoring</div>
  <div class="budget-kpis">
    <div class="bk estimated">
      <div class="bk-label">Estimated Amount</div>
      <div class="bk-val"><span class="cur">BDT</span>{{ number_format($approvedBudget, 2) }}</div>
      <div class="bk-meta">Approved estimate</div>
    </div>
    <div class="bk consumed">
      <div class="bk-label">Actual Consumed</div>
      <div class="bk-val"><span class="cur">BDT</span>{{ number_format($totalSpent, 2) }}</div>
      <div class="bk-bar"><div class="fill" style="width:{{ min(100, $spentPct) }}%"></div></div>
      <div class="bk-meta">{{ $spentPct }}% of estimate consumed</div>
    </div>
    <div class="bk remaining">
      <div class="bk-label">Remaining Budget</div>
      <div class="bk-val"><span class="cur">BDT</span>{{ number_format(abs($remAmt), 2) }}</div>
      <div class="bk-meta">{{ $isOver ? 'Over budget' : (100 - $spentPct) . '% available' }}</div>
    </div>
    <div class="bk variance {{ $isOver ? 'over' : '' }}">
      <div class="bk-label">Budget Difference</div>
      <div class="bk-val">{{ $isOver ? '+' : '−' }} <span class="cur">BDT</span>{{ number_format(abs($diff), 2) }}</div>
      <div class="bk-meta">{{ $isOver ? 'Over budget · review needed' : 'Within budget · on track' }}</div>
    </div>
  </div>
  @endif

  {{-- BOQ table --}}
  <div class="boq-wrap">
    {{-- Estimate meta bar --}}
    <div class="est-meta-bar">
      <div class="emb"><div class="k">Estimate No.</div><div class="v mono">{{ $activeEstimate->estimate_no ?? ('EST-V' . $activeEstimate->version) }}</div></div>
      <div class="emb"><div class="k">Title</div><div class="v">{{ $activeEstimate->title ?? 'Estimate V' . $activeEstimate->version }}</div></div>
      <div class="emb"><div class="k">Version</div><div class="v">v{{ $activeEstimate->version }}</div></div>
      @if($activeEstimate->estimate_date)
        <div class="emb"><div class="k">Date</div><div class="v mono">{{ $activeEstimate->estimate_date->format('d M Y') }}</div></div>
      @endif
      <div class="emb"><div class="k">Status</div><div class="v"><span class="ver-chip {{ $activeEstimate->status?->value ?? 'draft' }}">{{ $activeEstimate->status?->label() ?? 'Draft' }}</span></div></div>
      @if($activeEstimate->createdBy)
        <div class="emb"><div class="k">Created by</div><div class="v">{{ $activeEstimate->createdBy->name }}@if($activeEstimate->estimate_date) · {{ $activeEstimate->estimate_date->format('d M Y') }}@endif</div></div>
      @endif
      @if($activeEstimate->approvedBy)
        <div class="emb"><div class="k">Approved by</div><div class="v">{{ $activeEstimate->approvedBy->name }}@if($activeEstimate->approved_at) · {{ $activeEstimate->approved_at->format('d M Y') }}@endif</div></div>
      @endif
      @if($activeEstimate->isLocked())
        <div class="emb" style="margin-left:auto;align-self:center;">
          <span class="lock-flag">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Approved &amp; Locked
          </span>
        </div>
      @endif
    </div>

    <div class="boq-head">
      <h3>Bill of Quantities</h3>
      <div class="filters">
        <select class="select" wire:model.live="filterCostType">
          <option value="">All cost types</option>
          <option value="material">Material</option>
          <option value="labour">Labour</option>
          <option value="overhead">Overhead</option>
          <option value="indirect">Indirect</option>
        </select>
        <select class="select" wire:model.live="filterPhase">
          <option value="">All phases</option>
          @foreach(\App\Enums\Projects\WorkPhase::cases() as $phase)
            <option value="{{ $phase->value }}">{{ $phase->label() }}</option>
          @endforeach
        </select>
        @if(!$activeEstimate->isLocked())
          @can('project.edit')
            <button wire:click="editEstimate({{ $activeEstimate->id }})" class="btn">
              <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit items
            </button>
          @endcan
        @endif
        <span style="width:1px;height:22px;background:var(--rule);margin:0 2px;"></span>
        @if($activeEstimate)
          <a href="{{ route('admin.projects.estimates.pdf', [$project->id, $activeEstimate->id]) }}" class="btn" target="_blank">
            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Export PDF
          </a>
        @else
          <button class="btn" disabled style="opacity:0.5;cursor:not-allowed;">
            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Export PDF
          </button>
        @endif
        <button class="btn">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export Excel
        </button>
      </div>
    </div>

    @if($boqItems->isEmpty())
      <div style="padding:40px;text-align:center;color:var(--muted);font-style:italic;">No estimate items found.</div>
    @else
    <table class="boq">
      <thead>
        <tr>
          <th style="width:28%">Item</th>
          <th style="width:13%">Cost Type</th>
          <th style="width:9%">Unit</th>
          <th class="right" style="width:11%">Qty</th>
          <th class="right" style="width:14%">Rate</th>
          <th class="right" style="width:16%">Amount</th>
          <th class="center" style="width:9%">Phase</th>
        </tr>
      </thead>
      <tbody>
        @foreach($boqItems as $phaseKey => $items)
          @php $phaseLabel = $phaseKey ? (\App\Enums\Projects\WorkPhase::tryFrom($phaseKey)?->label() ?? $phaseKey) : 'General'; @endphp
          <tr class="phase-row">
            <td colspan="7">{{ $phaseLabel }} <span class="ph-sub">BDT {{ number_format($items->sum('estimated_amount'), 2) }}</span></td>
          </tr>
          @foreach($items as $item)
          <tr>
            <td>
              <div class="item-name">
                {{ $item->material?->name ?? $item->transactionCategory?->name ?? $item->name ?? '—' }}
                @if($item->is_optional)<span class="opt">Optional</span>@endif
              </div>
              @if($item->remarks)<div class="item-remark">{{ $item->remarks }}</div>@endif
            </td>
            <td>
              @if($item->cost_type)
                <span class="ct-badge {{ $item->cost_type->value }}"><span class="d"></span>{{ $item->cost_type->label() }}</span>
              @else
                <span style="color:var(--muted-2)">—</span>
              @endif
            </td>
            <td class="unit-col">{{ $item->unit ?? '—' }}</td>
            <td class="right qty">{{ number_format($item->estimated_qty, 2) }}</td>
            <td class="right rate">{{ number_format($item->estimated_rate, 2) }}</td>
            <td class="right amount">{{ number_format($item->estimated_amount, 2) }}</td>
            <td class="center">
              @if($item->work_phase)
                <span class="type-chip" style="font-size:9.5px;padding:2px 8px;">{{ $item->work_phase->label() }}</span>
              @else
                <span style="color:var(--muted-2)">—</span>
              @endif
            </td>
          </tr>
          @endforeach
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="5" class="grand-lbl">Grand Total Estimated Amount</td>
          <td class="grand-val" colspan="2">BDT {{ number_format($totals['grand'] ?? 0, 2) }}</td>
        </tr>
      </tfoot>
    </table>
    @endif

  </div>{{-- end boq-wrap --}}

  {{-- Footer actions --}}
  <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:16px;">
    @if($activeEstimate->isLocked())
      <span style="font-size:11.5px;color:var(--muted);display:inline-flex;align-items:center;gap:6px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        This version is approved &amp; locked — duplicate as a new version to make changes.
      </span>
      <div style="display:flex;gap:8px;">
        <button class="btn" disabled style="opacity:0.5;cursor:not-allowed;">Save Draft</button>
        <button class="btn" disabled style="opacity:0.5;cursor:not-allowed;">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Submit for approval
        </button>
        <button wire:click="duplicateEstimate({{ $activeEstimate->id }})" wire:confirm="Duplicate?" class="btn primary">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>Duplicate as new version
        </button>
      </div>
    @else
      <span style="font-size:11.5px;color:var(--muted);">
        {{ $activeEstimate->status?->label() ?? 'Draft' }} · edit items or change status below.
      </span>
      <div style="display:flex;gap:8px;">
        @can('project.edit')
          <button wire:click="editEstimate({{ $activeEstimate->id }})" class="btn">
            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit
          </button>
          @if($activeEstimate->status?->value === 'draft')
            <button wire:click="submitEstimate({{ $activeEstimate->id }})" wire:confirm="Submit this estimate for approval?" class="btn">
              <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Submit for approval
            </button>
          @endif
          <button wire:click="approveEstimate({{ $activeEstimate->id }})" wire:confirm="Approve and lock this estimate? It cannot be edited afterwards." class="btn ok">
            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Approve &amp; Lock
          </button>
        @endcan
      </div>
    @endif
  </div>

  @endif {{-- end if activeEstimate --}}
  @endif {{-- end if not empty --}}
  </div>{{-- end estimates-list-view --}}

@else
  <div wire:key="estimates-builder-view">
  {{-- ============================================================= --}}
  {{-- ESTIMATE BUILDER (create / edit)                              --}}
  {{-- ============================================================= --}}
  @include('livewire.admin.projects.partials.estimate-builder', [
      'project'    => $project,
      'materials'  => $materials,
      'categories' => $categories,
  ])
  </div>{{-- end estimates-builder-view --}}
@endif
</div>
