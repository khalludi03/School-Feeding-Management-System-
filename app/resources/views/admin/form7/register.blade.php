@extends('layouts.print', ['orientation' => 'A4 landscape'])
@section('title', 'ফরম-০৭: বিদ্যালয় পর্যায়ে সরবরাহের বিবরণী')

@section('print-actions')
  <a href="{{ route('admin.form7.pdf', ['month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-outline" data-turbo="false">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
    Download PDF
  </a>
  <a href="{{ route('admin.form7.export', ['month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-outline" data-turbo="false">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
    Export Excel
  </a>
@endsection

@section('content')
  <div class="doc-header">
    <div class="doc-form-box">ফরম-০৭</div>
    <h1 class="doc-title">সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</h1>
    <h2 class="doc-subtitle">উপজেলা প্রাথমিক শিক্ষা অফিস</h2>
    <div class="doc-meta">
      উপজেলা: আনোয়ারা &nbsp;&nbsp;|&nbsp;&nbsp; জেলা: {{ $district ?: 'চট্টগ্রাম' }}<br>
      @php
        $itemDescs = [];
        foreach ($items as $item) {
            $itemDescs[] = $item['name'];
        }
        $subject = $month_name . '-' . $year_code . ' মাসের ' . implode(', ', $itemDescs) . ' বিদ্যালয় পর্যায়ে সরবরাহের বিবরণী';
      @endphp
      <span style="font-weight: normal; margin-top: 4px; display: inline-block;">{{ $subject }}</span>
    </div>
  </div>

  <hr class="header-divider">
  <div class="mb-4 font-bold" style="font-size: 11pt;">সরবরাহকারী ঠিকাদারের নাম: {{ $contractor_name }}</div>

  <table class="print-table striped" aria-label="বিদ্যালয় পর্যায়ে সরবরাহের বিবরণী">
    <colgroup>
      <col style="width: 5%;">
      <col style="width: 20%;">
      <col style="width: 12%;">
      @for($i = 0; $i < count($items); $i++)
        <col style="width: 8%;">
        <col style="width: 13%;">
      @endfor
    </colgroup>
    <thead>
      <tr>
        <th rowspan="2">ক্রমিক<br>নং</th>
        <th rowspan="2">বিদ্যালয়ের নাম</th>
        <th rowspan="2">ইএমআইএস<br>কোড</th>
        @foreach($items as $item)
          <th colspan="2" style="border-bottom: 2px solid #000000; background: #e5e7eb;">{{ $item['name'] }}</th>
        @endforeach
      </tr>
      <tr>
        @foreach($items as $item)
          <th>মোট<br>চালানের<br>সংখ্যা</th>
          <th>মোট পরিমাণ<br>({{ $item['unit'] }})</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($schools as $idx => $school)
        <tr>
          <td class="text-center">{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($idx + 1)) }}</td>
          <td class="text-left font-bold">{{ $school['school_name'] }}</td>
          <td class="text-center" style="white-space: nowrap;">{{ $school['emis_code'] }}</td>
          @foreach($items as $item)
            @php $key = $item['key']; @endphp
            <td class="text-right">{{ $school['chalan_counts'][$key] ?? '০' }}</td>
            <td class="text-right">{{ $school['quantities'][$key] ?? '০' }}</td>
          @endforeach
        </tr>
      @endforeach
      <tr class="total-row">
        <td colspan="3" class="text-right" style="padding-right: 15px;">মোট</td>
        @foreach($items as $item)
          @php $key = $item['key']; @endphp
          <td class="text-right">{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($grand_totals['chalan_count'][$key] ?? 0)) }}</td>
          <td class="text-right">{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($grand_totals['quantity'][$key] ?? 0)) }}</td>
        @endforeach
      </tr>
    </tbody>
  </table>

  <div class="mt-4" style="border-top: 1px solid #ccc; padding-top: 4mm; font-size: 10pt; line-height: 1.8;">
    <p class="mb-4">
      উপর্যুক্ত বিবরণ অনুযায়ী অত্র উপজেলার {{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) count($schools)) }} টি সরকারি প্রাথমিক বিদ্যালয়ে
      {{ $month_name }}-{{ $year_code }} মাসের স্পেসিফিকেশন অনুযায়ী সরবরাহকৃত
      @foreach($items as $idx => $item)
        @php $key = $item['key']; @endphp
        {{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], number_format($grand_totals['quantity'][$key] ?? 0, 0, '', ',')) }} প্যাকেট {{ $item['name'] }}{{ $idx < count($items) - 1 ? ',' : '' }}
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

  <table style="width: 42%; margin-left: auto; margin-top: 15mm; font-size: 10pt; line-height: 2.2; text-align: left;">
    <tr><td style="font-weight: 700; text-align: center; padding-bottom: 18mm;">উপজেলা প্রাথমিক শিক্ষা অফিসারের</td></tr>
    <tr><td>স্বাক্ষর ও সিল:</td></tr>
    <tr><td>তারিখ:</td></tr>
    <tr><td>মোবাইল:</td></tr>
  </table>
@endsection
