<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>{{ $estimate->estimate_no ?? ('EST-V' . $estimate->version) }} — {{ $project->name }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: "DejaVu Sans", Arial, sans-serif;
    font-size: 8.5px;
    color: #000;
    line-height: 1.3;
    margin: 0;
    padding: 0;
  }
  @page {
    margin: 10mm;
    padding: 0;
  }
  .page { padding: 15px; margin: 0; }

  /* ---------- Letterhead ---------- */
  .letterhead {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 4px;
  }
  .letterhead .company {
    font-size: 18px;
    font-weight: bold;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }
  .letterhead .addr {
    font-size: 9px;
    color: #333;
    margin-top: 3px;
  }
  .doc-title {
    text-align: center;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin: 10px 0 12px;
    padding: 4px 0;
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
  }

  /* ---------- Meta block (two columns) ---------- */
  .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  .meta-table td { vertical-align: top; width: 50%; padding: 0 0 1px; font-size: 8.5px; }
  .meta-table .k { color: #333; display: inline-block; width: 80px; }
  .meta-table .v { font-weight: bold; }
  .meta-table .mono { font-family: "Courier New", monospace; }

  .status-tag {
    display: inline-block;
    border: 1px solid #000;
    padding: 1px 8px;
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }

  /* ---------- Section heading ---------- */
  .sec-head {
    font-size: 8.5px;
    font-weight: bold;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    background: #eee;
    border: 1px solid #000;
    border-bottom: none;
    padding: 3px 6px;
    margin-top: 8px;
    margin-bottom: 0;
  }

  /* ---------- BOQ table ---------- */
  .boq { width: 100%; border-collapse: collapse; }
  .boq thead th {
    text-align: left;
    font-size: 7.5px;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    padding: 4px 6px;
    border: 1px solid #000;
    background: #eee;
  }
  .boq thead th.right { text-align: right; }
  .boq tbody td {
    padding: 3px 6px;
    font-size: 8.5px;
    border: 1px solid #999;
    vertical-align: top;
  }
  .boq td.right { text-align: right; font-family: "Courier New", monospace; }
  .boq td.center { text-align: center; }

  .phase-row td {
    background: #ddd;
    font-size: 7.5px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    font-weight: bold;
    border: 1px solid #000 !important;
    padding: 3px 6px;
  }
  .phase-row .ph-sub { float: right; font-family: "Courier New", monospace; white-space: nowrap; }

  .item-name { font-weight: bold; }
  .item-remark { font-size: 7.5px; color: #444; font-style: italic; margin-top: 1px; }
  .opt {
    font-size: 7px; font-weight: bold; letter-spacing: 0.3px; text-transform: uppercase;
    border: 1px solid #000; padding: 0 3px; margin-left: 4px;
  }

  .boq tfoot td {
    padding: 5px 6px;
    font-size: 9px;
    border: 1px solid #000;
    background: #eee;
  }
  .boq tfoot .grand-lbl { text-align: right; font-weight: bold; letter-spacing: 0.5px; text-transform: uppercase; font-size: 9px; }
  .boq tfoot .grand-val { text-align: right; font-family: "Courier New", monospace; font-size: 11px; font-weight: bold; }

  /* ---------- Summary table ---------- */
  .sum { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  .sum td { border: 1px solid #999; padding: 3px 6px; font-size: 8.5px; }
  .sum .lbl { background: #f5f5f5; font-weight: bold; width: 70%; }
  .sum .val { text-align: right; font-family: "Courier New", monospace; width: 30%; }
  .sum .total td { border: 1px solid #000; background: #eee; font-weight: bold; }
  .sum .total .val { font-size: 10px; }

  /* ---------- Notes ---------- */
  .notes-box { border: 1px solid #999; padding: 6px; font-size: 9px; margin-bottom: 8px; }
  .notes-box .lbl { font-weight: bold; text-transform: uppercase; font-size: 9px; letter-spacing: 0.5px; margin-bottom: 3px; }

  /* ---------- Signatures ---------- */
  .sig-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
  .sig-table td { width: 33.33%; vertical-align: bottom; padding: 0 12px; }
  .sig-line { border-top: 1px solid #000; padding-top: 4px; text-align: center; }
  .sig-role { font-size: 9.5px; font-weight: bold; }
  .sig-sub { font-size: 8.5px; color: #444; }

  /* ---------- Footer ---------- */
  .doc-footer {
    margin-top: 12px;
    padding-top: 4px;
    border-top: 1px solid #000;
    font-size: 8px;
    color: #444;
  }
  .doc-footer .right { float: right; }
</style>
</head>
<body>
<div class="page">

  {{-- ============ LETTERHEAD ============ --}}
  <div class="letterhead">
    <div class="company">{{ config('app.name') }}</div>
    <div class="addr">
      @if($project->location){{ $project->location }}@endif
    </div>
  </div>
  <div class="doc-title">Project Cost Estimate</div>

  {{-- ============ META BLOCK ============ --}}
  <table class="meta-table">
    <tr>
      <td>
        <span class="k">Estimate No.</span>
        <span class="v mono">{{ $estimate->estimate_no ?? ('EST-V' . $estimate->version) }}</span>
      </td>
      <td>
        <span class="k">Project</span>
        <span class="v">{{ $project->name }}</span>
      </td>
    </tr>
    <tr>
      <td>
        <span class="k">Version</span>
        <span class="v">v{{ $estimate->version }}</span>
      </td>
      <td>
        <span class="k">Project Code</span>
        <span class="v mono">{{ $project->code ?? '—' }}</span>
      </td>
    </tr>
    <tr>
      <td>
        <span class="k">Estimate Date</span>
        <span class="v mono">{{ optional($estimate->estimate_date)->format('d M Y') ?? '—' }}</span>
      </td>
      <td>
        <span class="k">Title</span>
        <span class="v">{{ $estimate->title ?? 'Estimate V' . $estimate->version }}</span>
      </td>
    </tr>
    <tr>
      <td>
        <span class="k">Status</span>
        <span class="status-tag">{{ $estimate->status?->label() ?? 'Draft' }}</span>
      </td>
      <td>
        <span class="k">Generated</span>
        <span class="v mono">{{ now()->format('d M Y') }}</span>
      </td>
    </tr>
  </table>

  {{-- ============ BILL OF QUANTITIES ============ --}}
  <div class="sec-head">Bill of Quantities</div>
  @if($boqItems->isEmpty())
    <table class="boq"><tbody><tr><td style="font-style:italic;color:#666;padding:12px;">No estimate items.</td></tr></tbody></table>
  @else
  <table class="boq">
    <thead>
      <tr>
        <th style="width:6%;">SL</th>
        <th style="width:34%;">Description</th>
        <th style="width:12%;">Cost Type</th>
        <th style="width:8%;">Unit</th>
        <th class="right" style="width:11%;">Qty</th>
        <th class="right" style="width:13%;">Rate (BDT)</th>
        <th class="right" style="width:16%;">Amount (BDT)</th>
      </tr>
    </thead>
    <tbody>
      @php $sl = 0; @endphp
      @foreach($boqItems as $phaseKey => $items)
        @php $phaseLabel = $phaseKey ? (\App\Enums\Projects\WorkPhase::tryFrom($phaseKey)?->label() ?? $phaseKey) : 'General'; @endphp
        <tr class="phase-row">
          <td colspan="7">{{ $phaseLabel }} <span class="ph-sub">BDT {{ number_format($items->sum('estimated_amount'), 2) }}</span></td>
        </tr>
        @foreach($items as $item)
        @php $sl++; @endphp
        <tr>
          <td class="center">{{ $sl }}</td>
          <td>
            <span class="item-name">{{ $item->material?->name ?? $item->transactionCategory?->name ?? $item->name ?? '—' }}</span>
            @if($item->is_optional)<span class="opt">Optional</span>@endif
            @if($item->remarks)<div class="item-remark">{{ $item->remarks }}</div>@endif
          </td>
          <td>{{ ucfirst($item->cost_type?->value ?? '—') }}</td>
          <td>{{ $item->unit ?? '—' }}</td>
          <td class="right">{{ number_format($item->estimated_qty, 2) }}</td>
          <td class="right">{{ number_format($item->estimated_rate, 2) }}</td>
          <td class="right">{{ number_format($item->estimated_amount, 2) }}</td>
        </tr>
        @endforeach
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="6" class="grand-lbl">Grand Total Estimated Amount</td>
        <td class="grand-val">{{ number_format($totals['grand'], 2) }}</td>
      </tr>
    </tfoot>
  </table>
  @endif

  {{-- ============ COST SUMMARY ============ --}}
  <div class="sec-head" style="margin-top:14px;">Cost Summary</div>
  <table class="sum">
    <tr><td class="lbl">Material</td><td class="val">BDT {{ number_format($totals['material'], 2) }}</td></tr>
    <tr><td class="lbl">Labour</td><td class="val">BDT {{ number_format($totals['labour'], 2) }}</td></tr>
    <tr><td class="lbl">Overhead &amp; Indirect</td><td class="val">BDT {{ number_format($totals['overhead'] + $totals['indirect'], 2) }}</td></tr>
    <tr class="total"><td class="lbl">Grand Total</td><td class="val">BDT {{ number_format($totals['grand'], 2) }}</td></tr>
  </table>

  {{-- ============ NOTES ============ --}}
  @if($estimate->notes)
  <div class="notes-box">
    <div class="lbl">Notes</div>
    {{ $estimate->notes }}
  </div>
  @endif

  {{-- ============ SIGNATURES ============ --}}
  <table class="sig-table">
    <tr>
      <td>
        <div class="sig-line">
          <div class="sig-role">{{ $estimate->createdBy?->name ?? 'Prepared By' }}</div>
          <div class="sig-sub">Prepared By</div>
        </div>
      </td>
      <td>
        <div class="sig-line">
          <div class="sig-role">Checked By</div>
          <div class="sig-sub">Engineering Dept.</div>
        </div>
      </td>
      <td>
        <div class="sig-line">
          <div class="sig-role">{{ ($estimate->approvedBy && $estimate->approved_at) ? $estimate->approvedBy->name : 'Approved By' }}</div>
          <div class="sig-sub">Chairman / Authorized Signatory</div>
        </div>
      </td>
    </tr>
  </table>

  {{-- ============ FOOTER ============ --}}
  <div class="doc-footer">
    {{ config('app.name') }} · Project Cost Estimate · {{ $estimate->estimate_no ?? ('EST-V' . $estimate->version) }}
    <span class="right">Generated {{ now()->format('d M Y \a\t H:i') }} · Estimate ID #{{ $estimate->id }}</span>
  </div>

</div>
</body>
</html>
