{{--
  Project hero + shared CSS for all project tab pages.
  Props: $project (Project model)
--}}
<style>
:root {
  --ink:#14181f; --ink-2:#2a2f3a; --ink-3:#4a5160;
  --muted:#6b7280; --muted-2:#9aa0a6;
  --rule:#e4e4e7; --rule-soft:#ececec;
  --paper:#ffffff; --canvas:#f6f6f7;
  --accent:#0d2a4a; --accent-soft:#eaf0f8;
  --warn:#a16207; --warn-bg:#fef9e7; --warn-bd:#f3e3a8;
  --ok:#1f6f43; --ok-bg:#e9f4ee; --ok-bd:#bfddc8;
  --info:#0e63a8; --info-bg:#e9f2fb; --info-bd:#c2dcf3;
  --danger:#8a1212; --danger-bg:#fbeaea; --danger-bd:#f1c2c2;
  --teal:#0e7490; --teal-bg:#e6f5f8;
  --labour:#0e7490; --labour-bg:#e6f5f8;
  --other:#a16207; --other-bg:#fef9e7;
  --material:#0d2a4a; --material-bg:#eaf0f8;
  --overhead:#a16207; --overhead-bg:#fef9e7;
  --indirect:#6d28d9; --indirect-bg:#f3edfb;
}
@import url('https://fonts.googleapis.com/css2?family=Instrument+Serif&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap');

/* ---- Reset for project pages ---- */
.prj-page * { box-sizing:border-box; }
.prj-page { font-family:"Inter",system-ui,-apple-system,sans-serif; font-size:13px; line-height:1.45; -webkit-font-smoothing:antialiased; color:var(--ink); }

