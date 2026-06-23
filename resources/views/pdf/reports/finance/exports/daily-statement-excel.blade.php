{{-- Daily Statement Report · Excel template --}}
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="utf-8">
@verbatim
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Daily Statement</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
<style>td, th { mso-number-format:"\@"; }</style>
@endverbatim
</head>
<body>
@php
  $n = fn($v) => (is_null($v) || $v === '') ? '–' : ($v == 0 ? '0' : number_format($v));
@endphp

<table border="0" cellspacing="0" cellpadding="6" style="border-collapse:collapse; font-family:Calibri, Arial, sans-serif; font-size:11px; color:#161616;">

  {{-- Header --}}
  <tr>
    <td colspan="8" style="border:none; padding-bottom:20px;">
      <div style="font-size:15px; font-weight:bold; margin-bottom:3px;">{{ $report['meta']['company_name'] }}</div>
      <div style="font-size:9px; color:#5f5f5f; line-height:1.5;">
        {{ $report['meta']['address'] }}<br>
        {{ $report['meta']['contact'] }}
      </div>
      <div style="font-size:11px; font-weight:bold; margin-top:8px;">{{ $report['title'] }}</div>
      <div style="font-size:9px; color:#5f5f5f; margin-top:3px;">
        <b>Statement Date:</b> {{ $report['meta']['statement_date'] }}<br>
        <b>Generated:</b> {{ $report['meta']['generated_at'] }}@if(!empty($report['meta']['generated_by'])) by {{ $report['meta']['generated_by'] }}@endif
      </div>
    </td>
  </tr>

  {{-- SECTION 1 · BANK ACCOUNTS LEDGER --}}
  <tr>
    <td colspan="8" style="border:none; height:10px;"></td>
  </tr>
  <tr>
    <td colspan="8" style="background:#f4f4f4; border:2px solid #161616; font-size:11px; font-weight:bold; text-align:center; padding:7px;">BANK ACCOUNTS LEDGER</td>
  </tr>
  <tr>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px;">S.N</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px;">Bank Name &amp; Account</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:right;">Opening</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:right;">Cash Deposit</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:right;">Chq/Online Deposit</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:right;">Cash Withdraw</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:right;">Chq/Online Transfer</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:right;">Closing</th>
  </tr>
  @foreach ($report['banks'] as $i => $b)
  <tr>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:center;">{{ $i + 1 }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px;"><b>{{ $b['name'] }}</b> ({{ $b['account'] }})</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:right;">{{ $n($b['opening']) }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:right;">{{ $n($b['cash_deposit']) }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:right;">{{ $n($b['online_deposit']) }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:right;">{{ $n($b['cash_withdraw']) }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:right;">{{ $n($b['online_transfer']) }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:right;">{{ $n($b['closing']) }}</td>
  </tr>
  @endforeach
  <tr style="background:#f4f4f4; font-weight:bold;">
    <td colspan="2" style="border:1px solid #161616; padding:7px; text-align:right;">TOTAL</td>
    <td style="border:1px solid #161616; padding:7px; text-align:right;">{{ $n($report['bank_totals']['opening']) }}</td>
    <td style="border:1px solid #161616; padding:7px; text-align:right;">{{ $n($report['bank_totals']['cash_deposit']) }}</td>
    <td style="border:1px solid #161616; padding:7px; text-align:right;">{{ $n($report['bank_totals']['online_deposit']) }}</td>
    <td style="border:1px solid #161616; padding:7px; text-align:right;">{{ $n($report['bank_totals']['cash_withdraw']) }}</td>
    <td style="border:1px solid #161616; padding:7px; text-align:right;">{{ $n($report['bank_totals']['online_transfer']) }}</td>
    <td style="border:1px solid #161616; padding:7px; text-align:right;">{{ $n($report['bank_totals']['closing']) }}</td>
  </tr>

  {{-- SECTION 2A · RECEIPTS --}}
  <tr>
    <td colspan="8" style="border:none; height:10px;"></td>
  </tr>
  <tr>
    <td colspan="8" style="background:#f4f4f4; border:2px solid #161616; font-size:11px; font-weight:bold; text-align:center; padding:7px;">DAILY RECEIPT PAYMENT STATEMENT</td>
  </tr>
  <tr>
    <td colspan="8" style="border:none; padding:10px 0 5px; font-size:8px; font-weight:bold; text-transform:uppercase;">Receipts</td>
  </tr>
  <tr>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px;">S.N</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px;">Account Name</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px;">Particulars</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:center;">MR-No</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:center;">Folio/No</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:right;">Cash</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:right;">Bank</th>
    <td style="border:none;"></td>
  </tr>
  @foreach ($report['receipts'] as $i => $r)
  <tr>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:center;">{{ $i + 1 }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px;">{{ $r['account'] }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px;">{{ $r['particulars'] ?? '' }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:center;">{{ $r['mr_no'] ?? '–' }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:center;">{{ $r['folio'] ?? '–' }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:right;">{{ $n($r['cash']) }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:right;">{{ $n($r['bank']) }}</td>
    <td style="border:none;"></td>
  </tr>
  @endforeach
  <tr style="background:#f4f4f4; font-weight:bold;">
    <td colspan="5" style="border:1px solid #161616; padding:7px; text-align:right;">TOTAL RECEIPT</td>
    <td style="border:1px solid #161616; padding:7px; text-align:right;">{{ $n($report['receipt_totals']['cash']) }}</td>
    <td style="border:1px solid #161616; padding:7px; text-align:right;">{{ $n($report['receipt_totals']['bank']) }}</td>
    <td style="border:none;"></td>
  </tr>

  {{-- SECTION 2B · PAYMENTS --}}
  <tr>
    <td colspan="8" style="border:none; padding:10px 0 5px; font-size:8px; font-weight:bold; text-transform:uppercase;">Payments</td>
  </tr>
  <tr>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px;">S.N</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px;">Account Name</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px;">Particulars</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:center;">Proj-No</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:center;">Folio/No</th>
    <th style="border:1px solid #161616; background:#f4f4f4; padding:7px; text-align:right;">Cash</th>
    <td colspan="2" style="border:none;"></td>
  </tr>
  @foreach ($report['payments'] as $i => $p)
  <tr>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:center;">{{ $i + 1 }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px;"><b>{{ $p['account'] }}</b></td>
    <td style="border:1px solid #c9c9c9; padding:6px;">{{ $p['particulars'] ?? '' }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:center;">{{ $p['proj_no'] ?? '–' }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:center;">{{ $p['folio'] ?? '–' }}</td>
    <td style="border:1px solid #c9c9c9; padding:6px; text-align:right;">{{ $n($p['cash']) }}</td>
    <td colspan="2" style="border:none;"></td>
  </tr>
  @endforeach
  <tr style="background:#f4f4f4; font-weight:bold;">
    <td colspan="5" style="border:1px solid #161616; padding:7px; text-align:right;">TOTAL PAYMENTS</td>
    <td style="border:1px solid #161616; padding:7px; text-align:right;">{{ $n($report['payment_totals']['cash']) }}</td>
    <td colspan="2" style="border:none;"></td>
  </tr>

  {{-- CLOSING --}}
  <tr style="background:#f4f4f4; font-weight:bold;">
    <td colspan="4" style="border:2px solid #161616; padding:7px; text-align:right;">CLOSING BALANCE</td>
    <td colspan="2" style="border:2px solid #161616; padding:7px;">Cash {{ number_format($report['closing']['cash']) }}<br>Bank {{ number_format($report['closing']['bank']) }}</td>
    <td colspan="2" style="border:none;"></td>
  </tr>

  {{-- Footer --}}
  <tr>
    <td colspan="8" style="border:none; height:10px;"></td>
  </tr>
  <tr>
    <td colspan="8" style="border-top:1px solid #c9c9c9; padding:7px; font-size:8px; color:#8a8a8a;">
      {{ $report['meta']['company_name'] }} · {{ $report['title'] }} · {{ $report['meta']['statement_date'] }}<br>
      Generated {{ $report['meta']['generated_at'] }}@if(!empty($report['meta']['generated_by'])) by {{ $report['meta']['generated_by'] }}@endif
    </td>
  </tr>

</table>

</body>
</html>
