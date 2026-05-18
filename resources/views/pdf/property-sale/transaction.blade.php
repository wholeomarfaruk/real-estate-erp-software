{{-- 
    resources/views/receipts/transaction.blade.php
    
    Usage from controller:
    
        return view('receipts.transaction', [
            'receipt' => [
                'no'             => 'SU-MR-2026-00184',
                'txn_id'         => 'TXN-9F2C-4A18',
                'issue_date'     => '18 May 2026',
                'reference_code' => 'PS-2025-0071',
                'txn_date'       => '18 May 2026',
                'payment_method' => 'Bank Transfer',
                'instrument_no'  => 'DBBL/24081902',
                'amount'         => 5000,
                'status'         => 'Received',
                'narration'      => 'Part payment received against Installment #04 of 24...',
                'amount_words'   => 'Bangladeshi Taka Five Thousand Only',
                'attachments_count' => 5,
                'generated_at'   => '18 May 2026, 14:32',
            ],
            'company' => [
                'name'    => 'Star Unity Development Ltd.',
                'tag'     => 'Real Estate · Development · Property Management',
                'address' => 'House 42, Road 11, Banani, Dhaka 1213, Bangladesh',
                'phone'   => '+880 1700 000 000',
                'email'   => 'accounts@starunity.com.bd',
                'website' => 'www.starunity.com.bd',
                'logo_initial' => 'S',
                'bank_account' => 'Sales Collection — BDT',
                'bank_name'    => 'Dutch-Bangla Bank, Banani Br.',
                'bank_ac_no'   => '105.110.000XXXX',
            ],
            'customer' => [
                'name'    => 'Mr. Anisur Rahman Chowdhury',
                'id'      => 'CUS-001284',
                'phone'   => '+880 1711 234 567',
                'address' => 'Flat 5A, House 18, Road 7, Dhanmondi, Dhaka 1209',
            ],
            'payer' => [
                'is_customer' => false,  // true when customer pays directly
                'name'        => 'Mrs. Sabina Rahman',
                'phone'       => '+880 1812 987 654',
            ],
            'property' => [
                'name'       => 'Star Unity Heights',
                'address'    => 'Plot 12, Block C, Bashundhara R/A',
                'link_no'    => 'UNIT-A0704',
                'type'       => 'Apartment',
                'floor_unit' => '7th Floor, A-4',
                'size'       => '1,420 sft',
            ],
            'installment' => [
                'current'         => 4,
                'total'           => 24,
                'amount'          => 20000,
                'paid_total'      => 14000,
                'due'             => 6000,
                'history'         => [
                    ['date' => '02 Mar 2026', 'method' => 'Cash',   'amount' => 1000, 'current' => false],
                    ['date' => '19 Mar 2026', 'method' => 'bKash',  'amount' => 1500, 'current' => false],
                    ['date' => '02 Apr 2026', 'method' => 'Cheque', 'amount' => 1000, 'current' => false],
                    ['date' => '21 Apr 2026', 'method' => 'Bank',   'amount' => 2000, 'current' => false],
                    ['date' => '02 May 2026', 'method' => 'Bank',   'amount' => 2000, 'current' => false],
                    ['date' => '10 May 2026', 'method' => 'Cash',   'amount' => 1500, 'current' => false],
                    ['date' => '18 May 2026', 'method' => 'Bank',   'amount' => 5000, 'current' => true],
                ],
            ],
        ]);
--}}
@php
    $bdt = fn($n) => 'BDT ' . number_format($n, 2);
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Transaction Receipt — {{ $receipt['no'] }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
<style>
  :root {
    --ink:        #14181f;
    --ink-2:      #2a2f3a;
    --muted:      #6b7280;
    --muted-2:    #9aa0a6;
    --rule:       #d9d9d9;
    --rule-soft:  #ececec;
    --paper:      #ffffff;
    --bg:         #e9e9ea;
    --accent:     #0d2a4a;
  }
  * { box-sizing: border-box; }
  html, body {
    margin: 0; padding: 0; background: var(--bg); color: var(--ink);
    font-family: "Inter", system-ui, -apple-system, sans-serif;
    font-size: 11px; line-height: 1.45;
    -webkit-font-smoothing: antialiased;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  .workspace { min-height: 100vh; padding: 40px 20px 80px; display: flex; flex-direction: column; align-items: center; gap: 24px; }
  .sheet {
    width: 210mm; min-height: 297mm; max-height: 297mm;
    background: var(--paper);
    box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 12px 40px -8px rgba(0,0,0,0.18);
    padding: 13mm 14mm 11mm; display: flex; flex-direction: column;
    position: relative; overflow: hidden;
  }
  .sheet::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 6px; background: var(--accent); }

  .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--rule); }
  .brand { display: flex; gap: 12px; align-items: flex-start; }
  .brand-mark { width: 80px; height: 80px; border: 1.5px solid var(--accent); color: var(--accent); display: grid; place-items: center; font-family: "Instrument Serif", Georgia, serif; font-size: 24px; line-height: 1; letter-spacing: 0.5px; flex-shrink: 0; }
  .brand-name { font-family: "Instrument Serif", Georgia, serif; font-size: 19px; line-height: 1.1; color: var(--ink); letter-spacing: 0.2px; margin-bottom: 3px; }
  .brand-tag { font-size: 8.5px; color: var(--muted); letter-spacing: 0.6px; text-transform: uppercase; margin-bottom: 5px; }
  .brand-meta { font-size: 9.5px; color: var(--ink-2); line-height: 1.5; }
  .brand-meta .sep { color: var(--muted-2); margin: 0 5px; }
  .doc-id { text-align: right; min-width: 180px; }
  .doc-label { font-family: "Instrument Serif", Georgia, serif; font-size: 22px; color: var(--ink); line-height: 1; margin-bottom: 3px; }
  .doc-sub { font-size: 8.5px; letter-spacing: 0.6px; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
  .doc-id-grid { display: grid; grid-template-columns: auto auto; column-gap: 14px; row-gap: 4px; font-size: 10px; justify-content: end; }
  .doc-id-grid dt { color: var(--muted); letter-spacing: 0.3px; text-transform: uppercase; font-size: 8.5px; align-self: center; }
  .doc-id-grid dd { margin: 0; font-family: "JetBrains Mono", ui-monospace, monospace; font-weight: 500; color: var(--ink); font-size: 10.5px; text-align: right; }

  .parties { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0; margin-top: 10px; border: 1px solid var(--rule); }
  .party { padding: 9px 11px 10px; }
  .party + .party { border-left: 1px solid var(--rule); }
  .party-label { font-size: 8.5px; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
  .party-name { font-size: 11.5px; font-weight: 600; color: var(--ink); margin-bottom: 3px; }
  .party-line { font-size: 9.5px; color: var(--ink-2); line-height: 1.5; }
  .party-line strong { color: var(--muted); font-weight: 500; display: inline-block; min-width: 56px; }
  .party-flag { display: inline-block; background: #fff8e6; color: #a14b00; border: 1px solid #f0d9a0; font-size: 7.5px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; padding: 1px 5px; border-radius: 3px; margin-left: 6px; vertical-align: 1px; }

  .overview { display: grid; grid-template-columns: repeat(5, 1fr); margin-top: 10px; border: 1px solid var(--rule); }
  .ov { padding: 8px 10px; }
  .ov + .ov { border-left: 1px solid var(--rule); }
  .ov-label { font-size: 8.5px; letter-spacing: 0.8px; text-transform: uppercase; color: var(--muted); margin-bottom: 4px; }
  .ov-value { font-size: 11.5px; color: var(--ink); font-weight: 500; }
  .ov-value.mono { font-family: "JetBrains Mono", ui-monospace, monospace; font-weight: 500; font-size: 11px; }
  .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 9.5px; font-weight: 600; letter-spacing: 0.3px; border: 1px solid currentColor; background: rgba(31, 111, 67, 0.06); color: #1f6f43; }

  .section-title { display: flex; align-items: baseline; justify-content: space-between; margin: 12px 0 6px; }
  .section-title h2 { font-family: "Instrument Serif", Georgia, serif; font-weight: 400; font-size: 14px; margin: 0; color: var(--ink); }
  .section-title .hint { font-size: 9px; letter-spacing: 0.6px; text-transform: uppercase; color: var(--muted); }

  .property-table { width: 100%; border-collapse: collapse; border: 1px solid var(--rule); }
  .property-table th { background: #f7f7f8; text-align: left; font-size: 8.5px; letter-spacing: 0.8px; text-transform: uppercase; color: var(--muted); font-weight: 600; padding: 6px 10px; border-bottom: 1px solid var(--rule); }
  .property-table td { padding: 7px 10px; font-size: 10px; color: var(--ink); border-top: 1px solid var(--rule-soft); vertical-align: top; }
  .property-table td.mono { font-family: "JetBrains Mono", ui-monospace, monospace; font-size: 10px; }
  .property-table tr:first-child td { border-top: none; }

  .amount-block { display: grid; grid-template-columns: 1fr 320px; gap: 18px; margin-top: 10px; }
  .amount-note { border: 1px solid var(--rule); padding: 10px 12px; font-size: 9.5px; color: var(--ink-2); line-height: 1.55; }
  .amount-note .label { font-size: 8.5px; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
  .amount-note .in-words { font-family: "Instrument Serif", Georgia, serif; font-size: 12.5px; color: var(--ink); line-height: 1.35; }
  .attachments { margin-top: 10px; padding-top: 9px; border-top: 1px dashed var(--rule); display: flex; align-items: center; gap: 8px; }
  .att-icon { width: 22px; height: 22px; border: 1px solid var(--rule); border-radius: 4px; display: grid; place-items: center; color: var(--muted); flex-shrink: 0; }
  .att-text { font-size: 9.5px; color: var(--ink-2); letter-spacing: 0.2px; }
  .att-text .att-count { font-weight: 600; color: var(--ink); }
  .att-chips { margin-left: auto; display: flex; gap: 3px; }
  .att-chips .chip { width: 10px; height: 12px; background: #f0f1f3; border: 1px solid var(--rule); border-radius: 1.5px; position: relative; }
  .att-chips .chip::before { content: ""; position: absolute; top: 0; right: 0; width: 3px; height: 3px; background: #fff; border-left: 1px solid var(--rule); border-bottom: 1px solid var(--rule); }

  .totals { border: 1px solid var(--rule); }
  .totals-row { display: flex; justify-content: space-between; align-items: baseline; padding: 6px 12px; font-size: 10px; border-bottom: 1px solid var(--rule-soft); }
  .totals-row:last-child { border-bottom: none; }
  .totals-row .lbl { color: var(--muted); }
  .totals-row .val { font-family: "JetBrains Mono", ui-monospace, monospace; color: var(--ink); font-weight: 500; }
  .totals-row.grand { background: var(--accent); color: #fff; padding: 8px 12px; }
  .totals-row.grand .lbl { color: rgba(255,255,255,0.78); font-size: 10px; letter-spacing: 0.6px; text-transform: uppercase; }
  .totals-row.grand .val { color: #fff; font-size: 14px; font-weight: 600; }
  .totals-row.sub-head { background: #f7f7f8; padding: 5px 12px; }
  .totals-row.sub-head .lbl { font-size: 8.5px; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); font-weight: 600; }
  .totals-row.sub-head .val { font-size: 8.5px; letter-spacing: 0.4px; text-transform: uppercase; color: var(--muted-2); font-family: "Inter", sans-serif; font-weight: 500; }
  .totals-row.pay { padding: 4px 12px; font-size: 9.5px; }
  .totals-row.pay .lbl { color: var(--ink-2); }
  .totals-row.pay .lbl .dt { font-family: "JetBrains Mono", ui-monospace, monospace; color: var(--muted); font-size: 9.5px; margin-right: 2px; }
  .totals-row.pay.current { background: #fff8e6; border-left: 3px solid #d99500; padding-left: 9px; }
  .totals-row.pay.current .lbl, .totals-row.pay.current .val { color: #14181f; font-weight: 600; }
  .totals-row.pay.current .lbl .dt { color: #a14b00; }
  .totals-row.pay.current .now { display: inline-block; background: #d99500; color: #fff; font-size: 8px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; padding: 1px 5px; border-radius: 3px; margin-left: 4px; vertical-align: 1px; }
  .totals-row.sub-total { background: #f7f7f8; padding: 6px 12px; border-top: 1px solid var(--rule); }
  .totals-row.sub-total .lbl { color: var(--muted); font-size: 9.5px; letter-spacing: 0.6px; text-transform: uppercase; font-weight: 600; }
  .totals-row.sub-total .val { color: var(--ink); font-weight: 600; font-size: 11px; }
  .totals-row.grand.due { background: #fff; color: var(--ink); border-top: 2px solid var(--ink); padding: 9px 12px 10px; }
  .totals-row.grand.due .lbl { color: var(--ink); font-weight: 600; }
  .totals-row.grand.due .val { color: #8a1212; font-size: 15px; font-weight: 700; }

  .signatures { margin-top: auto; padding-top: 18px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 22px; }
  .sig { text-align: center; }
  .sig-line { border-top: 1px solid var(--ink-2); margin: 26px 8px 5px; }
  .sig-role { font-size: 10px; font-weight: 600; color: var(--ink); margin-bottom: 2px; }
  .sig-sub { font-size: 8.5px; letter-spacing: 0.6px; text-transform: uppercase; color: var(--muted); }

  .footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid var(--rule); display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; font-size: 8.5px; color: var(--muted); letter-spacing: 0.3px; }
  .footer .auto-gen { max-width: 60%; line-height: 1.55; }
  .footer .auto-gen em { font-style: italic; color: var(--muted-2); }
  .footer .stamp { text-align: right; font-family: "JetBrains Mono", ui-monospace, monospace; color: var(--muted-2); font-size: 8.5px; line-height: 1.5; }

  .actions { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; background: #14181f; color: #fff; padding: 8px; border-radius: 999px; box-shadow: 0 12px 36px rgba(0,0,0,0.25); z-index: 50; }
  .actions button { background: transparent; border: 0; color: #fff; font-family: inherit; font-size: 12px; font-weight: 500; padding: 8px 16px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
  .actions button:hover { background: rgba(255,255,255,0.08); }
  .actions button.primary { background: #fff; color: #14181f; }
  .actions button.primary:hover { background: #f0f0f0; }

  @page { size: A4; margin: 0; }
  @media print {
    html, body { background: #fff; }
    .workspace { padding: 0; }
    .actions { display: none !important; }
    .sheet { box-shadow: none; width: 210mm; min-height: 297mm; page-break-after: always; }
  }
</style>
</head>
<body>
  <div class="workspace">
    <div class="sheet">

      <header class="header">
        <div class="brand">
          <div class="brand-mark">
            @if(!empty($company['logo']))
              <img src="{{ asset($company['logo']) }}" alt="{{ $company['name'] }}" style="width:100%;height:100%;object-fit:contain;padding:4px;">
            @else
              {{ $company['logo_initial'] }}
            @endif
          </div>
          <div>
            <div class="brand-name">{{ $company['name'] }}</div>
            <div class="brand-tag">{{ $company['tag'] }}</div>
            <div class="brand-meta">
              {{ $company['address'] }}<br />
              <span>{{ $company['phone'] }}</span>
              <span class="sep">·</span>
              <span>{{ $company['email'] }}</span><br />
           
              <span>{{ $company['website'] }}</span>
            </div>
          </div>
        </div>
        <div class="doc-id">
          <div class="doc-label">Receipt</div>
          <div class="doc-sub">Money Receipt · Property Sale/Rent</div>
          <dl class="doc-id-grid">
            <dt>Receipt No.</dt><dd>{{ $receipt['no'] }}</dd>
            <dt>Transaction ID</dt><dd>{{ $receipt['txn_id'] }}</dd>
            <dt>Issue Date</dt><dd>{{ $receipt['issue_date'] }}</dd>
            <dt>Reference</dt><dd>{{ $receipt['reference_code'] }}</dd>
          </dl>
        </div>
      </header>

      <section class="parties">
        <div class="party">
          <div class="party-label">Customer</div>
          <div class="party-name">{{ $customer['name'] }}</div>
          <div class="party-line"><strong>Customer ID</strong> {{ $customer['id'] }}</div>
          <div class="party-line"><strong>Phone</strong> {{ $customer['phone'] }}</div>
          <div class="party-line"><strong>Address</strong> {{ $customer['address'] }}</div>
        </div>
        <div class="party">
          <div class="party-label">
            Paid By
            @unless($payer['is_customer'])
              <span class="party-flag">on behalf of customer</span>
            @endunless
          </div>
          <div class="party-name">{{ $payer['is_customer'] ? $customer['name'] : $payer['name'] }}</div>
          <div class="party-line"><strong>Phone</strong> {{ $payer['is_customer'] ? $customer['phone'] : $payer['phone'] }}</div>
        </div>
        <div class="party">
          <div class="party-label">Received By</div>
          <div class="party-name">{{ $company['name'] }}</div>
          <div class="party-line"><strong>Via</strong> {{ $company['received_via'] }}</div>
          @if($company['bank_name'])
            <div class="party-line"><strong>Bank</strong> {{ $company['bank_name'] }}</div>
            <div class="party-line"><strong>A/C No.</strong> {{ $company['bank_ac_no'] }}</div>
            <div class="party-line"><strong>Account</strong> {{ $company['bank_holder'] }}</div>
          @elseif($company['account_name'])
            <div class="party-line"><strong>Account</strong> {{ $company['account_name'] }}</div>
          @endif
        </div>
      </section>

      <section class="overview">
        <div class="ov">
          <div class="ov-label">Transaction Date</div>
          <div class="ov-value">{{ $receipt['txn_date'] }}</div>
        </div>
        <div class="ov">
          <div class="ov-label">Payment Method</div>
          <div class="ov-value">{{ $receipt['payment_method'] }}</div>
        </div>
        <div class="ov">
          <div class="ov-label">Reference No.</div>
          <div class="ov-value mono">{{ $receipt['reference_no'] ?? '—' }}</div>
        </div>
        <div class="ov">
          <div class="ov-label">Amount</div>
          <div class="ov-value mono">{{ $bdt($receipt['amount']) }}</div>
        </div>
        <div class="ov">
          <div class="ov-label">Status</div>
          <div class="ov-value"><span class="pill">{{ $receipt['status'] }}</span></div>
        </div>
      </section>

      <div class="section-title">
        <h2>Property Details</h2>
        <div class="hint">Reference · PropertySale</div>
      </div>
      <table class="property-table">
        <thead>
          <tr>
            <th style="width: 34%">Property</th>
          
            <th style="width: 18%">Floor / Unit</th>
            <th style="width: 16%">Type</th>
            <th style="width: 14%">Size</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div style="font-weight: 600;">{{ $property['name'] }}</div>
              <div style="color: var(--muted); font-size: 9.5px; margin-top: 2px;">{{ $property['address'] }}</div>
            </td>
            <td>{!! $property['floor_unit'] !!}</td>
            <td>{{ $property['type'] }}</td>
            <td>{{ $property['size'] }}</td>
          </tr>
        </tbody>
      </table>

      <div class="section-title">
        <h2>{{ $installment['label'] }}</h2>
        <div class="hint">Contract {{ $receipt['reference_code'] }}</div>
      </div>
      <div class="amount-block">
        <div class="amount-note">
          <div class="label">Amount in Words</div>
          <div class="in-words">{{ $receipt['amount_words'] }}</div>
          <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--rule);">
            <div class="label">Narration</div>
            {{ $receipt['narration'] }}
          </div>
          @if(($receipt['attachments_count'] ?? 0) > 0)
          <div class="attachments">
            <div class="att-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
            </div>
            <div class="att-text">
              <span class="att-count">{{ $receipt['attachments_count'] }} {{ $receipt['attachments_count'] === 1 ? 'file' : 'files' }}</span> attached
            </div>
            <div class="att-chips" aria-hidden="true">
              @for($i = 0; $i < min($receipt['attachments_count'], 5); $i++)
                <span class="chip"></span>
              @endfor
            </div>
          </div>
          @endif
        </div>
        <div class="totals">
          <div class="totals-row">
            <span class="lbl">{{ $installment['label'] }}</span>
            <span class="val">
              @if($installment['current'] && $installment['total'])
                {{ sprintf('%02d', $installment['current']) }} of {{ $installment['total'] }}
              @else
                —
              @endif
            </span>
          </div>
          <div class="totals-row"><span class="lbl">Amount due</span><span class="val">{{ $bdt($installment['amount']) }}</span></div>
          <div class="totals-row sub-head"><span class="lbl">Payment history</span><span class="val">{{ count($installment['history']) }} {{ count($installment['history']) === 1 ? 'entry' : 'entries' }}</span></div>
          @foreach($installment['history'] as $h)
            <div class="totals-row pay{{ $h['current'] ? ' current' : '' }}">
              <span class="lbl">
                <span class="dt">{{ $h['date'] }}</span> · {{ $h['method'] }}
                @if($h['current'])<span class="now">this txn</span>@endif
              </span>
              <span class="val">{{ $bdt($h['amount']) }}</span>
            </div>
          @endforeach
          <div class="totals-row sub-total"><span class="lbl">Total paid</span><span class="val">{{ $bdt($installment['paid_total']) }}</span></div>
          <div class="totals-row grand due"><span class="lbl">Due amount</span><span class="val">{{ $bdt($installment['due']) }}</span></div>
        </div>
      </div>

      <section class="signatures">
        <div class="sig">
          <div class="sig-line"></div>
          <div class="sig-role">Payer's Signature</div>
          <div class="sig-sub">{{ $payer['is_customer'] ? $customer['name'] : $payer['name'] }}</div>
        </div>
        <div class="sig">
          <div class="sig-line"></div>
          <div class="sig-role">Receiver's Signature</div>
          <div class="sig-sub">Accounts Department</div>
        </div>
        <div class="sig">
          <div class="sig-line"></div>
          <div class="sig-role">Authorised Signatory</div>
          <div class="sig-sub">{{ $company['name'] }}</div>
        </div>
      </section>

      <footer class="footer">
        <div class="auto-gen">
          <em>System-generated receipt — valid without physical signature. Please retain for your records.</em>
        </div>
        <div class="stamp">
          Generated {{ $receipt['generated_at'] }}<br />
          Page 1 of 1
        </div>
      </footer>
    </div>
  </div>

  <div class="actions" role="toolbar" aria-label="Receipt actions">
    <button class="primary" onclick="window.print()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
      Print / Save as PDF
    </button>
  </div>
</body>
</html>
