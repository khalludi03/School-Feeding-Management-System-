<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<title>ফরম-১২ | বিদ্যালয়ের মাসিক স্টক প্রতিবেদন</title>
<style>
  :root {
    --ink: #111;
    --grid: #1a1a1a;
    --head: #e9e3e3;
  }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #fff; }
  body {
    color: #111;
    font-family: "Noto Sans Bengali", "SolaimanLipi", "Kalpurush", "Siyam Rupali", sans-serif;
    -webkit-font-smoothing: antialiased;
    text-rendering: geometricPrecision;
  }
  .page {
    width: 210mm;
    
    padding: 18mm 14mm;
    margin: 0 auto;
    position: relative;
    background: white;
  }
  .header {
    position: relative;
    text-align: center;
    margin-bottom: 7mm;
  }
  .form-number {
    float: right;
    right: 0;
    top: 0;
    border: 1px solid #111;
    padding: 7px 12px;
    font-size: 10px;
    font-weight: 600;
  }
  .header h1 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
  }
  .header h2 {
    margin: 3px 0 0;
    font-size: 10px;
    font-weight: 600;
  }
  .date {
    margin-top: 6px;
    font-size: 10px;
  }
  .school-info {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
  }
  .school-info td {
    border: 1px solid #111;
    height: 9mm;
    padding: 5px 8px;
    font-size: 10px;
    font-weight: 600;
    vertical-align: middle;
  }
  .school-info .half { width: 50%; }
  .school-info .student { width: 33.333%; }
  .stock-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-top: 4mm;
  }
  .stock-table th, .stock-table td {
    border: 1px solid #111;
    text-align: center;
    vertical-align: middle;
    padding: 3px;
  }
  .stock-table thead tr:first-child th {
    height: 10mm;
    background: #e9e3e3;
    font-size: 10px;
    font-weight: 700;
  }
  .stock-table thead tr:nth-child(2) th {
    height: 30mm;
    background: #e9e3e3;
    font-size: 10px;
    font-weight: 600;
    line-height: 1.5;
  }
  .stock-table tbody td {
    height: 9mm;
    font-size: 10px;
  }
  .stock-table tbody tr { page-break-inside: avoid; }
  .qty-cell { text-align: center; }
  .signature-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4mm;
  }
  .signature-table td {
    width: 50%;
    border: 1px solid #111;
    height: 27mm;
    padding: 6px;
    vertical-align: top;
    font-size: 10px;
    font-weight: 600;
    line-height: 1.8;
  }
  .baseline-note {
    margin-top: 3mm;
    font-size: 9.5pt;
    color: #555;
    text-align: center;
  }
  .projection-note {
    margin-top: 2mm;
    font-size: 9pt;
    color: #666;
    font-style: italic;
    text-align: right;
  }
  .actual-indicator {
    display: inline-block;
    border: 1px solid #111;
    padding: 0 3px;
    font-size: 8pt;
    margin-left: 2px;
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
  <a href="{{ route('admin.form12.pdf', [$school->id, 'month' => sprintf('%04d-%02d', $year, $month)]) }}" style="border:1px solid #222;background:white;color:#111;border-radius:4px;padding:7px 11px;font:600 13px/1.1 system-ui,sans-serif;text-decoration:none;" data-turbo="false">PDF</a>
</div>

<div class="page print-document">

  <div class="header">
    <div class="form-number">ফরম-১২</div>
    <h1>সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</h1>
    <h2>বিদ্যালয়ের মাসিক স্টক প্রতিবেদন</h2>
    <div class="date">
      মাস: {{ $month_name }} &nbsp;&nbsp;&nbsp; সাল: {{ $year_bangla }}
    </div>
  </div>

  <table class="school-info">
    <tr>
      <td class="half">বিদ্যালয়ের নাম: {{ $meta['school_name'] }}</td>
      <td class="half">ইএমআইএস কোড: {{ $meta['school_code'] }}</td>
    </tr>
    <tr>
      <td class="half">জেলা: {{ $meta['district'] ?: 'চট্টগ্রাম' }}</td>
      <td class="half">উপজেলা: {{ $meta['upazila'] ?: 'আনোয়ারা' }}</td>
    </tr>
    <tr>
      <td class="half">ইউনিয়ন: {{ $meta['union'] ?: '-' }}</td>
      <td class="half">ক্লাস্টার: {{ $meta['cluster'] ?: '-' }}</td>
    </tr>
    <tr>
      <td class="student">ছাত্র: {{ isset($enrolment) ? '–' : '–' }}</td>
      <td class="student">ছাত্রী: {{ isset($enrolment) ? '–' : '–' }}</td>
      <td class="student">মোট শিক্ষার্থী: {{ isset($enrolment) ? '–' : '–' }}</td>
    </tr>
  </table>

  @if($is_baseline)
    <div class="baseline-note">প্রথম মাসের কর্মসূচি শুরু হওয়ায় উদ্বোধনী স্টক = ০ (শূন্য)</div>
  @endif

  @if($is_partial)
    <div class="projection-note">* আজকের তারিখ পর্যন্ত প্রকৃত তথ্য। ভবিষ্যতে বিতরণ প্রক্ষেপণ করা হয়নি।</div>
  @endif

  <table class="stock-table" aria-label="বিদ্যালয়ের মাসিক স্টক প্রতিবেদন">
    <thead>
      <tr>
        <th rowspan="2" style="width: 5%;">পণ্যের বিবরণ</th>
        @foreach($items as $item)
          <th colspan="3" style="width: 10.5%;">{{ $item['name'] }} {{ ($item['weight'] ?? null) ? "({$item['weight']} গ্রাম)" : '' }}</th>
        @endforeach
      </tr>
      <tr>
        @foreach($items as $item)
          <th style="width: 3.5%;">পূর্ববর্তী মাসের স্থিতিসহ গৃহীত</th>
          <th style="width: 3.5%;">বিতরণ</th>
          <th style="width: 3.5%;">মাস শেষে স্থিতি</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="width: 5%;">মোট</td>
        @foreach($items as $item)
          @php
            $li = $line_items[$loop->index] ?? null;
          @endphp
          <td class="qty-cell" style="width: 3.5%;">{{ $li['open_received_bangla'] ?? '০' }}</td>
          <td class="qty-cell" style="width: 3.5%;">{{ $li['distribution_bangla'] ?? '০' }}</td>
          <td class="qty-cell" style="width: 3.5%;">{{ $li['closing_bangla'] ?? '০' }}</td>
        @endforeach
      </tr>
    </tbody>
  </table>

  <table class="signature-table">
    <tr>
      <td>
        টিফিন ম্যানেজারের স্বাক্ষর ও সিল:
        <br><br><br>
        তারিখ:
        <br>
        মোবাইল নম্বর:
      </td>
      <td>
        প্রধান শিক্ষকের স্বাক্ষর ও সিল:
        <br><br><br>
        তারিখ:
        <br>
        মোবাইল নম্বর:
      </td>
    </tr>
    <tr>
      <td>
        সহকারী উপজেলা প্রাথমিক শিক্ষা অফিসারের স্বাক্ষর ও সিল:
        <br><br><br>
        তারিখ:
        <br>
        মোবাইল নম্বর:
      </td>
      <td>
        উপজেলা প্রাথমিক শিক্ষা অফিসারের স্বাক্ষর ও সিল:
        <br><br><br>
        তারিখ:
        <br>
        মোবাইল নম্বর:
      </td>
    </tr>
  </table>

</div>

</body>
</html>
