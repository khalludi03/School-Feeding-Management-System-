<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<title>ফরম-১৩</title>
<style>
  :root {
    --ink: #111;
    --grid: #1a1a1a;
    --head: #eeeeee;
  }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #fff; }
  body {
    color: #111;
    font-family: "Noto Sans Bengali", "SolaimanLipi", "Kalpurush", sans-serif;
    -webkit-font-smoothing: antialiased;
    text-rendering: geometricPrecision;
  }
  .page {
    width: 297mm;
    
    padding: 10mm 12mm;
    margin: 0 auto;
    background: white;
  }
  .header {
    position: relative;
    text-align: center;
    margin-bottom: 5mm;
  }
  .form-number {
    float: right;
    right: 0;
    top: -4px;
    width: 38mm;
    height: 17mm;
    border: 2px solid #111;
    
    
    font-size: 14px;
    font-weight: 700;
  }
  .header-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 5px;
  }
  .header-subtitle {
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 10px;
  }
  .date {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 6px;
  }
  .location {
    font-size: 14px;
    font-weight: 500;
  }
  .header-line {
    border: 0;
    border-top: 2px solid #111;
    margin-top: 8mm;
  }
  .supplier {
    margin: 5mm 0 3mm 5mm;
    font-size: 13px;
    font-weight: 700;
  }
  .projection-note {
    margin-bottom: 3mm;
    font-size: 9pt;
    color: #555;
    font-style: italic;
  }
  .incomplete-note {
    margin-bottom: 3mm;
    font-size: 9pt;
    color: #c00;
    font-weight: 600;
  }
  .stock-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }
  .stock-table th, .stock-table td {
    border: 1px solid #111;
    text-align: center;
    vertical-align: middle;
  }
  col.sl { width: 3%; }
  col.school { width: 10%; }
  col.emis { width: 3%; }
  col.prod { width: 3.8%; }
  .product-title {
    height: 8mm;
    background: #eeeeee;
    font-size: 8px;
    font-weight: 700;
  }
  .column-title {
    height: 20mm;
    background: #eeeeee;
    font-size: 8px;
    font-weight: 600;
    line-height: 1.45;
  }
  .main-heading {
    height: 20mm;
    background: #eeeeee;
    font-size: 9px;
    font-weight: 700;
  }
  .stock-table tbody td {
    height: 7mm;
    padding: 2px 3px;
    font-size: 9px;
  }
  .stock-table tbody tr { page-break-inside: avoid; }
  .school-name { text-align: left; padding-left: 5px; white-space: nowrap; }
  .number { white-space: nowrap; }
  .total-row td { font-weight: 700; background: #eeeeee; }
  .school-name-col { text-align: left; padding-left: 5px; }

  @media print {
    html, body { width: 297mm;  }
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
  <a href="{{ route('admin.form13.pdf', ['month' => sprintf('%04d-%02d', $year, $month)]) }}" style="border:1px solid #222;background:white;color:#111;border-radius:4px;padding:7px 11px;font:600 13px/1.1 system-ui,sans-serif;text-decoration:none;" data-turbo="false">PDF</a>
</div>

<div class="page print-document">

  <div class="header">
    <div class="form-number">ফরম-১৩</div>
    <div class="header-title">সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</div>
    <div class="header-subtitle">উপজেলা পর্যায়ের মাসিক স্টক প্রতিবেদন</div>
    <div class="date">মাস: {{ $month_name }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; সাল: {{ $year_bangla }}</div>
    <div class="location">জেলা: {{ $district ?: 'চট্টগ্রাম' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; উপজেলা: {{ $upazila ?: 'আনোয়ারা' }}</div>
    <hr class="header-line">
  </div>

  <div class="supplier">সরবরাহকারী ঠিকাদারের নাম: {{ $contractor_name }}</div>

  @if($has_projection)
    <div class="projection-note">* কিছু বিদ্যালয়ের তথ্য আজকের তারিখ পর্যন্ত প্রকৃত; ভবিষ্যতে বিতরণ প্রক্ষেপণ করা হয়নি।</div>
  @endif
  @if($has_incomplete)
    <div class="incomplete-note">! কিছু বিদ্যালয়ে মূল্য তথ্য অপূর্ণ — বিল সম্পূর্ণ হতে পারেনি।</div>
  @endif

  <table class="stock-table" aria-label="উপজেলা পর্যায়ের মাসিক স্টক প্রতিবেদন">
    <colgroup>
      <col class="sl">
      <col class="school">
      <col class="emis">
      @foreach($items as $item)
        <col class="prod">
        <col class="prod">
        <col class="prod">
      @endforeach
    </colgroup>
    <thead>
      <tr>
        <th rowspan="2" class="main-heading">ক্রমিক<br>নং</th>
        <th rowspan="2" class="main-heading">বিদ্যালয়ের নাম</th>
        <th rowspan="2" class="main-heading">ইএমআইএস<br>কোড</th>
        @foreach($items as $item)
          <th colspan="3" class="product-title">
            {{ $item['name'] }} {{ ($item['weight'] ?? null) ? "({$item['weight']} গ্রাম)" : '' }}
          </th>
        @endforeach
      </tr>
      <tr>
        @foreach($items as $item)
          <th class="column-title">পূর্ববর্তী মাসের<br>স্থিতিসহ<br>গৃহীত</th>
          <th class="column-title">বিতরণ</th>
          <th class="column-title">মাস শেষে<br>স্থিতি</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($school_rows as $idx => $row)
        <tr>
          <td>{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($idx + 1)) }}</td>
          <td class="school-name">{{ $row['school_name'] }}</td>
          <td class="number">{{ $row['emis_code'] }}</td>
          @foreach($items as $item)
            @php $key = $item['key']; $id = $row['item_data'][$key] ?? null; @endphp
            <td class="number">{{ $id['open_received_bangla'] ?? '০' }}</td>
            <td class="number">{{ $id['distribution_bangla'] ?? '০' }}</td>
            <td class="number">{{ $id['closing_bangla'] ?? '০' }}</td>
          @endforeach
        </tr>
      @endforeach
      <tr class="total-row">
        <td colspan="3">মোট</td>
        @foreach($line_items as $li)
          <td class="number">{{ $li['open_received_bangla'] }}</td>
          <td class="number">{{ $li['distribution_bangla'] }}</td>
          <td class="number">{{ $li['closing_bangla'] }}</td>
        @endforeach
      </tr>
    </tbody>
  </table>

</div>

</body>
</html>
