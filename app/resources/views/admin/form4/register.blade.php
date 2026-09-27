<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ফরম-০৪ | বিদ্যালয়ে গৃহীত খাদ্যের মাসিক প্রতিবেদন</title>
<style>
  
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #fff; }
  body {
    color: #111;
    font-family: "Noto Serif Bengali", "Noto Sans Bengali", "Nirmala UI", "Vrinda", serif;
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
    text-align: center;
  }
  .form-box {
    float: right;
    margin-top: 0;
    margin-right: 0.5mm;
    width: 29.5mm;
    height: 15.6mm;
    border: 0.6mm solid #111;
    display: table;
    text-align: center;
    font-size: 11.1pt;
    font-weight: 700;
  }
  .form-box span {
    display: table-cell;
    vertical-align: middle;
  }
  .main-title {
    padding-top: 1.5mm;
    font-size: 16.2pt;
    font-weight: 800;
    line-height: 1.2;
  }
  .subtitle {
    font-size: 12.4pt;
    font-weight: normal;
    margin-top: 3mm;
    line-height: 1.2;
  }
  .period {
    font-size: 11pt;
    margin-top: 5mm;
  }
  .school-info {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-bottom: 3mm;
    border-top: 0.35mm solid #111;
  }
  .school-info td {
    border: 0.35mm solid #111;
    height: 7mm;
    padding: 3px 6px;
    font-size: 9.5pt;
    font-weight: 700;
    vertical-align: middle;
  }
  .school-info .left { width: 50%; }
  .school-info .right { width: 50%; }
  .table-wrap { margin: 3.4mm 3.6mm 0; }
  .main-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }
  .main-table th,
  .main-table td {
    border: 0.35mm solid #111;
    text-align: center;
    vertical-align: middle;
  }
  .main-table thead th { background: #e9e3e3; font-weight: 700; }
  .main-table .top-header { height: 11mm; font-size: 9pt; line-height: 1.35; }
  .main-table .sub-header { height: 12mm; font-size: 8.5pt; line-height: 1.3; }
  .main-table .number-header { height: 7mm; font-size: 9pt; }
  .main-table tbody td { height: 6.9mm; padding: 1px 2px; font-size: 8.25pt; }
  .main-table tbody tr { page-break-inside: avoid; }
  .main-table .total-row td { background: #e9e3e3; height: 7mm; font-weight: 700; font-size: 9pt; }
  col.col-serial { width: 8.71mm; }
  col.col-date { width: 20.02mm; }
  col.col-challan { width: 23.50mm; }
  col.col-challan-date { width: 20.02mm; }
  col.col-product { width: 13.06mm; }
  col.col-sign { width: 29.60mm; }
  col.col-comment { width: 15.67mm; }
  .signature-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 3mm;
    margin-top: 0;
  }
  .signature-table .box {
    width: 50%;
    border: 0.35mm solid #111;
    padding: 8px 8px 14px 8px;
    font-size: 9.5pt;
    height: 20mm;
    vertical-align: top;
  }
  .signature-table .box .line { margin-top: 14px; }

  @media print {
    html, body { width: auto; height: auto; padding: 0; margin: 0; }
    .page { margin: 0; padding: 0; width: auto; min-height: 0; box-shadow: none; }
    .screen-actions { display: none; }
  }
  @media screen {
    body { background: #e7e7e7; padding: 18px 0; }
    .page { box-shadow: 0 2px 16px rgba(0,0,0,.16); }
    .screen-actions {
      display: block;
      gap: 8px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }
  }
</style>
</head>
<body>

<div class="screen-actions">
  <a href="{{ route('admin.form4.index') }}" class="text-sm font-semibold text-primary hover:underline">← Back</a>
  <x-ui.button href="{{ route('admin.form4.pdf', [$school->id, 'month' => sprintf('%04d-%02d', $year, $month)]) }}" size="sm" data-turbo="false">Download PDF</x-ui.button>
  <x-ui.button href="{{ route('admin.form4.export', [$school->id, 'month' => sprintf('%04d-%02d', $year, $month)]) }}" variant="outline" size="sm" data-turbo="false">Export Excel</x-ui.button>
  <button onclick="window.print()" class="rounded-lg border border-base-300 bg-white px-4 py-2 text-sm font-semibold text-base-content hover:bg-base-200" data-turbo="false">Print</button>
</div>

<div class="page">

  <section class="top">
    <div class="form-box"><span>ফরম-০৪</span></div>
    <div class="main-title">সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</div>
    <div class="subtitle">বিদ্যালয়ে গৃহীত খাদ্যের মাসিক প্রতিবেদন</div>
    <div class="period">মাস: {{ $month_name }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; সাল: {{ $year_bangla }}</div>
  </section>

  <table class="school-info">
    <tr>
      <td class="left">বিদ্যালয়ের নাম: {{ $meta['school_name'] }}</td>
      <td class="right">স্কুল কোড: {{ $meta['school_code'] }}</td>
    </tr>
    <tr>
      <td class="left">জেলা: {{ $meta['district'] }}</td>
      <td class="right">উপজেলা: {{ $meta['upazila'] }}</td>
    </tr>
    <tr>
      <td class="left">ইউনিয়ন: {{ $meta['union'] ?: '' }}</td>
      <td class="right">ক্লাস্টার: {{ $meta['cluster'] ?: '' }}</td>
    </tr>
  </table>

  <div class="table-wrap">
    <table class="main-table">
      <colgroup>
        <col class="col-serial">
        <col class="col-date">
        <col class="col-challan">
        <col class="col-challan-date">
        <col class="col-product">
        <col class="col-product">
        <col class="col-product">
        <col class="col-sign">
        <col class="col-comment">
      </colgroup>
      <thead>
        <tr>
          <th rowspan="2" class="top-header">ক্রমিক নং</th>
          <th rowspan="2" class="top-header">খাদ্য গ্রহণের তারিখ</th>
          <th rowspan="2" class="top-header">চালান নম্বর</th>
          <th rowspan="2" class="top-header">চালানের তারিখ</th>
          <th colspan="3" class="top-header">গৃহীত খাদ্যসামগ্রী (পিস/প্যাকেট)</th>
          <th rowspan="2" class="top-header">গ্রহণকারীর স্বাক্ষর</th>
          <th rowspan="2" class="top-header">মন্তব্য</th>
        </tr>
        <tr>
          @foreach($items as $item)
            <th class="sub-header">{{ $item['name'] }}<br>({{ $item['unit'] }})</th>
          @endforeach
        </tr>
        <tr>
          <th class="number-header">১</th>
          <th class="number-header">২</th>
          <th class="number-header">৩</th>
          <th class="number-header">৪</th>
          <th class="number-header">৫</th>
          <th class="number-header">৬</th>
          <th class="number-header">৭</th>
          <th class="number-header">৮</th>
          <th class="number-header">৯</th>
        </tr>
      </thead>
      <tbody>
        @foreach($days as $idx => $day)
          <tr>
            <td>{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($idx + 1)) }}</td>
            <td>{{ $day['date_bangla'] }}</td>
            <td>{{ $day['chalan_numbers'] }}</td>
            <td>{{ $day['chalan_dates'] }}</td>
            @foreach($items as $item)
              @php $key = $item['key']; @endphp
              <td>{{ $day['quantities'][$key] ?? '০' }}</td>
            @endforeach
            <td></td>
            <td></td>
          </tr>
        @endforeach
        <tr class="total-row">
          <td colspan="4">মোট</td>
          @foreach($items as $item)
            @php $key = $item['key']; $total = $total_qty[$key] ?? 0; @endphp
            <td>{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) $total) }}</td>
          @endforeach
          <td></td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>

  <table class="signature-table">
    <tr>
      <td class="box">
        টিফিন ম্যানেজারের স্বাক্ষর ও সিল:
        <div class="line">তারিখ:</div>
        <div class="line">মোবাইল নম্বর:</div>
      </td>
      <td class="box">
        প্রধান শিক্ষকের স্বাক্ষর ও সিল:
        <div class="line">তারিখ:</div>
        <div class="line">মোবাইল নম্বর:</div>
      </td>
    </tr>
    <tr>
      <td class="box">
        সহকারী উপজেলা প্রাথমিক শিক্ষা অফিসারের স্বাক্ষর ও সিল:
        <div class="line">তারিখ:</div>
        <div class="line">মোবাইল নম্বর:</div>
      </td>
      <td class="box">
        উপজেলা প্রাথমিক শিক্ষা অফিসারের স্বাক্ষর ও সিল:
        <div class="line">তারিখ:</div>
        <div class="line">মোবাইল নম্বর:</div>
      </td>
    </tr>
  </table>

</div>

</body>
</html>
