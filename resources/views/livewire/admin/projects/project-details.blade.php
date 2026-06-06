{{-- Project Details — Tab 1. Matches ui-reference/Project Details.html --}}
<div class="prj-page" x-data x-init="$store.pageName = { name: '{{ addslashes($project->name) }}', slug: 'projects' }">
<style>
/* ---- KPI strip ---- */
.kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:18px; }
.kpi { background:var(--paper); border:1px solid var(--rule); border-radius:10px; padding:14px 16px; }
.kpi .label { font-size:10.5px; letter-spacing:0.8px; text-transform:uppercase; color:var(--muted); font-weight:600; }
.kpi .value { font-family:"Instrument Serif",Georgia,serif; font-size:26px; line-height:1; margin-top:8px; color:var(--ink); }
.kpi .value .cur { font-family:"Inter",sans-serif; font-size:11px; color:var(--muted); vertical-align:5px; margin-right:3px; letter-spacing:0.5px; }
.kpi .meta { font-size:11px; color:var(--muted); margin-top:7px; }
.kpi .meta .pos { color:var(--ok); font-weight:600; }
.kpi .meta .neg { color:var(--danger); font-weight:600; }
.kpi.spent { border-left:4px solid var(--accent); }

/* ---- Content grid ---- */
.content { display:grid; grid-template-columns:1fr 340px; gap:16px; align-items:start; }
.card { background:var(--paper); border:1px solid var(--rule); border-radius:12px; margin-bottom:16px; overflow:hidden; }
.card-head { padding:13px 18px; border-bottom:1px solid var(--rule); display:flex; align-items:center; justify-content:space-between; }
.card-head h3 { font-family:"Instrument Serif",Georgia,serif; font-weight:400; font-size:17px; margin:0; color:var(--ink); }
.card-head .edit { font-size:11.5px; color:var(--muted); text-decoration:none; display:inline-flex; align-items:center; gap:5px; }
.card-head .edit:hover { color:var(--accent); }
.card-head .edit svg { width:12px; height:12px; }
.card-body { padding:16px 18px; }

/* def grid */
.def-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px 28px; }
.def-grid.three { grid-template-columns:repeat(3,1fr); }
.def dt { font-size:10px; letter-spacing:0.7px; text-transform:uppercase; color:var(--muted); font-weight:600; margin-bottom:4px; }
.def dd { margin:0; font-size:13.5px; color:var(--ink); font-weight:500; }
.def dd.mono { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12.5px; }
.def dd .unit { font-size:11px; color:var(--muted); font-weight:400; margin-left:3px; }
.def dd.empty { color:var(--muted-2); font-weight:400; font-style:italic; }
.desc-text { font-size:13px; color:var(--ink-2); line-height:1.65; }