/* ---- Hero ---- */
.hero { display:grid; grid-template-columns:300px 1fr; gap:0; background:var(--paper); border:1px solid var(--rule); border-radius:12px; overflow:hidden; margin-bottom:18px; }
.hero-cover { position:relative; background:linear-gradient(135deg,#1a3a5c 0%,#0d2a4a 100%); min-height:220px; display:grid; place-items:center; overflow:hidden; }
.hero-cover .ph { text-align:center; color:rgba(255,255,255,0.5); }
.hero-cover .ph svg { width:42px; height:42px; }
.hero-cover .ph div { font-size:11px; margin-top:8px; letter-spacing:0.5px; }
.hero-cover .cover-tag { position:absolute; bottom:10px; left:12px; background:rgba(0,0,0,0.35); color:#fff; font-size:10px; padding:3px 9px; border-radius:999px; backdrop-filter:blur(4px); letter-spacing:0.3px; }
.hero-cover .cover-img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
.hero-body { padding:22px 26px; display:flex; flex-direction:column; }
.hero-top { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
.hero-code { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:11px; font-weight:600; color:var(--accent); background:var(--accent-soft); padding:3px 9px; border-radius:5px; display:inline-block; letter-spacing:0.5px; }
.hero h1 { font-family:"Instrument Serif",Georgia,serif; font-weight:400; font-size:32px; margin:10px 0 6px; line-height:1.1; letter-spacing:0.2px; }
.hero .types { display:flex; gap:6px; flex-wrap:wrap; margin-top:4px; }
.type-chip { font-size:10.5px; padding:3px 10px; border-radius:999px; background:#f0f1f3; color:var(--ink-2); font-weight:500; letter-spacing:0.2px; border:1px solid var(--rule); }
.hero-actions { display:flex; gap:8px; }
.btn { font-family:inherit; font-size:12.5px; font-weight:500; padding:8px 14px; border-radius:6px; border:1px solid var(--rule); background:var(--paper); color:var(--ink-2); cursor:pointer; display:inline-flex; align-items:center; gap:6px; line-height:1; text-decoration:none; }
.btn:hover { background:#fafafb; }
.btn.primary { background:var(--ink); color:#fff; border-color:var(--ink); }
.btn.primary:hover { background:#000; }
.btn.ok { background:var(--ok); color:#fff; border-color:var(--ok); }
.btn.ok:hover { background:#185933; }
.btn .ic { width:13px; height:13px; }

/* hero progress */
.hero-progress { margin-top:auto; padding-top:18px; }
.hp-label { display:flex; justify-content:space-between; font-size:10.5px; color:var(--muted); margin-bottom:6px; letter-spacing:0.3px; }
.hp-label strong { color:var(--ink); font-weight:600; }
.hp-bar { height:7px; background:#eef0f2; border-radius:999px; overflow:hidden; }
.hp-fill { height:100%; background:var(--accent); border-radius:999px; }

/* status badge */
.status-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 11px; border-radius:999px; font-size:11px; font-weight:600; letter-spacing:0.3px; border:1px solid; }
.status-badge .dot { width:7px; height:7px; border-radius:50%; }
.status-badge.running   { background:var(--info-bg); border-color:var(--info-bd); color:var(--info); }
.status-badge.upcoming  { background:var(--warn-bg); border-color:var(--warn-bd); color:var(--warn); }
.status-badge.completed { background:var(--ok-bg); border-color:var(--ok-bd); color:var(--ok); }
.status-badge.on_hold   { background:#f0f0f1; border-color:var(--rule); color:var(--muted); }
.status-badge.cancelled { background:var(--danger-bg); border-color:var(--danger-bd); color:var(--danger); }
.status-badge.running .dot   { background:var(--info); }
.status-badge.upcoming .dot  { background:var(--warn); }
.status-badge.completed .dot { background:var(--ok); }
.status-badge.on_hold .dot   { background:var(--muted); }
.status-badge.cancelled .dot { background:var(--danger); }

/* nav tabs */
.nav-tabs { display:flex; gap:4px; margin-bottom:18px; border-bottom:1px solid var(--rule); padding:0 2px; overflow-x:auto; }
.nav-tab { background:transparent; border:0; font-family:inherit; font-size:13px; font-weight:500; color:var(--muted); padding:10px 16px 12px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; position:relative; border-bottom:2px solid transparent; margin-bottom:-1px; text-decoration:none; white-space:nowrap; }
.nav-tab:hover { color:var(--ink-2); }
.nav-tab.active { color:var(--ink); border-bottom-color:var(--ink); font-weight:600; }
.nav-tab .ic { width:15px; height:15px; }
.nav-tab .pill { font-family:"JetBrains Mono",ui-monospace,monospace; font-size:10.5px; padding:1px 7px; border-radius:999px; background:#f0f0f1; color:var(--ink-2); font-weight:600; letter-spacing:0.3px; }
.nav-tab.active .pill { background:var(--accent); color:#fff; }

/* toolbar shared */
.c-toolbar { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:16px; }
.c-title-wrap { display:flex; align-items:baseline; gap:12px; }
.c-title-wrap h2 { font-family:"Instrument Serif",Georgia,serif; font-weight:400; font-size:24px; margin:0; }
.c-title-wrap .note { font-size:12px; color:var(--muted); }

/* select */
.select { font-family:inherit; font-size:12.5px; padding:7px 26px 7px 10px; border:1px solid var(--rule); border-radius:6px; background:var(--paper) url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>") no-repeat right 10px center; appearance:none; color:var(--ink-2); cursor:pointer; }

/* cost-type badges */
.ct-badge { display:inline-flex; align-items:center; gap:5px; padding:2px 8px; border-radius:999px; font-size:10px; font-weight:600; letter-spacing:0.3px; }
.ct-badge .d { width:5px; height:5px; border-radius:50%; }
.ct-badge.material { background:var(--material-bg); color:var(--material); }
.ct-badge.labour   { background:var(--labour-bg); color:var(--labour); }
.ct-badge.overhead { background:var(--overhead-bg); color:var(--overhead); }
.ct-badge.indirect { background:var(--indirect-bg); color:var(--indirect); }
.ct-badge.other    { background:var(--other-bg); color:var(--other); }
.ct-badge.material .d { background:var(--material); }
.ct-badge.labour .d   { background:var(--labour); }
.ct-badge.overhead .d { background:var(--overhead); }
.ct-badge.indirect .d { background:var(--indirect); }
.ct-badge.other .d    { background:var(--other); }
</style>

@php
  $statusClass = match($project->status?->value) {
      'ongoing'   => 'running',
      'on_hold'   => 'on_hold',
      'completed' => 'completed',
      'cancelled' => 'cancelled',
      default     => 'upcoming',
  };
  $pct = $project->progress_pct ?? 0;
  $circumference = 226.2;
  $offset = $circumference - ($pct / 100) * $circumference;
@endphp

<div class="hero">
  <div class="hero-cover">
    @if($project->image)
      <img src="{{ file_path($project->image) }}" alt="{{ $project->name }}" class="cover-img">
    @endif
    <div class="ph">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
      <div>Cover image</div>
    </div>
    @if($project->location)
      <span class="cover-tag">{{ Str::limit($project->location, 40) }}</span>
    @endif
  </div>
  <div class="hero-body">
    <div class="hero-top">
      <div>
        @if($project->code)
          <span class="hero-code">{{ $project->code }}</span>
        @endif
        <h1>{{ $project->name }}</h1>
        <div class="types">
          @foreach($project->typeLabels() as $label)
            <span class="type-chip">{{ $label }}</span>
          @endforeach
        </div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:12px;">
        <span class="status-badge {{ $statusClass }}">
          <span class="dot"></span>{{ $project->status?->label() }}
        </span>
        <div class="hero-actions">
          {{-- Export PDF — shown only on the Details tab --}}
          @if(isset($showEditButton) && $showEditButton)
            <a href="{{ route('admin.projects.pdf.details', $project) }}" target="_blank" class="btn primary">
              <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Export PDF
            </a>
          @endif
          @can('project.edit')
            @if(isset($showEditButton) && $showEditButton)
              <button wire:click="openEditModal" type="button" class="btn">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
              </button>
            @else
              <a href="{{ route('admin.projects.create', ['project_id' => $project->id]) }}" class="btn">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
              </a>
            @endif
          @endcan
        </div>
      </div>
    </div>
    <div class="hero-progress">
      <div class="hp-label"><span>Construction progress</span><strong>{{ $pct }}% complete</strong></div>
      <div class="hp-bar"><div class="hp-fill" style="width:{{ $pct }}%"></div></div>
    </div>
  </div>
</div>
