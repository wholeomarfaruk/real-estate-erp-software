{{--
  Daily Statement Report · PDF template (DomPDF / barryvdh/laravel-dompdf)
  Monochrome · laser & colour printer friendly · DejaVu fonts only.
--}}
@php
  $n = fn($v) => (is_null($v) || $v === '') ? '–' : ($v == 0 ? '0' : number_format($v));
  $z = fn($v) => (is_null($v) || $v === '' || $v == 0) ? 'num-zero' : '';
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { font-family:"DejaVu Sans", sans-serif; box-sizing:border-box; }
  body{ font-size:11px; color:#161616; margin:0; padding:0;
        -webkit-print-color-adjust:exact; print-color-adjust:exact; background:#fff; }

  .sheet{ padding:14mm 13mm; position:relative; }
  .sheet::before{ content:""; position:absolute; left:0; right:0; top:0; height:3px; background:#161616; }

  /* Header */
  .head{ width:100%; border-collapse:collapse; }
  .head td{ vertical-align:top; padding:0; }
  .logo-box{ width:46px; height:46px; border:1.5px solid #161616; color:#161616; text-align:center;
             line-height:43px; font-size:18px; font-weight:bold; letter-spacing:.5px; }
  .co-name{ font-size:15.5px; font-weight:bold; color:#161616; line-height:1.2; white-space:nowrap; }
  .co-addr{ font-size:9.5px; color:#5f5f5f; line-height:1.5; margin-top:3px; }
  .doc-tag{ display:inline-block; border:1px solid #161616; color:#161616; font-size:9px;
            font-weight:bold; letter-spacing:1.6px; padding:3px 11px; text-transform:uppercase; }
  .doc-title{ font-size:14px; font-weight:bold; color:#161616; margin-top:8px; line-height:1.25; }
  .doc-meta{ font-size:9.5px; color:#5f5f5f; margin-top:5px; line-height:1.7; }
  .doc-meta b{ color:#161616; font-weight:bold; }

  .divider{ height:2px; background:#161616; margin:14px 0 0; }
  .divider-soft{ height:1px; background:#c9c9c9; margin:0 0 16px; }

  .band{ background:#f4f4f4; border-top:2px solid #161616; border-bottom:2px solid #161616;
         text-align:center; font-size:11px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;
         color:#161616; padding:7px; margin:18px 0 0; }
  .sec-h{ font-size:8px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;
          color:#5f5f5f; margin:18px 0 7px; }

  .table{ width:100%; border-collapse:collapse; table-layout:fixed; }
  .table thead th{ background:#f4f4f4; color:#161616; font-size:8px; font-weight:bold; letter-spacing:.3px;
                   text-transform:uppercase; padding:7px 6px; text-align:left; border:1px solid #161616;
                   line-height:1.25; vertical-align:middle; }
  .table tbody td{ padding:6px; font-size:9.5px; border:1px solid #c9c9c9; vertical-align:middle; line-height:1.3; }
  .table tbody tr.zebra td{ background:#f4f4f4; }
  .table tfoot td{ padding:7px 6px; font-size:9.5px; font-weight:bold; border:1px solid #161616; background:#f4f4f4; }

  .c{ text-align:center; }
  .acct-name{ font-weight:bold; color:#161616; }
  .acct-no{ color:#5f5f5f; }
  .num{ font-family:"DejaVu Sans Mono", monospace; text-align:right; white-space:nowrap; font-size:9.5px; }
  .num-zero{ color:#8a8a8a; }
  .sn{ text-align:center; color:#5f5f5f; font-family:"DejaVu Sans Mono", monospace; }
  .total-lbl{ text-align:right; text-transform:uppercase; letter-spacing:.6px; font-size:8.5px; }
  .closing td{ border:2px solid #161616; }

  .sign{ width:100%; border-collapse:collapse; margin-top:46px; }
  .sign td{ width:25%; text-align:center; vertical-align:bottom; padding:0 8px; }
  .sign .line{ border-top:1px solid #161616; padding-top:6px; font-size:9.5px; font-weight:bold; color:#161616; }

  .foot{ width:100%; border-top:1px solid #c9c9c9; padding-top:7px; border-collapse:collapse; margin-top:30px; }
  .foot td{ font-size:8.5px; color:#8a8a8a; vertical-align:middle; }

  @page{ size:A4; margin:0; }
  tr, .closing, .sign{ page-break-inside:avoid; }
</style>
</head>
<body>
  <div class="sheet">

    {{-- Header --}}
    <table class="head">
      <tr>
        <td style="width:62%;">
          <div class="co-name">{{ $report['meta']['company_name'] }}</div>
          <div class="co-addr">{{ $report['meta']['address'] }}<br>{{ $report['meta']['contact'] }}</div>
        </td>
        <td style="width:38%; text-align:right;">
          <span class="doc-tag">Daily Statement</span>
          <div class="doc-title">{{ $report['title'] }}</div>
          <div class="doc-meta">
            <b>Statement Date:</b> {{ $report['meta']['statement_date'] }}<br>
            <b>Generated:</b> {{ $report['meta']['generated_at'] }}@if(!empty($report['meta']['generated_by'])) by {{ $report['meta']['generated_by'] }}@endif
          </div>
        </td>
      </tr>
    </table>

    <div class="divider"></div>
    <div class="divider-soft"></div>

    {{-- SECTION 1 · BANK ACCOUNTS LEDGER --}}
    <div class="band">Bank Accounts Ledger</div>
    <table class="table" style="margin-top:0;">
      <colgroup>
        <col style="width:4%"><col style="width:26%"><col style="width:12%"><col style="width:11.5%">
        <col style="width:11.5%"><col style="width:11.5%"><col style="width:11.5%"><col style="width:12%">
      </colgroup>
      <thead>
        <tr>
          <th class="c">S.N</th>
          <th>Bank Name &amp; Account Number</th>
          <th style="text-align:right;">Opening Balance</th>
          <th style="text-align:right;">Cash Deposit</th>
          <th style="text-align:right;">Chq./Online Deposit</th>
          <th style="text-align:right;">Cash Withdraw</th>
          <th style="text-align:right;">Chq./Online Transfer</th>
          <th style="text-align:right;">Closing Balance</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($report['banks'] as $i => $b)
        <tr class="{{ $i % 2 ? 'zebra' : '' }}">
          <td class="sn">{{ $i + 1 }}</td>
          <td><span class="acct-name">{{ $b['name'] }}</span> <span class="acct-no">({{ $b['account'] }})</span></td>
          <td class="num {{ $z($b['opening']) }}">{{ $n($b['opening']) }}</td>
          <td class="num {{ $z($b['cash_deposit']) }}">{{ $n($b['cash_deposit']) }}</td>
          <td class="num {{ $z($b['online_deposit']) }}">{{ $n($b['online_deposit']) }}</td>
          <td class="num {{ $z($b['cash_withdraw']) }}">{{ $n($b['cash_withdraw']) }}</td>
          <td class="num {{ $z($b['online_transfer']) }}">{{ $n($b['online_transfer']) }}</td>
          <td class="num {{ $z($b['closing']) }}">{{ $n($b['closing']) }}</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2" class="total-lbl">Total</td>
          <td class="num {{ $z($report['bank_totals']['opening']) }}">{{ $n($report['bank_totals']['opening']) }}</td>
          <td class="num {{ $z($report['bank_totals']['cash_deposit']) }}">{{ $n($report['bank_totals']['cash_deposit']) }}</td>
          <td class="num {{ $z($report['bank_totals']['online_deposit']) }}">{{ $n($report['bank_totals']['online_deposit']) }}</td>
          <td class="num {{ $z($report['bank_totals']['cash_withdraw']) }}">{{ $n($report['bank_totals']['cash_withdraw']) }}</td>
          <td class="num {{ $z($report['bank_totals']['online_transfer']) }}">{{ $n($report['bank_totals']['online_transfer']) }}</td>
          <td class="num {{ $z($report['bank_totals']['closing']) }}">{{ $n($report['bank_totals']['closing']) }}</td>
        </tr>
      </tfoot>
    </table>

    <div class="sec-h">Receipts</div>
    <table class="table">
      <colgroup>
        <col style="width:4%"><col style="width:33%"><col style="width:25%">
        <col style="width:10%"><col style="width:10%"><col style="width:9%"><col style="width:9%">
      </colgroup>
      <thead>
        <tr>
          <th class="c">S.N</th><th>Account Name</th><th>Particulars</th>
          <th class="c">MR-No</th><th class="c">Folio/No</th>
          <th style="text-align:right;">Cash</th><th style="text-align:right;">Bank</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($report['receipts'] as $i => $r)
        <tr class="{{ $i % 2 ? 'zebra' : '' }}">
          <td class="sn">{{ $i + 1 }}</td>
          <td>{{ $r['account'] }}</td>
          <td>{{ $r['particulars'] ?? '' }}</td>
          <td class="c {{ empty($r['mr_no']) ? 'num-zero' : '' }}">{{ $r['mr_no'] ?? '–' }}</td>
          <td class="c {{ empty($r['folio']) ? 'num-zero' : '' }}">{{ $r['folio'] ?? '–' }}</td>
          <td class="num {{ $z($r['cash']) }}">{{ $n($r['cash']) }}</td>
          <td class="num {{ $z($r['bank']) }}">{{ $n($r['bank']) }}</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="5" class="total-lbl">Total Receipt</td>
          <td class="num {{ $z($report['receipt_totals']['cash']) }}">{{ $n($report['receipt_totals']['cash']) }}</td>
          <td class="num {{ $z($report['receipt_totals']['bank']) }}">{{ $n($report['receipt_totals']['bank']) }}</td>
        </tr>
      </tfoot>
    </table>

    <div class="sec-h">Payments</div>
    <table class="table">
      <colgroup>
        <col style="width:4%"><col style="width:30%"><col style="width:34%">
        <col style="width:10%"><col style="width:9%"><col style="width:13%">
      </colgroup>
      <thead>
        <tr>
          <th class="c">S.N</th><th>Account Name</th><th>Particulars</th>
          <th class="c">Proj-No</th><th class="c">Folio/N</th><th style="text-align:right;">Cash</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($report['payments'] as $i => $p)
        <tr class="{{ $i % 2 ? 'zebra' : '' }}">
          <td class="sn">{{ $i + 1 }}</td>
          <td>{{ $p['account'] }}</td>
          <td>{{ $p['particulars'] ?? '' }}</td>
          <td class="c {{ empty($p['proj_no']) ? 'num-zero' : '' }}">{{ $p['proj_no'] ?? '–' }}</td>
          <td class="c {{ empty($p['folio']) ? 'num-zero' : '' }}">{{ $p['folio'] ?? '–' }}</td>
          <td class="num {{ $z($p['cash']) }}">{{ $n($p['cash']) }}</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="5" class="total-lbl">Total Payments</td>
          <td class="num {{ $z($report['payment_totals']['cash']) }}">{{ $n($report['payment_totals']['cash']) }}</td>
        </tr>
        <tr class="closing">
          <td colspan="4" class="total-lbl" style="font-size:9.5px;">Closing Balance</td>
          <td colspan="2" class="num" style="font-size:10px; white-space:normal; line-height:1.5;">Cash {{ number_format($report['closing']['cash']) }}<br>Bank {{ number_format($report['closing']['bank']) }}</td>
        </tr>
      </tfoot>
    </table>

    {{-- Signatures --}}
    <table class="sign">
      <tr>
        <td><div class="line">Asst. Manager</div></td>
        <td><div class="line">Manager</div></td>
        <td><div class="line">AGM</div></td>
        <td><div class="line">Finance Director</div></td>
      </tr>
    </table>

    {{-- Footer --}}
    <table class="foot">
      <tr>
        <td style="text-align:left;">{{ $report['meta']['company_name'] }} · {{ $report['title'] }} · {{ $report['meta']['statement_date'] }}</td>
        <td style="text-align:right;">Generated {{ $report['meta']['generated_at'] }}@if(!empty($report['meta']['generated_by'])) by {{ $report['meta']['generated_by'] }}@endif</td>
      </tr>
    </table>

  </div>
</body>
</html>
