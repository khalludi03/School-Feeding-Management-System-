<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<title>ফরম-০৪ | বিদ্যালয়ে গৃহীত খাদ্যের মাসিক প্রতিবেদন</title>
<style>
  @page { size: A4 portrait; margin: 0; }

  * { box-sizing: border-box; }

  html, body { margin: 0; padding: 0; background: #fff; }

  body {
    font-family: "Noto Sans Bengali", "SolaimanLipi", "Kalpurush", "Siyam Rupali", sans-serif;
    font-size: 10.5px;
    color: #111;
  }

  .page {
    width: 210mm;
    min-height: 297mm;
    padding: 8.5mm 17.7mm 9mm 17.7mm;
    margin: 0 auto;
    position: relative;
    background: white;
  }

  .form-tag {
    float: right;
    border: 1px solid #000;
    padding: 3px 10px;
    font-size: 11px;
    margin-top: -4px;
  }

  .title { text-align: center; margin-bottom: 6px; }
  .title h1 { font-size: 14px; margin: 0 0 2px 0; }
  .title h2 { font-size: 12px; font-weight: normal; margin: 0 0 4px 0; }
  .title .period { font-size: 11px; margin-bottom: 8px; }

  .meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    clear: both;
  }
  .meta-table td {
    border: 1px solid #000;
    padding: 3px 6px;
    font-size: 10.5px;
  }
  .meta-table td.label { font-weight: bold; width: 12%; }
  .meta-table td.value { width: 38%; }

  table.register {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }
  table.register thead { display: table-header-group; }
  table.register th,
  table.register td {
    border: 1px solid #000;
    padding: 2px 3px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
  }
  table.register thead th { font-weight: bold; background: #f2f2f2; }
  table.register tfoot { display: table-row-group; }
  table.register tfoot td { font-weight: bold; background: #f2f2f2; }

  col.sl          { width: 4%; }
  col.recv-date   { width: 10%; }
  col.chalan-no   { width: 10%; }
  col.chalan-date { width: 9%; }
  col.bun         { width: 9%; }
  col.egg         { width: 9%; }
  col.banana      { width: 7%; }
  col.biscuit     { width: 7%; }
  col.milk        { width: 8%; }
  col.signature   { width: 14%; }
  col.remarks     { width: 13%; }

  .signature-block {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 14px;
  }
  .signature-block .box {
    border: 1px solid #000;
    padding: 8px 8px 14px 8px;
    font-size: 10.5px;
  }
  .signature-block .box .line { margin-top: 14px; }

  tfoot, .signature-block { page-break-inside: avoid; }

  @media screen {
    .screen-actions {
      display: flex;
      gap: 8px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }
  }

  @media print {
    .page { padding: 0; }
  }
</style>
</head>
<body>

<div class="screen-actions">
  <a href="{{ route('admin.form4.index') }}" class="text-sm font-semibold text-primary hover:underline">← Back</a>
  <a href="{{ route('admin.form4.pdf', [$school->id, 'month' => sprintf('%04d-%02d', $year, $month)]) }}"
     class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-content hover:bg-primary">Download PDF</a>
  <a href="{{ route('admin.form4.export', [$school->id, 'month' => sprintf('%04d-%02d', $year, $month)]) }}"
     class="rounded-lg border border-base-300 bg-white px-4 py-2 text-sm font-semibold text-base-content hover:bg-base-200">Export Excel</a>
  <button onclick="window.print()" class="rounded-lg border border-base-300 bg-white px-4 py-2 text-sm font-semibold text-base-content hover:bg-base-200">Print</button>
</div>

<div class="page">
  <div class="form-tag">ফরম-০৪</div>

  <div class="title">
    <h1 style="padding:0 90px">সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</h1>
    <h2>বিদ্যালয়ে গৃহীত খাদ্যের মাসিক প্রতিবেদন</h2>
    <div class="period">মাস: {{ $month_name }}&nbsp;&nbsp; সাল: {{ $year_bangla }}</div>
  </div>

  <table class="meta-table">
    <tr>
      <td class="label">বিদ্যালয়ের নাম:</td>
      <td class="value">{{ $meta['school_name'] }}</td>
      <td class="label">স্কুল কোড:</td>
      <td class="value">{{ $meta['school_code'] }}</td>
    </tr>
    <tr>
      <td class="label">জেলা:</td>
      <td class="value">{{ $meta['district'] }}</td>
      <td class="label">উপজেলা:</td>
      <td class="value">{{ $meta['upazila'] }}</td>
    </tr>
    <tr>
      <td class="label">ইউনিয়ন:</td>
      <td class="value">{{ $meta['union'] ?: '' }}</td>
      <td class="label">ক্লাস্টার:</td>
      <td class="value">{{ $meta['cluster'] ?: '' }}</td>
    </tr>
  </table>

  <table class="register">
    <colgroup>
      <col class="sl">
      <col class="recv-date">
      <col class="chalan-no">
      <col class="chalan-date">
      <col class="bun">
      <col class="egg">
      <col class="banana">
      <col class="biscuit">
      <col class="milk">
      <col class="signature">
      <col class="remarks">
    </colgroup>
    <thead>
      <tr>
        <th rowspan="2">ক্রমিক নং</th>
        <th rowspan="2">খাদ্য গ্রহণের তারিখ</th>
        <th rowspan="2">চালান নম্বর</th>
        <th rowspan="2">চালানের তারিখ</th>
        <th colspan="3">গৃহীত খাদ্যসামগ্রী (পিস/প্যাকেট)</th>
        <th rowspan="2">ফর্টিফাইড বিস্কুট<br>(প্যাকেট)</th>
        <th rowspan="2">ইউএইচটি দুধ<br>(প্যাকেট)</th>
        <th rowspan="2">গ্রহণকারীর<br>স্বাক্ষর</th>
        <th rowspan="2">মন্তব্য</th>
      </tr>
      <tr>
        @foreach($items as $item)
          <th>{{ $item['name'] }}<br>({{ $item['unit'] }})</th>
        @endforeach
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
          <td>{{ $day['biscuit_qty'] }}</td>
          <td>{{ $day['milk_qty'] }}</td>
          <td></td>
          <td></td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4">মোট</td>
        @foreach($items as $item)
          @php $key = $item['key']; $total = $total_qty[$key] ?? 0; @endphp
          <td>{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) $total) }}</td>
        @endforeach
        <td>{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($total_qty['biscuit'] ?? 0)) }}</td>
        <td>{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($total_qty['milk'] ?? 0)) }}</td>
        <td></td>
        <td></td>
      </tr>
    </tfoot>
  </table>

  <div class="signature-block">
    <div class="box">
      টিফিন ম্যানেজারের স্বাক্ষর ও সিল:
      <div class="line">তারিখ:</div>
      <div class="line">মোবাইল নম্বর:</div>
    </div>
    <div class="box">
      প্রধান শিক্ষকের স্বাক্ষর ও সিল:
      <div class="line">তারিখ:</div>
      <div class="line">মোবাইল নম্বর:</div>
    </div>
    <div class="box">
      সহকারী উপজেলা প্রাথমিক শিক্ষা অফিসারের স্বাক্ষর ও সিল:
      <div class="line">তারিখ:</div>
      <div class="line">মোবাইল নম্বর:</div>
    </div>
    <div class="box">
      উপজেলা প্রাথমিক শিক্ষা অফিসারের স্বাক্ষর ও সিল:
      <div class="line">তারিখ:</div>
      <div class="line">মোবাইল নম্বর:</div>
    </div>
  </div>
</div>

</body>
</html>
