@php $bdt = fn($n) => 'BDT ' . number_format((float) $n, 2); @endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Invoice — {{ $sale['number'] }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
  :root {
    --ink:#14181f; --ink-2:#2a2f3a; --muted:#6b7280; --muted-2:#9aa0a6;
    --rule:#d9d9d9; --rule-soft:#ececec; --paper:#ffffff; --bg:#e9e9ea; --accent:#0d2a4a;
    --red:#8a1212; --green:#1f6f43;
  }
  * { box-sizing: border-box; }
  html, body {
    margin:0; padding:0; background:{{ $pdfMode ? '#fff' : 'var(--bg)' }}; color:var(--ink);
    font-family:"Inter", Helvetica, Arial, sans-serif; font-size:11px; line-height:1.45;
    -webkit-print-color-adjust:exact; print-color-adjust:exact;
  }
  .workspace { padding:40px 20px 90px; }
  .sheet {
    width:210mm; min-height:297mm; margin:0 auto; background:var(--paper);
    padding:13mm 14mm 11mm; position:relative; border-top:6px solid var(--accent);
  }
  @if(!$pdfMode)
    /* Screen-only chrome — dompdf renders shadows very expensively, so skip in PDF. */
    .sheet { box-shadow:0 1px 2px rgba(0,0,0,0.04), 0 12px 40px -8px rgba(0,0,0,0.18); }
  @endif

  /* Header */
  .header { width:100%; border-bottom:1px solid var(--rule); padding-bottom:10px; }
  .header td { vertical-align:top; }
  .brand-mark { width:74px; height:74px; border:1.5px solid var(--accent); color:var(--accent);
    text-align:center; font-family:Georgia, serif; font-size:22px; line-height:74px; }
  .brand-name { font-family:Georgia, serif; font-size:19px; color:var(--ink); margin-bottom:3px; }
  .brand-tag { font-size:8.5px; color:var(--muted); letter-spacing:0.6px; text-transform:uppercase; margin-bottom:5px; }
  .brand-meta { font-size:9.5px; color:var(--ink-2); line-height:1.5; }
  .brand-meta .sep { color:var(--muted-2); margin:0 5px; }
  .doc-id { text-align:right; }
  .doc-label { font-family:Georgia, serif; font-size:24px; color:var(--ink); line-height:1; margin-bottom:3px; }
  .doc-sub { font-size:8.5px; letter-spacing:0.6px; text-transform:uppercase; color:var(--muted); margin-bottom:10px; }
  .doc-id-grid { margin-left:auto; }
  .doc-id-grid td { font-size:10px; padding:2px 0; }
  .doc-id-grid .k { color:var(--muted); text-transform:uppercase; font-size:8.5px; padding-right:14px; text-align:right; }
  .doc-id-grid .v { font-family:"Courier New", monospace; font-weight:700; text-align:right; }

  /* Pills */
  .pill { display:inline-block; padding:2px 8px; border-radius:999px; font-size:8.5px; font-weight:700;
    letter-spacing:0.3px; text-transform:uppercase; border:1px solid; }
  .pill-sale { background:rgba(13,42,74,.06); color:var(--accent); border-color:var(--accent); }
  .pill-paid { background:rgba(31,111,67,.08); color:var(--green); border-color:var(--green); }
  .pill-due  { background:rgba(138,18,18,.06); color:var(--red); border-color:var(--red); }
  .pill-mut  { background:#f3f4f6; color:var(--muted); border-color:var(--rule); }

  /* Parties */
  .parties { width:100%; border-collapse:collapse; margin-top:12px; border:1px solid var(--rule); }
  .parties td { width:50%; padding:9px 12px 10px; vertical-align:top; }
  .parties td + td { border-left:1px solid var(--rule); }
  .party-label { font-size:8.5px; letter-spacing:1px; text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
  .party-name { font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:3px; }
  .party-line { font-size:9.5px; color:var(--ink-2); line-height:1.6; }
  .party-line b { color:var(--muted); font-weight:500; display:inline-block; min-width:54px; }

  /* Section title */
  .section-title { margin:14px 0 6px; }
  .section-title h2 { font-family:Georgia, serif; font-weight:400; font-size:14px; margin:0; display:inline-block; }
  .section-title .hint { float:right; font-size:9px; letter-spacing:0.6px; text-transform:uppercase; color:var(--muted); padding-top:4px; }

  /* Units table */
  table.grid { width:100%; border-collapse:collapse; border:1px solid var(--rule); }
  table.grid th { background:#f7f7f8; text-align:left; font-size:8.5px; letter-spacing:0.7px; text-transform:uppercase;
    color:var(--muted); font-weight:700; padding:6px 10px; border-bottom:1px solid var(--rule); }
  table.grid th.r, table.grid td.r { text-align:right; }
  table.grid td { padding:8px 10px; font-size:10px; color:var(--ink); border-top:1px solid var(--rule-soft); vertical-align:top; }
  table.grid td.mono, table.grid th.mono { font-family:"Courier New", monospace; }
  table.grid tr:first-child td { border-top:none; }
  table.grid tfoot td { background:#f7f7f8; border-top:1px solid var(--rule); font-weight:700; font-size:10.5px; }
  .u-code { font-family:"Courier New", monospace; font-weight:700; color:var(--accent); }
  .u-sub { color:var(--muted); font-size:9px; margin-top:2px; }
  .neg { color:var(--red); }

  /* Summary block (two columns via table) */
  .sum-wrap { width:100%; border-collapse:collapse; margin-top:12px; }
  .sum-wrap > tbody > tr > td { vertical-align:top; }
  .sum-left { width:58%; padding-right:14px; }
  .sum-right { width:42%; }
  .words { border:1px solid var(--rule); padding:10px 12px; }
  .words .label { font-size:8.5px; letter-spacing:1px; text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
  .words .in-words { font-family:Georgia, serif; font-size:13px; color:var(--ink); line-height:1.35; }

  .totals { border:1px solid var(--rule); width:100%; border-collapse:collapse; }
  .totals td { padding:6px 12px; font-size:10px; border-bottom:1px solid var(--rule-soft); }
  .totals td.v { text-align:right; font-family:"Courier New", monospace; font-weight:500; }
  .totals td.l { color:var(--muted); }
  .totals tr:last-child td { border-bottom:none; }
  .totals tr.grand td { background:var(--accent); color:#fff; padding:9px 12px; }
  .totals tr.grand td.l { color:rgba(255,255,255,.8); text-transform:uppercase; letter-spacing:.5px; font-size:9.5px; }
  .totals tr.grand td.v { color:#fff; font-size:15px; font-weight:700; }

  /* Schedule */
  .sched-sum { width:100%; border-collapse:collapse; border:1px solid var(--rule); margin-bottom:8px; }
  .sched-sum td { width:33.33%; padding:9px 12px; }
  .sched-sum td + td { border-left:1px solid var(--rule); }
  .ss-k { font-size:8.5px; letter-spacing:.7px; text-transform:uppercase; color:var(--muted); margin-bottom:4px; }
  .ss-v { font-family:"Courier New", monospace; font-weight:700; font-size:13px; }

  /* Signatures + footer */
  .signatures { width:100%; border-collapse:collapse; margin-top:30px; }
  .signatures td { width:50%; text-align:center; padding:0 18px; vertical-align:bottom; }
  .sig-line { border-top:1px solid var(--ink-2); margin:30px 8px 5px; }
  .sig-role { font-size:10px; font-weight:700; }
  .sig-sub { font-size:8.5px; letter-spacing:.5px; text-transform:uppercase; color:var(--muted); }
  .footer { margin-top:16px; padding-top:8px; border-top:1px solid var(--rule); width:100%; }
  .footer td { font-size:8.5px; color:var(--muted); vertical-align:bottom; }
  .footer .stamp { text-align:right; font-family:"Courier New", monospace; color:var(--muted-2); line-height:1.5; }

  .actions { position:fixed; bottom:24px; left:0; right:0; text-align:center; z-index:50; }
  .actions .bar { display:inline-block; background:#14181f; padding:8px; border-radius:999px; box-shadow:0 12px 36px rgba(0,0,0,.25); }
  .actions a, .actions button { background:transparent; border:0; color:#fff; font-family:inherit; font-size:12px; font-weight:500;
    padding:8px 18px; border-radius:999px; cursor:pointer; text-decoration:none; display:inline-block; }
  .actions a.primary { background:#fff; color:#14181f; }

  @page { size:A4; margin:0; }
  @media print { html, body { background:#fff; } .workspace { padding:0; } .actions { display:none !important; } .sheet { box-shadow:none; } }
</style>
</head>
<body>
  <div class="workspace">
    <div class="sheet">

      {{-- Header --}}
      <table class="header">
        <tr>
          <td style="width:62%;">
            <div class="brand-name">{{ $company['name'] }}</div>
            <div class="brand-tag">{{ $company['tag'] }}</div>
            <div class="brand-meta">
              {{ $company['address'] }}<br>
              <span>{{ $company['phone'] }}</span><span class="sep">·</span><span>{{ $company['email'] }}</span><br>
              <span>{{ $company['website'] }}</span>
            </div>
          </td>
          <td class="doc-id">
            <div class="doc-label">Invoice</div>
            <div class="doc-sub">Property {{ $sale['type_label'] }}</div>
            <table class="doc-id-grid">
              <tr><td class="k">Invoice No.</td><td class="v">{{ $sale['number'] }}</td></tr>
              @if($sale['sale_date'])<tr><td class="k">Sale Date</td><td class="v">{{ $sale['sale_date'] }}</td></tr>@endif
              @if($sale['contract_date'])<tr><td class="k">Contract</td><td class="v">{{ $sale['contract_date'] }}</td></tr>@endif
              <tr><td class="k">Issued</td><td class="v">{{ $sale['created'] }}</td></tr>
            </table>
          </td>
        </tr>
      </table>

      {{-- Status pills --}}
      <div style="margin-top:10px;">
        <span class="pill pill-sale">{{ $sale['type_label'] }}</span>
        <span class="pill {{ $sale['payment_status'] === 'Paid' ? 'pill-paid' : ($sale['payment_status'] === 'Pending' ? 'pill-due' : 'pill-mut') }}">{{ $sale['payment_status'] }}</span>
        <span class="pill pill-mut">{{ $sale['status'] }}</span>
        @if($unit_count > 1)<span class="pill pill-mut">{{ $unit_count }} Units</span>@endif
      </div>

      {{-- Parties --}}
      <table class="parties">
        <tr>
          <td>
            <div class="party-label">Billed To</div>
            <div class="party-name">{{ $customer['name'] }}</div>
            @if($customer['id'])<div class="party-line"><b>Customer ID</b> {{ $customer['id'] }}</div>@endif
            @if($customer['phone'])<div class="party-line"><b>Phone</b> {{ $customer['phone'] }}</div>@endif
            @if($customer['email'])<div class="party-line"><b>Email</b> {{ $customer['email'] }}</div>@endif
            @if($customer['address'])<div class="party-line"><b>Address</b> {{ $customer['address'] }}</div>@endif
          </td>
          <td>
            <div class="party-label">Seller</div>
            <div class="party-name">{{ $company['name'] }}</div>
            <div class="party-line"><b>Address</b> {{ $company['address'] }}</div>
            <div class="party-line"><b>Phone</b> {{ $company['phone'] }}</div>
            <div class="party-line"><b>Email</b> {{ $company['email'] }}</div>
          </td>
        </tr>
      </table>

      {{-- Units --}}
      <div class="section-title">
        <h2>{{ $unit_count > 1 ? 'Units' : 'Unit' }} &amp; Pricing</h2>
        <span class="hint">Contract {{ $sale['number'] }}</span>
        <div style="clear:both;"></div>
      </div>
      <table class="grid">
        <thead>
          <tr>
            <th style="width:26%;">Unit</th>
            <th class="r" style="width:15%;">Sale</th>
            <th class="r" style="width:12%;">Discount</th>
            <th class="r" style="width:9%;">Tax</th>
            <th class="r" style="width:12%;">Service</th>
            <th class="r" style="width:12%;">Utility</th>
            <th class="r" style="width:14%;">Net</th>
          </tr>
        </thead>
        <tbody>
          @foreach($units as $u)
            <tr>
              <td>
                <div class="u-code">{{ $u['code'] }}</div>
                <div class="u-sub">
                  {{ $u['property'] }}@if($u['type'] !== '—') · {{ $u['type'] }}@endif@if($u['floor']) · {{ $u['floor'] }}@endif@if($u['area']) · {{ $u['area'] }}@endif
                </div>
              </td>
              <td class="r mono">{{ number_format($u['sale'], 2) }}</td>
              <td class="r mono {{ $u['discount'] > 0 ? 'neg' : '' }}">{{ $u['discount'] > 0 ? '−' : '' }}{{ number_format($u['discount'], 2) }}</td>
              <td class="r mono">{{ number_format($u['tax'], 2) }}</td>
              <td class="r mono">{{ number_format($u['service'], 2) }}</td>
              <td class="r mono">{{ number_format($u['utility'], 2) }}</td>
              <td class="r mono" style="font-weight:700;">{{ number_format($u['net'], 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        @if($unit_count > 1)
          <tfoot>
            <tr>
              <td>{{ $unit_count }} units · total</td>
              <td class="r mono">{{ number_format($sale['sale_amount'], 2) }}</td>
              <td class="r mono neg">{{ $sale['discount'] > 0 ? '−' : '' }}{{ number_format($sale['discount'], 2) }}</td>
              <td class="r mono">{{ number_format($sale['tax'], 2) }}</td>
              <td class="r mono">{{ number_format($sale['service'], 2) }}</td>
              <td class="r mono">{{ number_format($sale['utility'], 2) }}</td>
              <td class="r mono" style="color:var(--accent);">{{ number_format($sale['net'], 2) }}</td>
            </tr>
          </tfoot>
        @endif
      </table>

      {{-- Summary + amount in words --}}
      <table class="sum-wrap">
        <tr>
          <td class="sum-left">
            <div class="words">
              <div class="label">Total in Words</div>
              <div class="in-words">{{ $sale['final_words'] }}</div>
            </div>
          </td>
          <td class="sum-right">
            <table class="totals">
              <tr><td class="l">Sale Amount</td><td class="v">{{ $bdt($sale['sale_amount']) }}</td></tr>
              <tr><td class="l">Discount</td><td class="v neg">{{ $sale['discount'] > 0 ? '− ' : '' }}{{ $bdt($sale['discount']) }}</td></tr>
              <tr><td class="l">Tax</td><td class="v">{{ $bdt($sale['tax']) }}</td></tr>
              <tr><td class="l">Net Amount</td><td class="v">{{ $bdt($sale['net']) }}</td></tr>
              <tr><td class="l">Service Charge</td><td class="v">{{ $bdt($sale['service']) }}</td></tr>
              <tr><td class="l">Utility Charge</td><td class="v">{{ $bdt($sale['utility']) }}</td></tr>
              <tr><td class="l">Down Payment @if($sale['down_pct'])({{ number_format($sale['down_pct'], 2) }}%)@endif</td><td class="v">{{ $bdt($sale['down_payment']) }}</td></tr>
              <tr class="grand"><td class="l">Final Amount</td><td class="v">{{ $bdt($sale['final']) }}</td></tr>
            </table>
          </td>
        </tr>
      </table>

      {{-- Payment schedule --}}
      @if(count($schedules) > 0)
        <div class="section-title">
          <h2>Payment Schedule</h2>
          <span class="hint">{{ count($schedules) }} {{ count($schedules) === 1 ? 'entry' : 'entries' }}</span>
          <div style="clear:both;"></div>
        </div>
        <table class="sched-sum">
          <tr>
            <td><div class="ss-k">Scheduled</div><div class="ss-v">{{ $bdt($sched_totals['scheduled']) }}</div></td>
            <td><div class="ss-k">Paid</div><div class="ss-v" style="color:var(--green);">{{ $bdt($sched_totals['paid']) }}</div></td>
            <td><div class="ss-k">Due</div><div class="ss-v" style="color:{{ $sched_totals['due'] > 0 ? 'var(--red)' : 'var(--muted)' }};">{{ $bdt($sched_totals['due']) }}</div></td>
          </tr>
        </table>
        <div style="font-size:9px; color:var(--muted); margin-top:2px;">Full installment breakdown is provided in the separate Payment Schedule document.</div>
      @endif

      {{-- Signatures --}}
      <table class="signatures">
        <tr>
          <td><div class="sig-line"></div><div class="sig-role">Customer's Signature</div><div class="sig-sub">{{ $customer['name'] }}</div></td>
          <td><div class="sig-line"></div><div class="sig-role">Authorized Signature</div><div class="sig-sub">{{ $company['name'] }}</div></td>
        </tr>
      </table>

      {{-- Footer --}}
      <table class="footer">
        <tr>
          <td><em>System-generated invoice — valid without physical signature. Please retain for your records.</em></td>
          <td class="stamp">Generated {{ $generated_at }}<br>Page 1 of 1</td>
        </tr>
      </table>

    </div>
  </div>

  @unless($pdfMode)
    <div class="actions">
      <div class="bar">
        <button onclick="window.print()">Print</button>
        @if(!empty($schedule_url))<a href="{{ $schedule_url }}">Payment Schedule</a>@endif
        <a class="primary" href="{{ $download_url }}">Download PDF</a>
      </div>
    </div>
  @endunless
</body>
</html>
