<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Project Details — {{ $project->name }}</title>
<style>
  /* Base */
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: "DejaVu Sans", Arial, sans-serif;
    font-size: 11px;
    color: #14181f;
    line-height: 1.5;
    background: #f6f6f7;
  }

  /* Page wrapper */
  .page { padding: 28px 32px 40px; }

  /* Header / cover */
  .doc-header {
    background: #0d2a4a;
    color: #fff;
    padding: 22px 26px;
    border-radius: 8px;
    margin-bottom: 20px;
  }
  .doc-header .company {
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.6);
    margin-bottom: 6px;
  }
  .doc-header h1 {
    font-size: 22px;
    font-weight: bold;
    color: #fff;
    margin-bottom: 4px;
  }
  .doc-header .code-chip {
    display: inline-block;
    font-size: 10px;
    background: rgba(255,255,255,0.15);
    color: #fff;
    padding: 2px 8px;
    border-radius: 4px;
    margin-bottom: 8px;
    font-family: "Courier New", monospace;
  }
  .doc-header .meta-row {
    font-size: 10px;
    color: rgba(255,255,255,0.7);
  }
  .doc-header .meta-row span { margin-right: 20px; }

  /* Status badge */
  .status-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 0.4px;
    text-transform: uppercase;
  }
  .status-running   { background: #e9f2fb; color: #0e63a8; border: 1px solid #c2dcf3; }
  .status-upcoming  { background: #fef9e7; color: #a16207; border: 1px solid #f3e3a8; }
  .status-completed { background: #e9f4ee; color: #1f6f43; border: 1px solid #bfddc8; }
  .status-on_hold   { background: #f0f0f1; color: #6b7280; border: 1px solid #e4e4e7; }
  .status-cancelled { background: #fbeaea; color: #8a1212; border: 1px solid #f1c2c2; }

  /* Progress bar */
  .progress-wrap { margin-top: 12px; }
  .progress-label { font-size: 9px; color: rgba(255,255,255,0.6); margin-bottom: 4px; }
  .progress-track { background: rgba(255,255,255,0.2); height: 6px; border-radius: 999px; }
  .progress-fill  { background: #fff; height: 6px; border-radius: 999px; }

  /* KPI strip */
  .kpi-strip { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
  .kpi-strip td { width: 25%; padding: 12px 14px; background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; vertical-align: top; }
  .kpi-strip td:first-child { border-left: 4px solid #0d2a4a; }
  .kpi-label { font-size: 8.5px; letter-spacing: 0.8px; text-transform: uppercase; color: #6b7280; font-weight: bold; margin-bottom: 4px; }
  .kpi-value { font-size: 16px; font-weight: bold; color: #14181f; }
  .kpi-meta  { font-size: 8.5px; color: #6b7280; margin-top: 3px; }

  /* Section card */
  .card { background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; margin-bottom: 14px; overflow: hidden; }
  .card-head {
    background: #fafafb;
    border-bottom: 1px solid #e4e4e7;
    padding: 8px 14px;
    font-size: 11px;
    font-weight: bold;
    color: #14181f;
  }
  .card-body { padding: 12px 14px; }

  /* Definition grid as table */
  .def-table { width: 100%; border-collapse: collapse; }
  .def-table td { padding: 5px 8px 5px 0; vertical-align: top; width: 50%; }
  .def-table td.full { width: 100%; }
  .def-dt { font-size: 8.5px; letter-spacing: 0.6px; text-transform: uppercase; color: #6b7280; font-weight: bold; margin-bottom: 2px; display: block; }
  .def-dd { font-size: 11px; color: #14181f; font-weight: 500; }

  /* Team */
  .team-row { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #ececec; }
  .team-row:last-child { border-bottom: none; margin-bottom: 0; }
  .team-role { font-size: 8.5px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
  .team-name { font-size: 11.5px; font-weight: bold; color: #14181f; }
  .chief-chip {
    display: inline-block;
    font-size: 7.5px;
    font-weight: bold;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    color: #6d28d9;
    background: #f3edfb;
    border: 1px solid #d8c9ee;
    padding: 1px 6px;
    border-radius: 999px;
    margin-left: 5px;
  }

  /* Construction progress */
  .cp-row { margin-bottom: 8px; }
  .cp-name { font-size: 10.5px; font-weight: 500; color: #14181f; margin-bottom: 3px; }
  .cp-track { background: #eef0f2; height: 7px; border-radius: 999px; }
  .cp-bar   { height: 7px; border-radius: 999px; }
  .cp-meta  { font-size: 9px; color: #6b7280; margin-top: 2px; display: flex; justify-content: space-between; }

  /* Type chips */
  .type-chip {
    display: inline-block;
    font-size: 8.5px;
    padding: 2px 8px;
    border-radius: 999px;
    background: #f0f1f3;
    color: #2a2f3a;
    border: 1px solid #e4e4e7;
    margin-right: 4px;
  }

  /* Two-column layout */
  .two-col { width: 100%; border-collapse: collapse; }
  .two-col td.main { width: 62%; padding-right: 12px; vertical-align: top; }
  .two-col td.side { width: 38%; vertical-align: top; }

  /* Footer */
  .pdf-footer {
    text-align: center;
    font-size: 8.5px;
    color: #9aa0a6;
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px solid #e4e4e7;
  }
</style>
</head>
<body>
<div class="page">

  {{-- ===== Document Header ===== --}}
  <div class="doc-header">
    <div class="company">Star Unity Development Ltd — Project Report</div>
    @if($project->code)
      <div class="code-chip">{{ $project->code }}</div>
    @endif
    <h1>{{ $project->name }}</h1>
    <div class="meta-row">
      @foreach($project->typeLabels() as $label)
        <span class="type-chip" style="background:rgba(255,255,255,0.15);color:#fff;border-color:rgba(255,255,255,0.3);">{{ $label }}</span>
      @endforeach
      &nbsp;&nbsp;
      @php
        $sc = match($project->status?->value) { 'ongoing'=>'running','on_hold'=>'on_hold','completed'=>'completed','cancelled'=>'cancelled',default=>'upcoming' };
      @endphp
      <span class="status-badge status-{{ $sc }}">{{ $project->status?->label() }}</span>
      <span style="margin-left:16px; font-size:9px; color:rgba(255,255,255,0.6);">Generated: {{ now()->format('d M Y, H:i') }}</span>
    </div>
    <div class="progress-wrap">
      <div class="progress-label">Construction Progress — {{ $project->progress_pct ?? 0 }}% complete</div>
      <div class="progress-track">
        <div class="progress-fill" style="width:{{ $project->progress_pct ?? 0 }}%"></div>
      </div>
    </div>
  </div>

  {{-- ===== KPI Strip ===== --}}
  <table class="kpi-strip" cellspacing="4" cellpadding="0">
    <tr>
      <td>
        <div class="kpi-label">Budget</div>
        <div class="kpi-value">{{ $project->budget ? 'BDT ' . number_format($project->budget, 2) : '—' }}</div>
        <div class="kpi-meta">Approved project budget</div>
      </td>
      <td>
        <div class="kpi-label">Spent to Date</div>
        <div class="kpi-value">BDT {{ number_format($totalSpent, 2) }}</div>
        @if($project->budget && $project->budget > 0)
          <div class="kpi-meta">{{ round(($totalSpent / $project->budget) * 100, 1) }}% of budget used</div>
        @endif
      </td>
      <td>
        <div class="kpi-label">Remaining</div>
        <div class="kpi-value" style="color:{{ $remaining >= 0 ? '#1f6f43' : '#8a1212' }}">BDT {{ number_format(abs($remaining), 2) }}</div>
        <div class="kpi-meta">{{ $remaining >= 0 ? 'Available' : 'Over budget' }}</div>
      </td>
      <td>
        <div class="kpi-label">Days to Handover</div>
        <div class="kpi-value">{{ $daysLeft ?? '—' }}</div>
        <div class="kpi-meta">@if($project->handover_date) Target: {{ $project->handover_date->format('d M Y') }} @else No handover date @endif</div>
      </td>
    </tr>
  </table>

  {{-- ===== Two-column content ===== --}}
  <table class="two-col" cellspacing="0" cellpadding="0">
    <tr>
      {{-- Main Column --}}
      <td class="main">

        {{-- Basic Info --}}
        <div class="card">
          <div class="card-head">Basic Information</div>
          <div class="card-body">
            <table class="def-table">
              <tr>
                <td>
                  <span class="def-dt">Project Code</span>
                  <span class="def-dd" style="font-family:'Courier New',monospace;">{{ $project->code ?? '—' }}</span>
                </td>
                <td>
                  <span class="def-dt">Project Name</span>
                  <span class="def-dd">{{ $project->name }}</span>
                </td>
              </tr>
              <tr>
                <td>
                  <span class="def-dt">Project Types</span>
                  <span class="def-dd">
                    @foreach($project->typeLabels() as $label)
                      <span class="type-chip">{{ $label }}</span>
                    @endforeach
                    @if(empty($project->typeLabels())) — @endif
                  </span>
                </td>
                <td>
                  <span class="def-dt">Status</span>
                  <span class="def-dd">
                    <span class="status-badge status-{{ $sc }}">{{ $project->status?->label() }}</span>
                  </span>
                </td>
              </tr>
              <tr>
                <td>
                  <span class="def-dt">Budget</span>
                  <span class="def-dd">{{ $project->budget ? 'BDT ' . number_format($project->budget, 2) : '—' }}</span>
                </td>
                <td>
                  <span class="def-dt">Progress</span>
                  <span class="def-dd">{{ $project->progress_pct ?? 0 }}%</span>
                </td>
              </tr>
            </table>
          </div>
        </div>

        {{-- Timeline --}}
        <div class="card">
          <div class="card-head">Timeline</div>
          <div class="card-body">
            <table class="def-table">
              <tr>
                <td>
                  <span class="def-dt">Start Date</span>
                  <span class="def-dd" style="font-family:'Courier New',monospace;">{{ optional($project->start_date)->format('d M Y') ?? '—' }}</span>
                </td>
                <td>
                  <span class="def-dt">End Date</span>
                  <span class="def-dd" style="font-family:'Courier New',monospace;">{{ optional($project->end_date)->format('d M Y') ?? '—' }}</span>
                </td>
              </tr>
              <tr>
                <td>
                  <span class="def-dt">Handover Date</span>
                  <span class="def-dd" style="font-family:'Courier New',monospace;">{{ optional($project->handover_date)->format('d M Y') ?? '— pending —' }}</span>
                </td>
                <td>
                  <span class="def-dt">Days to Handover</span>
                  <span class="def-dd">{{ $daysLeft !== null ? $daysLeft . ' days' : '—' }}</span>
                </td>
              </tr>
            </table>
          </div>
        </div>

        {{-- Location & Area --}}
        <div class="card">
          <div class="card-head">Location &amp; Area</div>
          <div class="card-body">
            <table class="def-table">
              <tr>
                <td class="full" colspan="2" style="padding-bottom:8px;">
                  <span class="def-dt">Address</span>
                  <span class="def-dd">{{ $project->location ?? '—' }}</span>
                </td>
              </tr>
              <tr>
                <td>
                  <span class="def-dt">Land Area</span>
                  <span class="def-dd">{{ $project->land_area ? number_format($project->land_area, 2) . ' sft' : '—' }}</span>
                </td>
                <td>
                  <span class="def-dt">Building Area</span>
                  <span class="def-dd">{{ $project->building_area ? number_format($project->building_area, 2) . ' sft' : '—' }}</span>
                </td>
              </tr>
            </table>
          </div>
        </div>

        {{-- Construction Progress --}}
        @if($project->timelinePhases->isNotEmpty())
        <div class="card">
          <div class="card-head">Construction Progress</div>
          <div class="card-body">
            @foreach($project->timelinePhases as $phase)
              @php
                $p = $phase->progress_percentage ?? 0;
                if ($p >= 100) { $barColor = '#1f6f43'; $tag = 'Done'; }
                elseif ($p > 0) { $barColor = '#0e63a8'; $tag = 'In Progress'; }
                else { $barColor = '#9aa0a6'; $tag = 'Pending'; }
              @endphp
              <div class="cp-row">
                <div class="cp-name">{{ $phase->name }}</div>
                <div class="cp-track">
                  <div class="cp-bar" style="width:{{ $p }}%; background:{{ $barColor }}"></div>
                </div>
                <table style="width:100%;border-collapse:collapse;">
                  <tr>
                    <td style="font-size:9px;color:#6b7280;">{{ $tag }}</td>
                    <td style="font-size:9px;color:#14181f;font-weight:bold;text-align:right;font-family:'Courier New',monospace;">{{ $p }}%</td>
                  </tr>
                </table>
              </div>
            @endforeach
          </div>
        </div>
        @endif

        {{-- Description --}}
        @if($project->description)
        <div class="card">
          <div class="card-head">Description</div>
          <div class="card-body">
            <p style="font-size:11px;color:#2a2f3a;line-height:1.6;">{{ $project->description }}</p>
          </div>
        </div>
        @endif

      </td>

      {{-- Side Column --}}
      <td class="side">

        {{-- Project Team --}}
        <div class="card">
          <div class="card-head">Project Team</div>
          <div class="card-body">
            @if($project->chiefEngineer)
              <div class="team-row">
                <div class="team-name">
                  {{ $project->chiefEngineer->name }}
                  <span class="chief-chip">Chief</span>
                </div>
                <div class="team-role">Chief Engineer</div>
              </div>
            @endif
            @if($project->siteEngineer)
              <div class="team-row">
                <div class="team-name">{{ $project->siteEngineer->name }}</div>
                <div class="team-role">Senior Site Engineer</div>
              </div>
            @endif
            @foreach($project->engineers as $eng)
              @if($eng->id !== $project->chief_engineer_id && $eng->id !== $project->site_engineer_id)
                <div class="team-row">
                  <div class="team-name">{{ $eng->name }}</div>
                  <div class="team-role">Engineer</div>
                </div>
              @endif
            @endforeach
            @if(!$project->chiefEngineer && !$project->siteEngineer && $project->engineers->isEmpty())
              <p style="font-size:10px;color:#9aa0a6;font-style:italic;">No team assigned.</p>
            @endif
          </div>
        </div>

        {{-- Record --}}
        <div class="card">
          <div class="card-head">Record</div>
          <div class="card-body">
            <table style="width:100%;border-collapse:collapse;">
              <tr>
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Project ID</td>
                <td style="font-size:9.5px;color:#14181f;font-weight:500;text-align:right;font-family:'Courier New',monospace;padding:4px 0;">#{{ $project->id }}</td>
              </tr>
              <tr style="border-top:1px solid #ececec;">
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Created</td>
                <td style="font-size:9.5px;color:#14181f;font-weight:500;text-align:right;font-family:'Courier New',monospace;padding:4px 0;">{{ $project->created_at->format('d M Y') }}</td>
              </tr>
              <tr style="border-top:1px solid #ececec;">
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Last Updated</td>
                <td style="font-size:9.5px;color:#14181f;font-weight:500;text-align:right;font-family:'Courier New',monospace;padding:4px 0;">{{ $project->updated_at->format('d M Y') }}</td>
              </tr>
              @if($project->createdBy)
              <tr style="border-top:1px solid #ececec;">
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Created by</td>
                <td style="font-size:9.5px;color:#14181f;font-weight:500;text-align:right;padding:4px 0;">{{ $project->createdBy->name }}</td>
              </tr>
              @endif
            </table>
          </div>
        </div>

        {{-- Project Stats --}}
        <div class="card">
          <div class="card-head">Project Stats</div>
          <div class="card-body">
            @php
              $floorsCount    = $project->floors->count();
              $unitsCount     = $project->units->count();
              $availableCount = $project->units->where('availability_status', 'available')->count();
              $soldCount      = $project->units->where('availability_status', 'sold')->count();
            @endphp
            <table style="width:100%;border-collapse:collapse;">
              <tr>
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Floors</td>
                <td style="font-size:14px;font-weight:bold;color:#0e63a8;text-align:right;padding:4px 0;">{{ $floorsCount }}</td>
              </tr>
              <tr style="border-top:1px solid #ececec;">
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Total Units</td>
                <td style="font-size:14px;font-weight:bold;color:#0e63a8;text-align:right;padding:4px 0;">{{ $unitsCount }}</td>
              </tr>
              <tr style="border-top:1px solid #ececec;">
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Available</td>
                <td style="font-size:14px;font-weight:bold;color:#1f6f43;text-align:right;padding:4px 0;">{{ $availableCount }}</td>
              </tr>
              <tr style="border-top:1px solid #ececec;">
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Sold</td>
                <td style="font-size:14px;font-weight:bold;color:#a16207;text-align:right;padding:4px 0;">{{ $soldCount }}</td>
              </tr>
            </table>
          </div>
        </div>

      </td>
    </tr>
  </table>

  {{-- Footer --}}
  <div class="pdf-footer">
    Star Unity Development Ltd &nbsp;·&nbsp; Project Details Report &nbsp;·&nbsp; {{ $project->code ?? 'N/A' }} &nbsp;·&nbsp; Generated {{ now()->format('d M Y \a\t H:i') }}
  </div>

</div>
</body>
</html>
