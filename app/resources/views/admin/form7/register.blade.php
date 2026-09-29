@extends('layouts.print', ['orientation' => 'A4 landscape'])
@section('title', 'ফরম-০৭: বিদ্যালয় পর্যায়ে সরবরাহের বিবরণী')

@section('print-actions')
  <a href="{{ route('admin.form7.pdf', ['month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-outline" data-turbo="false">Download PDF</a>
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
            $w = $item['weight'] ?? null;
            $itemDescs[] = $item['name'] . ($w ? " ({$w} গ্রাম)" : '');
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
      <col style="width: 4%;">
      <col style="width: 18%;">
      <col style="width: 6%;">
      @for($i = 0; $i < count($items); $i++)
        <col style="width: 8%;">
        <col style="width: 10%;">
      @endfor
    </colgroup>
    <thead>
      <tr>
        <th rowspan="2">ক্রমিক<br>নং</th>
        <th rowspan="2">বিদ্যালয়ের নাম</th>
        <th rowspan="2">ইএমআইএস<br>কোড</th>
        @foreach($items as $item)
          @php $w = $item['weight'] ?? null; @endphp
          <th colspan="2" style="border-bottom: 2px solid var(--grid); background: #e5e7eb;">{{ $item['name'] }} {{ $w ? "({$w} গ্রাম)" : '' }}</th>
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
          <td class="text-center">{{ $school['emis_code'] }}</td>
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

  <table style="width: 42%; margin-left: auto; margin-top: 15mm; font-size: 10pt; line-height: 2.2; text-align: left;">
    <tr><td style="font-weight: 700; text-align: center; padding-bottom: 18mm;">উপজেলা প্রাথমিক শিক্ষা অফিসারের</td></tr>
    <tr><td>স্বাক্ষর ও সিল:</td></tr>
    <tr><td>তারিখ:</td></tr>
    <tr><td>মোবাইল:</td></tr>
  </table>
@endsection
