@extends('layouts.print', ['orientation' => 'A4 portrait'])
@section('title', 'ফরম-০৪: বিদ্যালয়ে গৃহীত খাদ্যের মাসিক প্রতিবেদন')

@section('print-actions')
  <a href="{{ route('admin.form4.pdf', [$school->id, 'month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-outline" data-turbo="false">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
    Download PDF
  </a>
  <a href="{{ route('admin.form4.export', [$school->id, 'month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-outline" data-turbo="false">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
    Export Excel
  </a>
@endsection

@section('content')
  <div class="doc-header">
    <div class="doc-form-box">ফরম-০৪</div>
    <h1 class="doc-title">সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</h1>
    <h2 class="doc-subtitle">বিদ্যালয়ে গৃহীত খাদ্যের মাসিক প্রতিবেদন</h2>
    <div class="doc-meta">
      মাস: {{ $month_name }} &nbsp;&nbsp;|&nbsp;&nbsp; সাল: {{ $year_bangla }}
    </div>
  </div>

  <table class="print-table text-left mb-4" style="border: 2px solid #000000;">
    <tr>
      <td style="width: 50%; font-weight: 700;">বিদ্যালয়ের নাম: {{ $meta['school_name'] }}</td>
      <td style="width: 50%; font-weight: 700;">স্কুল কোড: {{ $meta['school_code'] }}</td>
    </tr>
    <tr>
      <td style="font-weight: 700;">জেলা: {{ $meta['district'] }}</td>
      <td style="font-weight: 700;">উপজেলা: {{ $meta['upazila'] }}</td>
    </tr>
    <tr>
      <td style="font-weight: 700;">ইউনিয়ন: {{ $meta['union'] ?: '' }}</td>
      <td style="font-weight: 700;">ক্লাস্টার: {{ $meta['cluster'] ?: '' }}</td>
    </tr>
  </table>

  <table class="print-table striped">
    <colgroup>
      <col style="width: 7%;">
      <col style="width: 13%;">
      <col style="width: 15%;">
      <col style="width: 13%;">
      @foreach($items as $item)
        <col style="width: 10%;">
      @endforeach
      <col style="width: 13%;">
      <col style="width: 9%;">
    </colgroup>
    <thead>
      <tr>
        <th rowspan="2">ক্রমিক নং</th>
        <th rowspan="2">খাদ্য গ্রহণের তারিখ</th>
        <th rowspan="2">চালান নম্বর</th>
        <th rowspan="2">চালানের তারিখ</th>
        <th colspan="{{ count($items) }}" style="border-bottom: 2px solid #000000; background: #e5e7eb;">গৃহীত খাদ্যসামগ্রী (পিস/প্যাকেট)</th>
        <th rowspan="2">গ্রহণকারীর স্বাক্ষর</th>
        <th rowspan="2">মন্তব্য</th>
      </tr>
      <tr>
        @foreach($items as $item)
          <th>{{ $item['name'] }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($days as $idx => $day)
        <tr>
          <td class="text-center">{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($idx + 1)) }}</td>
          <td class="text-center">{{ $day['date_bangla'] }}</td>
          <td class="text-center">{{ $day['chalan_numbers'] }}</td>
          <td class="text-center">{{ $day['chalan_dates'] }}</td>
          @foreach($items as $item)
            @php $key = $item['key']; @endphp
            <td class="text-right">{{ $day['quantities'][$key] ?? '০' }}</td>
          @endforeach
          <td></td>
          <td></td>
        </tr>
      @endforeach
      <tr class="total-row">
        <td colspan="4" class="text-right" style="padding-right: 15px;">মোট</td>
        @foreach($items as $item)
          @php $key = $item['key']; $total = $total_qty[$key] ?? 0; @endphp
          <td class="text-right">{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) $total) }}</td>
        @endforeach
        <td></td>
        <td></td>
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
@endsection