/* construction progress */
.cp-overall { display:flex; align-items:center; gap:18px; padding:4px 0 18px; margin-bottom:18px; border-bottom:1px solid var(--rule-soft); }
.cp-ring { position:relative; width:84px; height:84px; flex-shrink:0; }
.cp-ring svg { transform:rotate(-90deg); }
.cp-ring .cp-pct { position:absolute; inset:0; display:grid; place-items:center; font-family:"Instrument Serif",Georgia,serif; font-size:22px; color:var(--ink); }
.cp-overall-info .cp-t { font-size:13.5px; font-weight:600; color:var(--ink); }
.cp-overall-info .cp-s { font-size:11.5px; color:var(--muted); margin-top:3px; line-height:1.5; }
.cp-overall-info .cp-s strong { color:var(--ink-2); font-weight:600; }
.cp-phase { display:flex; align-items:center; gap:14px; padding:10px 0; border-bottom:1px solid var(--rule-soft); }
.cp-phase:last-child { border-bottom:none; }
.cp-phase .cp-name { flex:0 0 150px; font-size:12.5px; font-weight:500; color:var(--ink); display:flex; align-items:center; gap:8px; }
.cp-phase .cp-name .cp-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.cp-track { flex:1; height:8px; background:#eef0f2; border-radius:999px; overflow:hidden; }
.cp-bar { height:100%; border-radius:999px; }
.cp-val { flex:0 0 92px; text-align:right; font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12px; font-weight:600; color:var(--ink); }
.cp-status { flex:0 0 92px; text-align:right; }
.cp-tag { font-size:9.5px; font-weight:600; letter-spacing:0.3px; text-transform:uppercase; padding:2px 8px; border-radius:999px; border:1px solid; }
.cp-tag.done    { background:var(--ok-bg); border-color:var(--ok-bd); color:var(--ok); }
.cp-tag.active  { background:var(--info-bg); border-color:var(--info-bd); color:var(--info); }
.cp-tag.pending { background:#f0f0f1; border-color:var(--rule); color:var(--muted); }

/* timeline */
.timeline-bar { display:flex; align-items:center; margin:4px 0 18px; padding:0 4px; }
.tl-node { display:flex; flex-direction:column; align-items:center; gap:8px; position:relative; flex:0 0 auto; }
.tl-dot { width:14px; height:14px; border-radius:50%; background:#fff; border:2px solid var(--rule); z-index:2; }
.tl-node.done .tl-dot { background:var(--accent); border-color:var(--accent); }
.tl-node.current .tl-dot { background:#fff; border-color:var(--accent); box-shadow:0 0 0 4px var(--accent-soft); }
.tl-connector { flex:1; height:2px; background:var(--rule); margin:0 -2px; position:relative; top:-11px; }
.tl-connector.done { background:var(--accent); }
.tl-meta { text-align:center; }
.tl-meta .t-label { font-size:9.5px; letter-spacing:0.5px; text-transform:uppercase; color:var(--muted); font-weight:600; }
.tl-meta .t-date { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:11px; color:var(--ink); margin-top:2px; }
.tl-meta .t-date.empty { color:var(--muted-2); }

/* documents */
.doc-list { display:flex; flex-direction:column; gap:2px; }
.doc-row { display:flex; align-items:center; gap:12px; padding:9px 10px; border-radius:8px; cursor:pointer; }
.doc-row:hover { background:#fafafb; }
.doc-icon { width:34px; height:34px; border-radius:7px; display:grid; place-items:center; flex-shrink:0; }
.doc-icon svg { width:15px; height:15px; }
.doc-icon.pdf { background:var(--danger-bg); color:var(--danger); }
.doc-icon.img { background:var(--info-bg); color:var(--info); }
.doc-icon.dwg { background:#f3edfb; color:#6d28d9; }
.doc-icon.file { background:var(--accent-soft); color:var(--accent); }
.doc-info { flex:1; min-width:0; }
.doc-info .name { font-size:12.5px; font-weight:500; color:var(--ink); }
.doc-info .meta { font-size:10.5px; color:var(--muted); margin-top:1px; }
.doc-dl { color:var(--muted-2); padding:4px; border-radius:5px; }
.doc-row:hover .doc-dl { color:var(--accent); }
.doc-dl svg { width:15px; height:15px; display:block; }

/* team */
.person { display:flex; align-items:center; gap:12px; padding:4px 0; }
.person .pav { width:40px; height:40px; border-radius:50%; background:var(--accent); color:#fff; display:grid; place-items:center; font-family:"Instrument Serif",Georgia,serif; font-size:17px; flex-shrink:0; }
.person .pinfo .pname { font-size:13.5px; font-weight:600; color:var(--ink); }
.person .pinfo .prole { font-size:11px; color:var(--muted); margin-top:1px; }

/* meta list */
.meta-list { display:flex; flex-direction:column; }
.meta-row { display:flex; justify-content:space-between; align-items:baseline; padding:9px 0; border-bottom:1px solid var(--rule-soft); font-size:12.5px; }
.meta-row:last-child { border-bottom:none; }
.meta-row .k { color:var(--muted); }
.meta-row .v { color:var(--ink); font-weight:500; font-family:"JetBrains Mono",ui-monospace,monospace; font-size:12px; }

/* quick links */
.quick-links { display:flex; flex-direction:column; gap:2px; }
.ql { display:flex; align-items:center; gap:11px; padding:10px 11px; border-radius:8px; text-decoration:none; color:var(--ink); }
.ql:hover { background:#fafafb; }
.ql .qic { width:32px; height:32px; border-radius:7px; background:var(--accent-soft); color:var(--accent); display:grid; place-items:center; flex-shrink:0; }
.ql .qic svg { width:15px; height:15px; }
.ql .qinfo { flex:1; }
.ql .qinfo .qt { font-size:12.5px; font-weight:600; display:block; }
.ql .qinfo .qd { font-size:10.5px; color:var(--muted); margin-top:1px; display:block; }
.ql .qarrow { color:var(--muted-2); }
.ql:hover .qarrow { color:var(--accent); }
.ql .qarrow svg { width:14px; height:14px; display:block; }
</style>

  @include('livewire.admin.projects.partials.project-hero', ['project' => $project, 'showEditButton' => true])
  @include('livewire.admin.projects.partials.tab-bar', [
      'project'          => $project,
      'activeTab'        => 'details',
      'estimatesCount'   => $project->estimates()->count() ?: null,
      'consumptionCount' => null,
      'expensesCount'    => null,
  ])

  {{-- KPI strip --}}
  <div class="kpis">
    <div class="kpi">
      <div class="label">Budget</div>
      <div class="value"><span class="cur">BDT</span>{{ $project->budget ? number_format($project->budget, 2) : '—' }}</div>
      <div class="meta">Approved project budget</div>
    </div>
    <div class="kpi spent">
      <div class="label">Spent to date</div>
      <div class="value"><span class="cur">BDT</span>{{ number_format($totalSpent, 2) }}</div>
      <div class="meta">
        @if($project->budget && $project->budget > 0)
          @php $spentPct = round(($totalSpent / $project->budget) * 100, 1); @endphp
          <span class="{{ $spentPct > 80 ? 'neg' : 'pos' }}">{{ $spentPct }}%</span> of budget used
        @else
          —
        @endif
      </div>
    </div>
    <div class="kpi">
      <div class="label">Remaining</div>
      <div class="value"><span class="cur">BDT</span>{{ number_format(abs($remaining), 2) }}</div>
      <div class="meta">
        @if($project->budget && $project->budget > 0)
          @php $remPct = round(($remaining / $project->budget) * 100, 1); @endphp
          <span class="{{ $remaining >= 0 ? 'pos' : 'neg' }}">{{ abs($remPct) }}%</span> {{ $remaining >= 0 ? 'available' : 'over budget' }}
        @else
          —
        @endif
      </div>
    </div>
    <div class="kpi">
      <div class="label">Days to handover</div>
      <div class="value">{{ $daysLeft ?? '—' }}</div>
      <div class="meta">@if($project->handover_date) Target · {{ $project->handover_date->format('d M Y') }} @else No handover date set @endif</div>
    </div>
  </div>

  {{-- Content grid --}}
  <div class="content">
    <div class="main-col">

      {{-- Basic Info --}}
      <div class="card">
        <div class="card-head">
          <h3>Basic Information</h3>
          @can('project.edit')
            <button wire:click="openEditModal" type="button" class="edit">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit
            </button>
          @endcan
        </div>
        <div class="card-body">
          <div class="def-grid">
            <div class="def"><dt>Project Code</dt><dd class="mono">{{ $project->code ?? '—' }}</dd></div>
            <div class="def"><dt>Project Name</dt><dd>{{ $project->name }}</dd></div>
            <div class="def"><dt>Project Types</dt><dd>{{ implode(', ', $project->typeLabels()) ?: '—' }}</dd></div>
            <div class="def">
              <dt>Status</dt>
              <dd>
                @php
                  $sc = match($project->status?->value) { 'ongoing'=>'running','on_hold'=>'on_hold','completed'=>'completed','cancelled'=>'cancelled',default=>'upcoming' };
                @endphp
                <span class="status-badge {{ $sc }}" style="font-size:10px;padding:2px 9px;">
                  <span class="dot"></span>{{ $project->status?->label() }}
                </span>
              </dd>
            </div>
          </div>
        </div>
      </div>

      {{-- Location & Area --}}
      <div class="card">
        <div class="card-head">
          <h3>Location &amp; Area</h3>
          @can('project.edit')
            <button wire:click="openEditModal" type="button" class="edit">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit
            </button>
          @endcan
        </div>
        <div class="card-body">
          <div class="def" style="margin-bottom:16px;">
            <dt>Location</dt>
            <dd style="font-weight:400;font-size:13px;line-height:1.5;">{{ $project->location ?? '—' }}</dd>
          </div>
          <div class="def-grid">
            <div class="def">
              <dt>Land Area</dt>
              <dd class="mono">{{ $project->land_area ? number_format($project->land_area, 2) : '—' }}<span class="unit">sft</span></dd>
            </div>
            <div class="def">
              <dt>Building Area</dt>
              <dd class="mono">{{ $project->building_area ? number_format($project->building_area, 2) : '—' }}<span class="unit">sft</span></dd>
            </div>
          </div>
        </div>
      </div>

      {{-- Timeline --}}
      <div class="card">
        <div class="card-head">
          <h3>Timeline</h3>
        </div>
        <div class="card-body">
          @php
            $now = now();
            $startDone = $project->start_date && $project->start_date->isPast();
            $endDone   = $project->end_date && $project->end_date->isPast();
          @endphp
          <div class="timeline-bar">
            <div class="tl-node {{ $startDone ? 'done' : '' }}">
              <div class="tl-dot"></div>
              <div class="tl-meta">
                <div class="t-label">Start</div>
                <div class="t-date {{ !$project->start_date ? 'empty' : '' }}">{{ optional($project->start_date)->format('d M Y') ?? '— pending —' }}</div>
              </div>
            </div>
            <div class="tl-connector {{ $startDone ? 'done' : '' }}"></div>
            <div class="tl-node current">
              <div class="tl-dot"></div>
              <div class="tl-meta">
                <div class="t-label">Today</div>
                <div class="t-date">{{ $now->format('d M Y') }}</div>
              </div>
            </div>
            <div class="tl-connector {{ $endDone ? 'done' : '' }}"></div>
            <div class="tl-node {{ $endDone ? 'done' : '' }}">
              <div class="tl-dot"></div>
              <div class="tl-meta">
                <div class="t-label">End</div>
                <div class="t-date {{ !$project->end_date ? 'empty' : '' }}">{{ optional($project->end_date)->format('d M Y') ?? '— pending —' }}</div>
              </div>
            </div>
            <div class="tl-connector"></div>
            <div class="tl-node">
              <div class="tl-dot"></div>
              <div class="tl-meta">
                <div class="t-label">Handover</div>
                <div class="t-date {{ !$project->handover_date ? 'empty' : '' }}">{{ optional($project->handover_date)->format('d M Y') ?? '— pending —' }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Construction Progress --}}
      <div class="card">
        <div class="card-head">
          <h3>Construction Progress</h3>
          @can('project.edit')
            <button wire:click="openProgressModal" type="button" class="edit">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Update
            </button>
          @endcan
        </div>
        <div class="card-body">
          @php
            $overallPct = $project->progress_pct ?? 0;
            $circumference = 226.2;
            $dashOffset = $circumference - ($overallPct / 100) * $circumference;
          @endphp
          <div class="cp-overall">
            <div class="cp-ring">
              <svg width="84" height="84" viewBox="0 0 84 84">
                <circle cx="42" cy="42" r="36" fill="none" stroke="#eef0f2" stroke-width="9" />
                <circle cx="42" cy="42" r="36" fill="none" stroke="#0d2a4a" stroke-width="9" stroke-linecap="round"
                        stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $dashOffset }}" />
              </svg>
              <div class="cp-pct">{{ $overallPct }}%</div>
            </div>
            <div class="cp-overall-info">
              <div class="cp-t">Overall construction progress</div>
              <div class="cp-s">Weighted across all phases.</div>
            </div>
          </div>

          @forelse($project->timelinePhases as $phase)
            @php
              $p = (int)($phase->progress_percentage ?? 0);
              if ($p >= 100)     { $tagClass = 'done';    $tagLabel = 'Done';        $barColor = '#1f6f43'; }
              elseif ($p > 0)    { $tagClass = 'active';  $tagLabel = 'In progress'; $barColor = '#0e63a8'; }
              else               { $tagClass = 'pending'; $tagLabel = 'Pending';     $barColor = '#9aa0a6'; }
            @endphp
            <div class="cp-phase">
              <div class="cp-name"><span class="cp-dot" style="background:{{ $barColor }}"></span>{{ $phase->name }}</div>
              <div class="cp-track"><div class="cp-bar" style="width:{{ $p }}%;background:{{ $barColor }}"></div></div>
              <div class="cp-val">{{ $p }}%</div>
              <div class="cp-status"><span class="cp-tag {{ $tagClass }}">{{ $tagLabel }}</span></div>
            </div>
          @empty
            @can('project.edit')
              <p style="font-size:12px;color:var(--muted-2);font-style:italic;margin-top:8px;">
                No phases added yet. Click <strong>Update</strong> to add construction phases.
              </p>
            @endcan
          @endforelse
        </div>
      </div>

      {{-- Description --}}
      @if($project->description)
      <div class="card">
        <div class="card-head">
          <h3>Description</h3>
        </div>
        <div class="card-body">
          <p class="desc-text">{{ $project->description }}</p>
        </div>
      </div>
      @endif

      {{-- Documents --}}
      <div class="card">
        <div class="card-head">
          <h3>Documents
            @if($documentFiles && count($documentFiles))
              <span style="font-family:'JetBrains Mono',monospace;background:#f0f1f3;color:var(--ink-2);padding:1px 7px;border-radius:999px;font-size:10px;font-weight:600;margin-left:4px;">{{ count($documentFiles) }}</span>
            @endif
          </h3>
          @if($canEdit)
            <a class="edit" href="#" onclick="return false;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Upload
            </a>
          @endif
        </div>
        <div class="card-body" style="padding:10px 12px;">
          @if($canEdit)
            <div style="margin-bottom:12px;">
              <x-media-picker-field field="documents" :value="$documents"
                placeholder="Click to upload files" :multiple="true" type="all"
                label="Manage Documents" required="false" />
              <div style="margin-top:6px;display:flex;gap:8px;">
                <button wire:click="saveDocuments" class="btn" style="padding:6px 12px;font-size:12px;">Save Documents</button>
              </div>
            </div>
          @endif

          <div class="doc-list">
            @forelse($documentFiles as $file)
              @php
                $ext = strtolower($file->extension ?? 'file');
                $iconClass = in_array($ext, ['pdf']) ? 'pdf' : (in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'img' : (in_array($ext, ['dwg','dxf']) ? 'dwg' : 'file'));
              @endphp
              <div class="doc-row">
                <div class="doc-icon {{ $iconClass }}">
                  @if($iconClass === 'pdf')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  @elseif($iconClass === 'img')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                  @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  @endif
                </div>
                <div class="doc-info">
                  <div class="name">{{ $file->name ?? 'Document ' . $loop->iteration }}</div>
                  <div class="meta">{{ strtoupper($ext) }}</div>
                </div>
                <a href="{{ file_path($file->id) }}" download class="doc-dl">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </a>
              </div>
            @empty
              <p style="font-size:12.5px;color:var(--muted-2);font-style:italic;padding:8px 10px;">No documents uploaded yet.</p>
            @endforelse
          </div>
        </div>
      </div>

    </div>{{-- end main-col --}}

    <div class="side-col">

      {{-- Project Team --}}
      <div class="card">
        <div class="card-head"><h3>Project Team</h3></div>
        <div class="card-body">
          @if($project->chiefEngineer)
            <div class="person" style="padding-bottom:12px;border-bottom:1px solid var(--rule-soft);">
              <div class="pav" style="background:#6d28d9;">{{ strtoupper(substr($project->chiefEngineer->name, 0, 1)) }}</div>
              <div class="pinfo">
                <div class="pname">
                  {{ $project->chiefEngineer->name }}
                  <span style="font-size:9.5px;font-weight:600;letter-spacing:0.4px;text-transform:uppercase;color:#6d28d9;background:#f3edfb;border:1px solid #d8c9ee;padding:1px 7px;border-radius:999px;margin-left:4px;vertical-align:1px;">Chief</span>
                </div>
                <div class="prole">Chief Engineer</div>
              </div>
            </div>
          @endif
          @if($project->siteEngineer)
            <div class="person" style="{{ $project->chiefEngineer ? 'padding-top:12px;' : '' }}">
              <div class="pav">{{ strtoupper(substr($project->siteEngineer->name, 0, 1)) }}</div>
              <div class="pinfo">
                <div class="pname">{{ $project->siteEngineer->name }}</div>
                <div class="prole">Senior Site Engineer</div>
              </div>
            </div>
          @endif
          @foreach($project->engineers as $eng)
            @if($eng->id !== $project->chief_engineer_id && $eng->id !== $project->site_engineer_id)
              <div class="person" style="padding-top:8px;">
                <div class="pav">{{ strtoupper(substr($eng->name, 0, 1)) }}</div>
                <div class="pinfo">
                  <div class="pname">{{ $eng->name }}</div>
                  <div class="prole">Engineer</div>
                </div>
              </div>
            @endif
          @endforeach
          @if(!$project->chiefEngineer && !$project->siteEngineer && $project->engineers->isEmpty())
            <p style="font-size:12px;color:var(--muted-2);font-style:italic;">No team assigned.</p>
          @endif
        </div>
      </div>

      {{-- Record --}}
      <div class="card">
        <div class="card-head"><h3>Record</h3></div>
        <div class="card-body">
          <div class="meta-list">
            <div class="meta-row"><span class="k">Project ID</span><span class="v">#{{ $project->id }}</span></div>
            <div class="meta-row"><span class="k">Created</span><span class="v">{{ $project->created_at->format('d M Y') }}</span></div>
            <div class="meta-row"><span class="k">Last updated</span><span class="v">{{ $project->updated_at->format('d M Y') }}</span></div>
            @if($project->createdBy)
              <div class="meta-row"><span class="k">Created by</span><span class="v" style="font-family:inherit;">{{ $project->createdBy->name }}</span></div>
            @endif
          </div>
        </div>
      </div>

      {{-- Project Sections --}}
      <div class="card">
        <div class="card-head"><h3>Project Sections</h3></div>
        <div class="card-body" style="padding:8px 10px;">
          <div class="quick-links">
            <a class="ql" href="{{ route('admin.projects.estimates', $project) }}">
              <span class="qic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 7h6M9 11h6M9 15h4"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg></span>
              <span class="qinfo"><span class="qt">Estimates</span><span class="qd">BOQ budget versions</span></span>
              <span class="qarrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </a>
            <a class="ql" href="{{ route('admin.projects.consumption', $project) }}">
              <span class="qic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7L9 18l-5-5"/><path d="M3 3h18v4H3z"/></svg></span>
              <span class="qinfo"><span class="qt">Consumption</span><span class="qd">Material usage from inventory</span></span>
              <span class="qarrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </a>
            <a class="ql" href="{{ route('admin.projects.expenses', $project) }}">
              <span class="qic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
              <span class="qinfo"><span class="qt">Expenses</span><span class="qd">Labour &amp; other costs</span></span>
              <span class="qarrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </a>
            <a class="ql" href="{{ route('admin.projects.reports', $project) }}">
              <span class="qic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
              <span class="qinfo"><span class="qt">Reports</span><span class="qd">Cost analysis &amp; summaries</span></span>
              <span class="qarrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </a>
          </div>
        </div>
      </div>

    </div>{{-- end side-col --}}
  </div>{{-- end content --}}

  {{-- ===== Construction Progress Modal ===== --}}
  <x-modal wire:model="progressModal" maxWidth="2xl">
    <div class="bg-white rounded-lg overflow-hidden">

      {{-- Header --}}
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Construction Progress</h3>
          <p class="text-sm text-gray-500 mt-0.5">
            Add or update phases. Overall % is auto-calculated as the average.
          </p>
        </div>
        <button wire:click="closeProgressModal" type="button"
          class="rounded-md p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      {{-- Body --}}
      <div class="px-6 py-5 overflow-y-auto max-h-[60vh]">

        {{-- Overall progress preview --}}
        @php
          $phasesList = $phases ?? [];
          $avgPct = count($phasesList) > 0
            ? (int) round(collect($phasesList)->avg('progress_percentage'))
            : ($project->progress_pct ?? 0);
        @endphp
        <div class="flex items-center gap-4 mb-5 p-4 bg-gray-50 rounded-xl border border-gray-200">
          @php
            $circ = 226.2;
            $off  = $circ - ($avgPct / 100) * $circ;
          @endphp
          <div style="position:relative;width:64px;height:64px;flex-shrink:0;">
            <svg width="64" height="64" viewBox="0 0 84 84" style="transform:rotate(-90deg)">
              <circle cx="42" cy="42" r="36" fill="none" stroke="#eef0f2" stroke-width="9"/>
              <circle cx="42" cy="42" r="36" fill="none" stroke="#0d2a4a" stroke-width="9"
                stroke-linecap="round" stroke-dasharray="{{ $circ }}"
                stroke-dashoffset="{{ $off }}"/>
            </svg>
            <div style="position:absolute;inset:0;display:grid;place-items:center;font-size:13px;font-weight:700;color:#14181f;">{{ $avgPct }}%</div>
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-800">Overall Progress Preview</p>
            <p class="text-xs text-gray-500 mt-0.5">Average of all {{ count($phasesList) }} phase(s) below</p>
          </div>
        </div>

        {{-- Global phases error (e.g. empty list) --}}
        @error('phases')
          <div class="mb-3 flex items-center gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-2.5 text-sm text-red-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            {{ $message }}
          </div>
        @enderror

        {{-- Phase rows --}}
        <div class="space-y-3">
          @foreach($phasesList as $i => $phase)
            @php
              $pct      = (int)($phase['progress_percentage'] ?? 0);
              $barColor = $pct >= 100 ? '#1f6f43' : ($pct > 0 ? '#0e63a8' : '#d1d5db');
              $hasError = $errors->has("phases.{$i}.name")
                       || $errors->has("phases.{$i}.progress_percentage")
                       || $errors->has("phases.{$i}.start_date")
                       || $errors->has("phases.{$i}.end_date");
            @endphp
            <div class="rounded-xl p-4 bg-white transition-all
              {{ $hasError ? 'border border-red-300 bg-red-50/30' : 'border border-gray-200' }}">

              {{-- Row header: name + remove --}}
              <div class="flex items-center gap-3 mb-3">
                <div class="flex-1">
                  <x-label value="Phase Name *" class="text-xs" />
                  <x-input wire:model.live="phases.{{ $i }}.name"
                    type="text" class="mt-1 block w-full text-sm {{ $errors->has('phases.'.$i.'.name') ? 'border-red-400 focus:border-red-400 focus:ring-red-300' : '' }}"
                    placeholder="e.g. Foundation, Structure, Finishing…" />
                  @error("phases.{$i}.name")
                    <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                      {{ $message }}
                    </p>
                  @enderror
                </div>
                <button wire:click="removePhase({{ $i }})" type="button"
                  wire:confirm="Remove this phase?"
                  class="mt-5 flex-shrink-0 p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition"
                  title="Remove phase">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                  </svg>
                </button>
              </div>

              {{-- Progress slider + percentage --}}
              <div class="mb-3">
                <div class="flex items-center justify-between mb-1">
                  <x-label value="Progress %" class="text-xs" />
                  <span class="text-xs font-bold font-mono" style="color:{{ $barColor }}">{{ $pct }}%</span>
                </div>
                <input type="range"
                  wire:model.live="phases.{{ $i }}.progress_percentage"
                  min="0" max="100" step="5"
                  class="w-full h-2 rounded-full appearance-none cursor-pointer"
                  style="accent-color:#0d2a4a;" />
                <div class="mt-2 h-2 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all"
                    style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
                </div>
                @error("phases.{$i}.progress_percentage")
                  <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                  </p>
                @enderror
              </div>

              {{-- Optional dates --}}
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <x-label value="Start Date (optional)" class="text-xs" />
                  <x-input wire:model="phases.{{ $i }}.start_date"
                    type="date" class="mt-1 block w-full text-sm {{ $errors->has('phases.'.$i.'.start_date') ? 'border-red-400' : '' }}" />
                  @error("phases.{$i}.start_date")
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                  @enderror
                </div>
                <div>
                  <x-label value="End Date (optional)" class="text-xs" />
                  <x-input wire:model="phases.{{ $i }}.end_date"
                    type="date" class="mt-1 block w-full text-sm {{ $errors->has('phases.'.$i.'.end_date') ? 'border-red-400' : '' }}" />
                  @error("phases.{$i}.end_date")
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                  @enderror
                </div>
              </div>
            </div>
          @endforeach
        </div>

        {{-- Add phase button --}}
        <button wire:click="addPhase" type="button"
          class="mt-4 w-full flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-200 py-3 text-sm font-medium text-gray-500 hover:border-[#0d2a4a] hover:text-[#0d2a4a] transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          Add Phase
        </button>
      </div>

      {{-- Footer --}}
      <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">
        <p class="text-xs text-gray-400">Overall % auto-saves as average of all phases</p>
        <div class="flex gap-3">
          <button wire:click="closeProgressModal" type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
            Cancel
          </button>
          <button wire:click="saveProgress" type="button"
            wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
            class="inline-flex items-center gap-2 rounded-lg bg-[#0d2a4a] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#0a2240] transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            <span wire:loading.remove wire:target="saveProgress">Save Progress</span>
            <span wire:loading wire:target="saveProgress">Saving…</span>
          </button>
        </div>
      </div>

    </div>
  </x-modal>

  {{-- ===== Edit Project Modal ===== --}}
  <x-modal wire:model="editModal" maxWidth="2xl">
    <div class="bg-white rounded-lg overflow-hidden">

      {{-- Modal header --}}
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Edit Project</h3>
          <p class="text-sm text-gray-500 mt-0.5">Update project details</p>
        </div>
        <button wire:click="closeEditModal" type="button"
          class="rounded-md p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      {{-- Modal body --}}
      <div class="px-6 py-5 overflow-y-auto max-h-[72vh]">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          {{-- Name --}}
          <div class="sm:col-span-2">
            <x-label for="edit_name" value="Project Name *" />
            <x-input wire:model="edit_name" id="edit_name" type="text" class="mt-1 block w-full"
              placeholder="Enter project name" />
            <x-input-error for="edit_name" class="mt-1" />
          </div>

          {{-- Code --}}
          <div>
            <x-label for="edit_code" value="Project Code" />
            <x-input wire:model="edit_code" id="edit_code" type="text" class="mt-1 block w-full"
              placeholder="e.g. SUDP001" />
            <x-input-error for="edit_code" class="mt-1" />
          </div>

          {{-- Progress --}}
          <div>
            <x-label for="edit_progress_pct" value="Construction Progress (%)" />
            <x-input wire:model="edit_progress_pct" id="edit_progress_pct" type="number"
              min="0" max="100" class="mt-1 block w-full" placeholder="0–100" />
            <x-input-error for="edit_progress_pct" class="mt-1" />
          </div>

          {{-- Project Type (multiple) --}}
          <div class="sm:col-span-2">
            <x-label value="Project Type * (select one or more)" />
            <div class="mt-2 flex flex-wrap gap-3">
              @foreach(\App\Enums\Project\Type::cases() as $type)
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                  <input type="checkbox"
                    wire:model="edit_project_type"
                    value="{{ $type->value }}"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">{{ $type->label() }}</span>
                </label>
              @endforeach
            </div>
            <x-input-error for="edit_project_type" class="mt-1" />
          </div>

          {{-- Status --}}
          <div>
            <x-label for="edit_status" value="Status *" />
            <select wire:model="edit_status" id="edit_status"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
              <option value="">Select Status</option>
              @foreach(\App\Enums\Project\Status::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
              @endforeach
            </select>
            <x-input-error for="edit_status" class="mt-1" />
          </div>

          {{-- Location --}}
          <div class="sm:col-span-2">
            <x-label for="edit_location" value="Location *" />
            <x-input wire:model="edit_location" id="edit_location" type="text"
              class="mt-1 block w-full" placeholder="Full address" />
            <x-input-error for="edit_location" class="mt-1" />
          </div>

          {{-- Land Area --}}
          <div>
            <x-label for="edit_land_area" value="Land Area (sft)" />
            <x-input wire:model="edit_land_area" id="edit_land_area" type="number"
              step="0.01" class="mt-1 block w-full" placeholder="e.g. 12500" />
            <x-input-error for="edit_land_area" class="mt-1" />
          </div>

          {{-- Building Area --}}
          <div>
            <x-label for="edit_building_area" value="Building Area (sft)" />
            <x-input wire:model="edit_building_area" id="edit_building_area" type="number"
              step="0.01" class="mt-1 block w-full" placeholder="e.g. 96400" />
            <x-input-error for="edit_building_area" class="mt-1" />
          </div>

          {{-- Start Date --}}
          <div>
            <x-label for="edit_start_date" value="Start Date *" />
            <x-input wire:model="edit_start_date" id="edit_start_date" type="date"
              class="mt-1 block w-full" />
            <x-input-error for="edit_start_date" class="mt-1" />
          </div>

          {{-- End Date --}}
          <div>
            <x-label for="edit_end_date" value="End Date *" />
            <x-input wire:model="edit_end_date" id="edit_end_date" type="date"
              class="mt-1 block w-full" />
            <x-input-error for="edit_end_date" class="mt-1" />
          </div>

          {{-- Handover Date --}}
          <div>
            <x-label for="edit_handover_date" value="Handover Date" />
            <x-input wire:model="edit_handover_date" id="edit_handover_date" type="date"
              class="mt-1 block w-full" />
            <x-input-error for="edit_handover_date" class="mt-1" />
          </div>

          {{-- Budget --}}
          <div>
            <x-label for="edit_budget" value="Budget (BDT)" />
            <x-input wire:model="edit_budget" id="edit_budget" type="number"
              step="0.01" class="mt-1 block w-full" placeholder="e.g. 42000000" />
            <x-input-error for="edit_budget" class="mt-1" />
          </div>

          {{-- Chief Engineer --}}
          <div>
            <x-label for="edit_chief_engineer_id" value="Chief Engineer" />
            <select wire:model="edit_chief_engineer_id" id="edit_chief_engineer_id"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
              <option value="">— None —</option>
              @foreach($engineers as $eng)
                <option value="{{ $eng->id }}">{{ $eng->name }}</option>
              @endforeach
            </select>
            <x-input-error for="edit_chief_engineer_id" class="mt-1" />
          </div>

          {{-- Site Engineer --}}
          <div>
            <x-label for="edit_site_engineer_id" value="Site Engineer" />
            <select wire:model="edit_site_engineer_id" id="edit_site_engineer_id"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
              <option value="">— None —</option>
              @foreach($engineers as $eng)
                <option value="{{ $eng->id }}">{{ $eng->name }}</option>
              @endforeach
            </select>
            <x-input-error for="edit_site_engineer_id" class="mt-1" />
          </div>

          {{-- Description --}}
          <div class="sm:col-span-2">
            <x-label for="edit_description" value="Description" />
            <textarea wire:model="edit_description" id="edit_description" rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
              placeholder="Project description (optional)"></textarea>
            <x-input-error for="edit_description" class="mt-1" />
          </div>

          {{-- Cover Image --}}
          <div class="sm:col-span-2">
            <x-media-picker-field field="edit_image" :value="$edit_image"
              placeholder="Click to upload cover image" :multiple="false"
              type="image" label="Cover Image" required="false" />
          </div>

        </div>
      </div>

      {{-- Modal footer --}}
      <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
        <button wire:click="closeEditModal" type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
          Cancel
        </button>
        <button wire:click="saveEdit" type="button"
          wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
          class="inline-flex items-center gap-2 rounded-lg bg-[#0d2a4a] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#0a2240] transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
          </svg>
          <span wire:loading.remove wire:target="saveEdit">Save Changes</span>
          <span wire:loading wire:target="saveEdit">Saving…</span>
        </button>
      </div>

    </div>
  </x-modal>

</div>
