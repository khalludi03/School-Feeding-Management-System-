<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<title>ফরম-০৭</title>
<style>
  @page { size: A4 portrait; margin: 0; }
  :root {
    --ink: #111;
    --grid: #1a1a1a;
    --head: #f1f1f1;
  }
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
    min-height: 297mm;
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
    position: absolute;
    top: 0;
    right: 0.5mm;
    width: 29.5mm;
    height: 15.6mm;
    border: 0.6mm solid #111;
    display: flex;
    align-items: center;
    justify-content: center;
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
  .upazila, .district {
    font-size: 12.2pt;
    line-height: 1.2;
  }
  .upazila { margin-top: 5.8mm; }
  .district { margin-top: 5.5mm; }
  .subject {
    margin-top: 6.9mm;
    font-size: 10.55pt;
    line-height: 1.55;
    font-weight: 800;
    white-space: nowrap;
  }
  .rule {
    height: 0;
    border-top: 0.55mm solid #111;
    margin-top: 0.8mm;
  }
  .contractor {
    height: 15.6mm;
    display: flex;
    align-items: center;
    padding-left: 7.6mm;
    font-size: 10.35pt;
    font-weight: 700;
    border-bottom: 0.28mm solid #dedede;
  }
  .table-wrap { margin: 3.4mm 3.6mm 0; }
  table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 8.45pt;
  }
  col.c1 { width: 4.9%; }
  col.c2 { width: 24.1%; }
  col.c3 { width: 10.5%; }
  col.c4 { width: 7.0%; }
  col.c5 { width: 10.8%; }
  col.c6 { width: 7.0%; }
  col.c7 { width: 10.8%; }
  col.c8 { width: 7.0%; }
  col.c9 { width: 10.9%; }
  th, td {
    border: 0.35mm solid #1a1a1a;
    text-align: center;
    vertical-align: middle;
    padding: 0;
  }
  thead th { background: #f1f1f1; font-weight: 800; }
  thead tr.group th {
    height: 8.5mm;
    font-size: 8.7pt;
    line-height: 1.25;
  }
  thead tr.sub th {
    height: 18.6mm;
    font-size: 7.9pt;
    line-height: 1.45;
  }
  thead .rowspan { font-size: 8.8pt; line-height: 1.45; }
  tbody tr { height: 9.48mm; page-break-inside: avoid; }
  tbody td {
    font-size: 8.25pt;
    line-height: 1.1;
  }
  tbody td:nth-child(2) { text-align: left; padding-left: 1.4mm; white-space: nowrap; }
  tbody td:nth-child(3) { font-size: 7.75pt; white-space: nowrap; }
  .bn-num { white-space: nowrap; }
  .total-row td { font-weight: 800; background: #f1f1f1; }
  .notes { margin-top: 6mm; border-top: 0.28mm solid #dedede; padding-top: 4mm; font-size: 9.5pt; line-height: 2; text-align: left; }
  .notes p { margin: 0 0 5mm 0; }
  .signature { width: 42%; margin-left: auto; margin-top: 22mm; font-size: 9.5pt; line-height: 2.2; }
  .signature .designation { font-weight: 700; text-align: center; margin-bottom: 18mm; }
  .signature .field { margin: 0; }

  @media print {
    html, body { width: 210mm; height: 297mm; }
    .page { margin: 0; }
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
</div>

<div class="page">
  <section class="top">
    <div class="form-box">ফরম-০৭</div>
    <div class="main-title">সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</div>
    <div class="office">উপজেলা প্রাথমিক শিক্ষা অফিস</div>
    <div class="upazila">উপজেলা: আনোয়ারা</div>
    <div class="district">জেলা: {{ $district ?: 'চট্টগ্রাম' }}</div>
    <div class="subject">
      @php
        $itemDescs = [];
        foreach ($items as $item) {
            $w = $item['weight'] ?? null;
            $itemDescs[] = $item['name'] . ($w ? " ({$w} গ্রাম)" : '');
        }
        $subject = $month_name . '-' . $year_code . ' মাসের ' . implode(', ', $itemDescs) . ' বিদ্যালয় পর্যায়ে সরবরাহের বিবরণী';
      @endphp
      {{ $subject }}
    </div>
  </section>

  <div class="rule"></div>
  <div class="contractor">সরবরাহকারী ঠিকাদারের নাম: {{ $contractor_name }}</div>

  <div class="table-wrap">
    <table aria-label="বিদ্যালয় পর্যায়ে সরবরাহের বিবরণী">
      <colgroup>
        <col class="c1">
        <col class="c2">
        <col class="c3">
        @for($i = 0; $i < count($items); $i++)
          <col class="c{{ 4 + $i * 2 }}">
          <col class="c{{ 5 + $i * 2 }}">
        @endfor
      </colgroup>
      <thead>
        <tr class="group">
          <th class="rowspan" rowspan="2">ক্রমিক<br>নং</th>
          <th class="rowspan" rowspan="2">বিদ্যালয়ের নাম</th>
          <th class="rowspan" rowspan="2">ইএমআইএস<br>কোড</th>
          @foreach($items as $item)
            @php $w = $item['weight'] ?? null; @endphp
            <th colspan="2">{{ $item['name'] }} {{ $w ? "({$w} গ্রাম)" : '' }}</th>
          @endforeach
        </tr>
        <tr class="sub">
          @foreach($items as $item)
            <th>মোট<br>চালানের<br>সংখ্যা</th>
            <th>মোট পরিমাণ<br>({{ $item['unit'] }})</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($schools as $idx => $school)
          <tr>
            <td>{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($idx + 1)) }}</td>
            <td>{{ $school['school_name'] }}</td>
            <td>{{ $school['emis_code'] }}</td>
            @foreach($items as $item)
              @php $key = $item['key']; @endphp
              <td>{{ $school['chalan_counts'][$key] ?? '০' }}</td>
              <td>{{ $school['quantities'][$key] ?? '০' }}</td>
            @endforeach
          </tr>
        @endforeach
        <tr class="total-row">
          <td colspan="3">মোট</td>
          @foreach($items as $item)
            @php $key = $item['key']; @endphp
            <td>{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($grand_totals['chalan_count'][$key] ?? 0)) }}</td>
            <td>{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($grand_totals['quantity'][$key] ?? 0)) }}</td>
          @endforeach
        </tr>
      </tbody>
    </table>
  </div>

  <div class="notes">
    <p>
      উপর্যুক্ত বিবরণ অনুযায়ী অত্র উপজেলার {{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) count($schools)) }} টি সরকারি প্রাথমিক বিদ্যালয়ে
      {{ $month_name }}-{{ $year_code }} মাসের স্পেসিফিকেশন অনুযায়ী সরবরাহকৃত
      @foreach($items as $idx => $item)
        @php $key = $item['key']; @endphp
        {{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], number_format($grand_totals['quantity'][$key] ?? 0, 0, '', ',')) }} প্যাকেট {{ $item['name'] }}@if($item['weight'])({{ $item['weight'] }} গ্রাম)@endif{{ $idx < count($items) - 1 ? ',' : '' }}
      @endforeach
      সরবরাহের চালানের মূল কপি অত্র কার্যালয়ে সংরক্ষিত আছে।
    </p>
    <p>
      এমতাবস্থায়, উক্ত সরবরাহকারী ঠিকাদারকে {{ $month_name }}-{{ $year_code }} মাসের
      @foreach($items as $idx => $item)
        @php $key = $item['key']; @endphp
        {{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], number_format($grand_totals['quantity'][$key] ?? 0, 0, '', ',')) }} প্যাকেট {{ $item['name'] }}@if($idx < count($items) - 1) ও @endif
      @endforeach
      সরবরাহের বিল পরিশোধ করার সুপারিশ করা হলো।
    </p>
  </div>

  <div class="signature">
    <div class="designation">উপজেলা প্রাথমিক শিক্ষা অফিসারের</div>
    <p class="field">স্বাক্ষর ও সিল:</p>
    <p class="field">তারিখ:</p>
    <p class="field">মোবাইল:</p>
  </div>

</div>

</body>
</html>
