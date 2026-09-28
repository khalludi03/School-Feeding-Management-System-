<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<title>ফরম-১০</title>
<style>
  :root {
    --ink: #111;
    --grid: #1a1a1a;
    --head: #f1f1f1;
  }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #fff; }
  body {
    color: #111;
    font-family: Arial, Helvetica, sans-serif;
    -webkit-font-smoothing: antialiased;
    text-rendering: geometricPrecision;
  }
  .page {
    width: 210mm;
    
    padding: 8.5mm 18.2mm 9mm 17.7mm;
    margin: 0 auto;
    position: relative;
    background: white;
  }
  .top {
    position: relative;
    min-height: 71mm;
    text-align: center;
  }
  .form-box {
    float: right;
    top: 0;
    right: 0.5mm;
    width: 29.5mm;
    height: 15.6mm;
    border: 0.6mm solid #111;
    
    
    font-size: 11.1pt;
    line-height: 1;
    font-weight: 700;
    white-space: nowrap;
  }
  .main-title {
    padding-top: 1.5mm;
    font-size: 16.2pt;
    font-weight: 800;
    line-height: 1.2;
  }
  .office {
    margin-top: 4.1mm;
    font-size: 12.4pt;
    line-height: 1.2;
  }
  .subject {
    margin-top: 6.9mm;
    font-size: 10.55pt;
    line-height: 1.55;
    font-weight: 700;
    white-space: nowrap;
  }
  .rule {
    height: 0;
    border-top: 0.55mm solid #111;
    margin-top: 0.8mm;
  }
  .invoice-meta {
    margin-top: 3.2mm;
    width: 100%;
    border-collapse: collapse;
  }
  .invoice-meta td {
    border: 0.35mm solid #1a1a1a;
    padding: 1.2mm 2mm;
    font-size: 9.5pt;
    vertical-align: top;
  }
  .invoice-meta td.label {
    font-weight: 700;
    background: #f1f1f1;
    width: 28mm;
  }
  .invoice-meta td.value {
    background: white;
  }
  .invoice-meta .bangla-val {
    font-size: 10.5pt;
  }

  .parties {
    margin-top: 3.5mm;
    width: 100%;
    border-collapse: collapse;
  }
  .parties td {
    border: 0.35mm solid #1a1a1a;
    padding: 1.5mm 2.5mm;
    font-size: 9pt;
    line-height: 1.5;
    vertical-align: top;
  }
  .parties td.section-label {
    background: #f1f1f1;
    font-weight: 700;
    font-size: 9.5pt;
    text-align: center;
    width: 25mm;
  }
  .parties .recipient-name {
    font-weight: 700;
    font-size: 10pt;
  }

  .table-wrap { margin: 4mm 0 0; }
  table.invoice {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 9pt;
  }
  col.sl { width: 7%; }
  col.desc { width: 33%; }
  col.unit { width: 12%; }
  col.qty { width: 13%; }
  col.price { width: 17%; }
  col.total { width: 18%; }
  table.invoice th, table.invoice td {
    border: 0.35mm solid #1a1a1a;
    text-align: center;
    vertical-align: middle;
    padding: 1.5mm 2mm;
  }
  table.invoice thead th {
    background: #f1f1f1;
    font-weight: 700;
    font-size: 9pt;
  }
  table.invoice tbody td { font-size: 9pt; }
  table.invoice tbody tr { page-break-inside: avoid; }
  table.invoice tbody td:nth-child(2) { text-align: left; }
  table.invoice .qty-col, table.invoice .price-col, table.invoice .total-col {
    text-align: right;
    font-variant-numeric: tabular-nums;
  }
  .total-row td {
    font-weight: 700;
    background: #f1f1f1;
  }
  .total-row td:nth-child(2) { text-align: center; }

  .bank-info {
    margin-top: 3mm;
    width: 100%;
    border-collapse: collapse;
  }
  .bank-info td {
    border: 0.35mm solid #1a1a1a;
    padding: 1mm 2mm;
    font-size: 8.5pt;
    line-height: 1.6;
  }
  .bank-info td.section-label {
    background: #f1f1f1;
    font-weight: 700;
    font-size: 9pt;
    text-align: center;
    width: 25mm;
  }
  .bank-info .account-row {
    gap: 1mm;
  }
  .bank-info .account-row span:first-child {
    font-weight: 700;
    min-width: 35mm;
  }

  .recommendation {
    margin-top: 4mm;
    font-size: 9pt;
    line-height: 1.6;
    text-align: justify;
    padding: 0 1mm;
  }

  .signature-block {
    margin-top: 6mm;
    justify-content: space-between;
    padding: 0 5mm;
  }
  .sig-box {
    text-align: center;
    min-width: 45%;
  }
  .sig-box .title {
    font-size: 9pt;
    font-weight: 700;
    margin-bottom: 10mm;
  }
  .sig-line {
    border-top: 0.3mm solid #111;
    width: 70%;
    margin: 0 auto;
    padding-top: 1mm;
    font-size: 8.5pt;
  }

  @media print {
    html, body { width: auto; height: auto; padding: 0; margin: 0; }
    .page { margin: 0; padding: 0; width: auto; min-height: 0; box-shadow: none; }
    .print-tools { display: none; }
  }
  @media screen {
    body { background: #e7e7e7; padding: 18px 0; }
    .page { box-shadow: 0 2px 16px rgba(0,0,0,.16); }
  }
</style>
</head>
<body>

<div class="print-tools" style="position:fixed;top:12px;right:12px;z-index:10;display:flex;gap:8px;">
  <button type="button" onclick="window.print()" style="border:1px solid #222;background:white;color:#111;border-radius:4px;padding:7px 11px;font:600 13px/1.1 system-ui,sans-serif;cursor:pointer;" data-turbo="false">প্রিন্ট</button>
  <a href="{{ route('admin.form10.pdf', ['month' => sprintf('%04d-%02d', $year, $month)]) }}" style="border:1px solid #222;background:white;color:#111;border-radius:4px;padding:7px 11px;font:600 13px/1.1 system-ui,sans-serif;text-decoration:none;" data-turbo="false">PDF</a>
</div>

<div class="page print-document">
  <section class="top">
    <div class="form-box">ফরম-১০</div>
    <div class="main-title">সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</div>
    <div class="office">উপজেলা প্রাথমিক শিক্ষা অফিস</div>
    <div class="office" style="font-size:11pt;margin-top:2mm;">
      উপজেলা: {{ $upazila ?: 'আনোয়ারা' }} &nbsp;&nbsp; জেলা: {{ $district ?: 'চট্টগ্রাম' }}
    </div>
    <div class="subject">
      {{ $month_name }} {{ $year_code }} মাসের সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচির চালান ভিত্তিক প্রকৃত রসদ সরবরাহ বিল সংক্রান্ত বিবরণী
    </div>
  </section>

  <div class="rule"></div>

  <table class="invoice-meta">
    <tr>
      <td class="label">বিলের নম্বর</td>
      <td class="value bangla-val">{{ $invoice_number }}</td>
      <td class="label">বিলের তারিখ</td>
      <td class="value bangla-val">{{ $invoice_date_bangla }}</td>
    </tr>
    <tr>
      <td class="label">চুক্তিপত্র নং</td>
      <td class="value bangla-val">{{ $contract_number ?: '-' }}</td>
      <td class="label">মোট বিদ্যালয় সংখ্যা</td>
      <td class="value bangla-val">{{ $school_count_bangla }}</td>
    </tr>
    <tr>
      <td class="label">মোট চালান সংখ্যা</td>
      <td class="value bangla-val" colspan="3">{{ $chalan_count_bangla }}টি চালান</td>
    </tr>
  </table>

  <table class="parties" style="margin-top:2.5mm;">
    <tr>
      <td class="section-label">প্রাপক</td>
      <td>
        <div class="recipient-name">{{ $recipient['name'] }}</div>
        <div>{{ $recipient['org'] }}</div>
        <div>{{ $recipient['address'] }}</div>
        <div style="margin-top:1mm;">{{ $recipient['via'] }}</div>
      </td>
    </tr>
    <tr>
      <td class="section-label">সরবরাহকারী</td>
      <td>
        <div style="font-weight:700;">{{ $supplier_name }}</div>
        <div>ঠিকানা: বৈরাগ, উপজেলা: আনোয়ারা, জেলা: চট্টগ্রাম</div>
      </td>
    </tr>
  </table>

  <div class="table-wrap">
    <table class="invoice" aria-label="সরবরাহ বিলের বিবরণী">
      <colgroup>
        <col class="sl">
        <col class="desc">
        <col class="unit">
        <col class="qty">
        <col class="price">
        <col class="total">
      </colgroup>
      <thead>
        <tr>
          <th>ক্রমিক<br>নং</th>
          <th>পণ্যের বিবরণ</th>
          <th>ইউনিট</th>
          <th>পরিমাণ</th>
          <th>ইউনিট মূল্য<br>(টাকা)</th>
          <th>মোট মূল্য<br>(টাকা)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($line_items as $idx => $item)
          <tr>
            <td>{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($idx + 1)) }}</td>
            <td>
              {{ $item['name'] }}
              @if(($item['weight'] ?? null))
                <span style="font-size:8pt;color:#555;">({{ $item['weight'] }} গ্রাম)</span>
              @endif
            </td>
            <td>{{ $item['unit'] }}</td>
            <td class="qty-col">{{ $item['quantity_bangla'] }}</td>
            <td class="price-col">{{ $item['unit_price_bangla'] ?? '-' }}</td>
            <td class="total-col">{{ $item['total_bangla'] }}</td>
          </tr>
        @endforeach
        <tr class="total-row">
          <td colspan="3">সর্বমোট (কোটিশন অনুযায়ী)</td>
          <td class="qty-col">{{ $form7_totals['banana_bread'] ?? '০' }}</td>
          <td></td>
          <td></td>
        </tr>
        <tr class="total-row" style="background:#e8e8e8;">
          <td colspan="4">প্রকৃত সরবরাহের সামগ্রিক বিল</td>
          <td class="price-col" style="text-align:center;font-size:8.5pt;">সর্বমোট (টাকা)</td>
          <td class="total-col" style="font-size:11pt;">{{ $grand_total_bangla }}/=</td>
        </tr>
      </tbody>
    </table>
  </div>

  <table class="bank-info" style="margin-top:3mm;">
    <tr>
      <td class="section-label">ব্যাংক তথ্য</td>
      <td>
        <div class="account-row"><span>হিসাবের নাম:</span> <span>{{ $bank_info['account_name'] }}</span></div>
        <div class="account-row"><span>হিসাব নং:</span> <span>{{ $bank_info['account_number'] }}</span></div>
        <div class="account-row"><span>ব্যাংকের নাম:</span> <span>{{ $bank_info['bank_name'] }}</span></div>
        <div class="account-row"><span>শাখা:</span> <span>{{ $bank_info['branch'] }}</span></div>
        <div class="account-row"><span>রাউটিং নং:</span> <span>{{ $bank_info['routing'] }}</span></div>
      </td>
    </tr>
  </table>

  <div class="recommendation">
    <strong>সুপারিশ:</strong> উপজেলা পর্যায়ে সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচির আওতাধীন সকল বিদ্যালয়ে উপর্যুক্ত বিলের প্রকৃত রসদ সরবরাহ সম্পন্ন হয়েছে মর্মে প্রত্যয়ন পূর্বক অনুমোদনের জন্য সংশ্লিষ্ট কর্তৃপক্ষের বরাবরে উপস্থাপন করা হলো।
  </div>

  <table class="signature-block" style="width:100%; margin-top:6mm;">
    <tr>
      <td class="sig-box" style="width:50%; text-align:center;">
        <div class="title" style="font-size:9pt; font-weight:700; margin-bottom:10mm;">সরবরাহকারী ঠিকাদার</div>
        <div class="sig-line" style="border-top:0.3mm solid #111; width:70%; margin:0 auto; padding-top:1mm; font-size:8.5pt;">স্বাক্ষর ও সীল</div>
      </td>
      <td class="sig-box" style="width:50%; text-align:center;">
        <div class="title" style="font-size:9pt; font-weight:700; margin-bottom:10mm;">উপজেলা প্রাথমিক শিক্ষা অফিসার</div>
        <div class="sig-line" style="border-top:0.3mm solid #111; width:70%; margin:0 auto; padding-top:1mm; font-size:8.5pt;">স্বাক্ষর ও সীল</div>
      </td>
    </tr>
  </table>
</div>

</body>
</html>
