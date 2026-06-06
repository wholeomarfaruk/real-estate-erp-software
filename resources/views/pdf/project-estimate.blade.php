<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>{{ $estimate->estimate_no ?? ('EST-V' . $estimate->version) }} — {{ $project->name }}</title>
<style>
  /* Base */
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: "DejaVu Sans", Arial, sans-serif;
    font-size: 11px;
    color: #14181f;
    line-height: 1.5;
  }

  /* Page wrapper */
  .page { padding: 28px 32px 40px; }

  /* Header */
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
  .doc-header .meta-row span { margin-right: 20px; display: inline-block; margin-bottom: 4px; }

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
  .status-draft { background: #f0f0f1; color: #6b7280; border: 1px solid #e4e4e7; }
  .status-submitted { background: #fef9e7; color: #a16207; border: 1px solid #f3e3a8; }
  .status-approved { background: #e9f4ee; color: #1f6f43; border: 1px solid #bfddc8; }
  .status-rejected { background: #fbeaea; color: #8a1212; border: 1px solid #f1c2c2; }

  /* KPI strip */
  .kpi-strip { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
  .kpi-strip td { width: 25%; padding: 12px 14px; background: #fff; border: 1px solid #e4e4e7; border-radius: 8px; vertical-align: top; }
  .kpi-strip td:first-child { border-left: 4px solid #0d2a4a; }
  .kpi-label { font-size: 8.5px; letter-spacing: 0.8px; text-transform: uppercase; color: #6b7280; font-weight: bold; margin-bottom: 4px; }
  .kpi-value { font-size: 16px; font-weight: bold; color: #14181f; }
  .kpi-meta  { font-size: 8.5px; color: #6b7280; margin-top: 3px; }

  /* Card */
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

  /* Definition grid */
  .def-table { width: 100%; border-collapse: collapse; }
  .def-table td { padding: 5px 8px 5px 0; vertical-align: top; width: 50%; }
  .def-table td.full { width: 100%; }
  .def-dt { font-size: 8.5px; letter-spacing: 0.6px; text-transform: uppercase; color: #6b7280; font-weight: bold; margin-bottom: 2px; display: block; }
  .def-dd { font-size: 11px; color: #14181f; font-weight: 500; }

  /* BOQ table */
  .boq-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  .boq-table thead th { text-align: left; font-size: 9.5px; letter-spacing: 0.7px; text-transform: uppercase; color: #6b7280; font-weight: bold; padding: 9px 14px; background: #fafafb; border-bottom: 1px solid #e4e4e7; }
  .boq-table th.right { text-align: right; }
  .boq-table tbody td { padding: 10px 14px; font-size: 11px; color: #14181f; border-bottom: 1px solid #ececec; vertical-align: middle; }
  .boq-table td.right { text-align: right; font-family: "Courier New", monospace; }
  .boq-table td.mono { font-family: "Courier New", monospace; }

  .phase-row td { background: #f4f6f8; padding: 7px 14px; font-size: 9.5px; letter-spacing: 0.8px; text-transform: uppercase; color: #2a2f3a; font-weight: bold; border-bottom: 1px solid #e4e4e7; }
  .phase-row .ph-sub { float: right; color: #6b7280; font-weight: bold; }

  .item-name { font-weight: 500; }
  .item-remark { font-size: 9.5px; color: #6b7280; margin-top: 2px; }

  .boq-table tfoot td { padding: 12px 14px; font-size: 11px; border-top: 2px solid #14181f; }
  .boq-table tfoot .grand-lbl { text-align: right; font-weight: bold; color: #6b7280; letter-spacing: 0.5px; text-transform: uppercase; font-size: 10px; }
  .boq-table tfoot .grand-val { text-align: right; font-family: "Courier New", monospace; font-size: 14px; font-weight: bold; color: #14181f; }

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

  /* Signature area */
  .sig-area { height: 60px; border-top: 1px solid #14181f; margin-top: 4px; padding-top: 4px; }
  .sig-line { font-size: 9px; color: #6b7280; text-align: center; }
  .sig-date { font-size: 9px; color: #6b7280; text-align: center; margin-top: 3px; }
</style>
</head>
<body>
<div class="page">

  {{-- Document Header --}}
  <div class="doc-header">
    <div class="company">{{ config('app.name') }} — Project Estimate</div>
    <div class="code-chip">{{ $estimate->estimate_no ?? ('EST-V' . $estimate->version) }}</div>
    <h1>{{ $project->name }}</h1>
    <div class="meta-row">
      <span style="font-family:'Courier New',monospace;">{{ $project->code ?? 'N/A' }}</span>
      @if($project->location)
        <span>{{ $project->location }}</span>
      @endif
      <span class="status-badge status-{{ $estimate->status?->value ?? 'draft' }}">{{ $estimate->status?->label() ?? 'Draft' }}</span>
      <span style="margin-left:auto;">Generated: {{ now()->format('d M Y') }}</span>
    </div>
  </div>

  {{-- Summary KPIs --}}
  <table class="kpi-strip" cellspacing="4" cellpadding="0">
    <tr>
      <td>
        <div class="kpi-label">Material</div>
        <div class="kpi-value">BDT {{ number_format($totals['material'], 2) }}</div>
        <div class="kpi-meta">{{ $estimate->items->where('cost_type', 'material')->count() }} items</div>
      </td>
      <td>
        <div class="kpi-label">Labour</div>
        <div class="kpi-value">BDT {{ number_format($totals['labour'], 2) }}</div>
        <div class="kpi-meta">{{ $estimate->items->where('cost_type', 'labour')->count() }} items</div>
      </td>
      <td>
        <div class="kpi-label">Overhead & Indirect</div>
        <div class="kpi-value">BDT {{ number_format($totals['overhead'] + $totals['indirect'], 2) }}</div>
        <div class="kpi-meta">{{ $estimate->items->whereIn('cost_type', ['overhead', 'indirect'])->count() }} items</div>
      </td>
      <td>
        <div class="kpi-label">Grand Total</div>
        <div class="kpi-value">BDT {{ number_format($totals['grand'], 2) }}</div>
        <div class="kpi-meta">{{ $estimate->items->count() }} line items</div>
      </td>
    </tr>
  </table>

  {{-- Two-column layout --}}
  <table class="two-col" cellspacing="0" cellpadding="0">
    <tr>
      {{-- Main Column --}}
      <td class="main">

        {{-- Estimate Details --}}
        <div class="card">
          <div class="card-head">Estimate Details</div>
          <div class="card-body">
            <table class="def-table">
              <tr>
                <td>
                  <span class="def-dt">Estimate No.</span>
                  <span class="def-dd" style="font-family:'Courier New',monospace;">{{ $estimate->estimate_no ?? ('EST-V' . $estimate->version) }}</span>
                </td>
                <td>
                  <span class="def-dt">Version</span>
                  <span class="def-dd">v{{ $estimate->version }}</span>
                </td>
              </tr>
              <tr>
                <td>
                  <span class="def-dt">Title</span>
                  <span class="def-dd">{{ $estimate->title ?? 'Estimate V' . $estimate->version }}</span>
                </td>
                <td>
                  <span class="def-dt">Date</span>
                  <span class="def-dd" style="font-family:'Courier New',monospace;">{{ optional($estimate->estimate_date)->format('d M Y') ?? '—' }}</span>
                </td>
              </tr>
              <tr>
                <td class="full" colspan="2">
                  <span class="def-dt">Status</span>
                  <span class="def-dd">
                    <span class="status-badge status-{{ $estimate->status?->value ?? 'draft' }}">{{ $estimate->status?->label() ?? 'Draft' }}</span>
                  </span>
                </td>
              </tr>
              @if($estimate->notes)
              <tr>
                <td class="full" colspan="2">
                  <span class="def-dt">Notes</span>
                  <span class="def-dd">{{ $estimate->notes }}</span>
                </td>
              </tr>
              @endif
            </table>
          </div>
        </div>

        {{-- Project Details --}}
        <div class="card">
          <div class="card-head">Project Information</div>
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
              @if($project->location)
              <tr>
                <td class="full" colspan="2">
                  <span class="def-dt">Location</span>
                  <span class="def-dd">{{ $project->location }}</span>
                </td>
              </tr>
              @endif
            </table>
          </div>
        </div>

        {{-- BOQ Table --}}
        <div class="card">
          <div class="card-head">Bill of Quantities</div>
          <div class="card-body">
            @if($boqItems->isEmpty())
              <p style="font-size:10px;color:#9aa0a6;font-style:italic;padding:10px 0;">No estimate items.</p>
            @else
            <table class="boq-table">
              <thead>
                <tr>
                  <th style="width:32%;">Item</th>
                  <th style="width:10%;">Cost Type</th>
                  <th style="width:8%;">Unit</th>
                  <th class="right" style="width:10%;">Qty</th>
                  <th class="right" style="width:12%;">Rate</th>
                  <th class="right" style="width:14%;">Amount</th>
                  <th style="width:14%;">Phase</th>
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
                        @if($item->is_optional)<span style="display:inline-block;font-size:8.5px;font-weight:bold;letter-spacing:0.3px;text-transform:uppercase;background:#f0f0f1;color:#6b7280;padding:1px 5px;border-radius:3px;margin-left:6px;">Optional</span>@endif
                      </div>
                      @if($item->remarks)<div class="item-remark">{{ $item->remarks }}</div>@endif
                    </td>
                    <td style="font-size:10px;color:#6b7280;">{{ ucfirst($item->cost_type?->value ?? 'N/A') }}</td>
                    <td style="color:#6b7280;font-size:10px;">{{ $item->unit ?? '—' }}</td>
                    <td class="right mono">{{ number_format($item->estimated_qty, 2) }}</td>
                    <td class="right mono">{{ number_format($item->estimated_rate, 2) }}</td>
                    <td class="right mono">{{ number_format($item->estimated_amount, 2) }}</td>
                    <td style="font-size:9px;color:#6b7280;">
                      @if($item->work_phase)
                        {{ $item->work_phase->label() }}
                      @else
                        —
                      @endif
                    </td>
                  </tr>
                  @endforeach
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="5" class="grand-lbl">Grand Total Estimated Amount</td>
                  <td class="grand-val">BDT {{ number_format($totals['grand'], 2) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
            @endif
          </div>
        </div>

      </td>

      {{-- Side Column --}}
      <td class="side">

        {{-- Created By --}}
        <div class="card">
          <div class="card-head">Created By</div>
          <div class="card-body">
            @if($estimate->createdBy)
              <div style="margin-bottom:10px;">
                <div style="font-size:11px;font-weight:bold;color:#14181f;margin-bottom:2px;">{{ $estimate->createdBy->name }}</div>
                <div style="font-size:9.5px;color:#6b7280;">
                  @if($estimate->createdBy->roles->isNotEmpty())
                    {{ $estimate->createdBy->roles->first()->name ?? 'Engineer' }}
                  @else
                    Engineer
                  @endif
                </div>
              </div>
              @if($estimate->estimate_date)
                <div style="font-size:9.5px;color:#6b7280;">{{ $estimate->estimate_date->format('d M Y') }}</div>
              @endif
            @else
              <p style="font-size:10px;color:#9aa0a6;font-style:italic;">Not specified</p>
            @endif
          </div>
        </div>

        {{-- Approval Info --}}
        @if($estimate->approvedBy && $estimate->approved_at)
        <div class="card">
          <div class="card-head">Approved By</div>
          <div class="card-body">
            <div style="margin-bottom:10px;">
              <div style="font-size:11px;font-weight:bold;color:#14181f;margin-bottom:2px;">{{ $estimate->approvedBy->name }}</div>
              <div style="font-size:9.5px;color:#6b7280;">
                @if($estimate->approvedBy->roles->isNotEmpty())
                  {{ $estimate->approvedBy->roles->first()->name ?? 'Manager' }}
                @else
                  Manager
                @endif
              </div>
            </div>
            <div style="font-size:9.5px;color:#6b7280;">{{ $estimate->approved_at->format('d M Y, H:i') }}</div>
          </div>
        </div>

        {{-- Chairman Signature --}}
        <div class="card">
          <div class="card-head">Chairman Signature</div>
          <div class="card-body">
            <div class="sig-area">
              <div class="sig-line"></div>
              <div class="sig-date">{{ $estimate->approvedBy->name }}</div>
            </div>
            <div style="font-size:9px;color:#6b7280;text-align:center;margin-top:8px;">Chairman / Authorized Signatory</div>
          </div>
        </div>
        @else
        <div class="card">
          <div class="card-head">Chairman Signature</div>
          <div class="card-body">
            <div class="sig-area">
              <div class="sig-line"></div>
            </div>
            <div style="font-size:9px;color:#6b7280;text-align:center;margin-top:8px;">Chairman / Authorized Signatory</div>
          </div>
        </div>
        @endif

        {{-- Attachments --}}
        @if($estimate->attachments && is_array($estimate->attachments) && count($estimate->attachments) > 0)
        <div class="card">
          <div class="card-head">Attachments</div>
          <div class="card-body">
            <div style="font-size:9.5px;color:#6b7280;margin-bottom:4px;">
              <strong>{{ count($estimate->attachments) }}</strong> file(s) attached
            </div>
            <ul style="font-size:9.5px;color:#14181f;padding-left:16px;line-height:1.6;">
              @foreach($estimate->attachments as $attachmentId)
                <li>File ID: {{ $attachmentId }}</li>
              @endforeach
            </ul>
          </div>
        </div>
        @endif

        {{-- Record --}}
        <div class="card">
          <div class="card-head">Record</div>
          <div class="card-body">
            <table style="width:100%;border-collapse:collapse;">
              <tr>
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Estimate ID</td>
                <td style="font-size:9.5px;color:#14181f;font-weight:500;text-align:right;font-family:'Courier New',monospace;padding:4px 0;">#{{ $estimate->id }}</td>
              </tr>
              <tr style="border-top:1px solid #ececec;">
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Created</td>
                <td style="font-size:9.5px;color:#14181f;font-weight:500;text-align:right;font-family:'Courier New',monospace;padding:4px 0;">{{ $estimate->created_at->format('d M Y') }}</td>
              </tr>
              <tr style="border-top:1px solid #ececec;">
                <td style="font-size:9.5px;color:#6b7280;padding:4px 0;">Last Updated</td>
                <td style="font-size:9.5px;color:#14181f;font-weight:500;text-align:right;font-family:'Courier New',monospace;padding:4px 0;">{{ $estimate->updated_at->format('d M Y') }}</td>
              </tr>
            </table>
          </div>
        </div>

      </td>
    </tr>
  </table>

  {{-- Footer --}}
  <div class="pdf-footer">
    {{ config('app.name') }} &nbsp;·&nbsp; Project Estimate &nbsp;·&nbsp; {{ $estimate->estimate_no ?? ('EST-V' . $estimate->version) }} &nbsp;·&nbsp; Generated {{ now()->format('d M Y \a\t H:i') }}
  </div>

</div>
</body>
</html>
