@extends('layouts.print', ['orientation' => 'A4 landscape'])
@section('title', 'ফরম-১৩: উপজেলা পর্যায়ের মাসিক স্টক প্রতিবেদন')

@section('print-actions')
  <a href="{{ route('admin.form13.pdf', ['month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-outline" data-turbo="false">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
    Download PDF
  </a>
  <a href="{{ route('admin.form13.export', ['month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-outline" data-turbo="false">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
    Export Excel
  </a>
@endsection

@section('content')
  <div class="doc-header">
    <div class="doc-form-box">ফরম-১৩</div>
    <h1 class="doc-title">সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</h1>
    <h2 class="doc-subtitle">উপজেলা পর্যায়ের মাসিক স্টক প্রতিবেদন</h2>
    <div class="doc-meta">
      মাস: {{ $month_name }} &nbsp;&nbsp;|&nbsp;&nbsp; সাল: {{ $year_bangla }}<br>
      <span style="font-weight: normal; margin-top: 4px; display: inline-block;">জেলা: {{ $district ?: 'চট্টগ্রাম' }} &nbsp;&nbsp;|&nbsp;&nbsp; উপজেলা: {{ $upazila ?: 'আনোয়ারা' }}</span>
    </div>
  </div>

  <hr class="header-divider">
  <div class="mb-2 font-bold" style="font-size: 11pt;">সরবরাহকারী ঠিকাদারের নাম: {{ $contractor_name }}</div>

  @if($has_projection)
    <div class="mb-2 text-left" style="font-size: 9pt; color: #555; font-style: italic;">* কিছু বিদ্যালয়ের তথ্য আজকের তারিখ পর্যন্ত প্রকৃত; ভবিষ্যতে বিতরণ প্রক্ষেপণ করা হয়নি।</div>
  @endif
  @if($has_incomplete)
    <div class="mb-4 text-left" style="font-size: 9pt; color: #c00; font-weight: 600;">! কিছু বিদ্যালয়ে মূল্য তথ্য অপূর্ণ — বিল সম্পূর্ণ হতে পারেনি।</div>
  @endif

  <table class="print-table striped" aria-label="উপজেলা পর্যায়ের মাসিক স্টক প্রতিবেদন">
    <colgroup>
      <col style="width: 4%;">
      <col style="width: 14%;">
      <col style="width: 6%;">
      @foreach($items as $item)
        <col style="width: 7.5%;">
        <col style="width: 7.5%;">
        <col style="width: 7.5%;">
      @endforeach
    </colgroup>
    <thead>
      <tr>
        <th rowspan="2">ক্রমিক<br>নং</th>
        <th rowspan="2">বিদ্যালয়ের নাম</th>
        <th rowspan="2">ইএমআইএস<br>কোড</th>
        @foreach($items as $item)
          <th colspan="3" style="border-bottom: 2px solid #000000; background: #e5e7eb;">
            {{ $item['name'] }}
          </th>
        @endforeach
      </tr>
      <tr>
        @foreach($items as $item)
          <th>পূর্ববর্তী মাসের<br>স্থিতিসহ<br>গৃহীত</th>
          <th>বিতরণ</th>
          <th>মাস শেষে<br>স্থিতি</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($school_rows as $idx => $row)
        <tr>
          <td class="text-center">{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($idx + 1)) }}</td>
          <td class="text-left font-bold">{{ $row['school_name'] }}</td>
          <td class="text-center">{{ $row['emis_code'] }}</td>
          @foreach($items as $item)
            @php $key = $item['key']; $id = $row['item_data'][$key] ?? null; @endphp
            <td class="text-right">{{ $id['open_received_bangla'] ?? '০' }}</td>
            <td class="text-right">{{ $id['distribution_bangla'] ?? '০' }}</td>
            <td class="text-right">{{ $id['closing_bangla'] ?? '০' }}</td>
          @endforeach
        </tr>
      @endforeach
      <tr class="total-row">
        <td colspan="3" class="text-right" style="padding-right: 15px;">মোট</td>
        @foreach($line_items as $li)
          <td class="text-right">{{ $li['open_received_bangla'] }}</td>
          <td class="text-right">{{ $li['distribution_bangla'] }}</td>
          <td class="text-right">{{ $li['closing_bangla'] }}</td>
        @endforeach
      </tr>
    </tbody>
  </table>
@endsection
