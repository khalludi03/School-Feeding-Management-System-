@extends('layouts.print', ['orientation' => 'A4 portrait'])
@section('title', 'ফরম-১২: বিদ্যালয়ের মাসিক স্টক প্রতিবেদন')

@section('print-actions')
  <a href="{{ route('admin.form12.pdf', [$school->id, 'month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-outline" data-turbo="false">Download PDF</a>
@endsection

@section('content')
  <div class="doc-header">
    <div class="doc-form-box">ফরম-১২</div>
    <h1 class="doc-title">সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</h1>
    <h2 class="doc-subtitle">বিদ্যালয়ের মাসিক স্টক প্রতিবেদন</h2>
    <div class="doc-meta">
      মাস: {{ $month_name }} &nbsp;&nbsp;|&nbsp;&nbsp; সাল: {{ $year_bangla }}
    </div>
  </div>

  <table class="print-table text-left mb-4" style="border: 2px solid #000000;">
    <tr>
      <td style="width: 50%; font-weight: bold;">বিদ্যালয়ের নাম: {{ $meta['school_name'] }}</td>
      <td style="width: 50%; font-weight: bold;">ইএমআইএস কোড: {{ $meta['school_code'] }}</td>
    </tr>
    <tr>
      <td style="font-weight: bold;">জেলা: {{ $meta['district'] ?: 'চট্টগ্রাম' }}</td>
      <td style="font-weight: bold;">উপজেলা: {{ $meta['upazila'] ?: 'আনোয়ারা' }}</td>
    </tr>
    <tr>
      <td style="font-weight: bold;">ইউনিয়ন: {{ $meta['union'] ?: '-' }}</td>
      <td style="font-weight: bold;">ক্লাস্টার: {{ $meta['cluster'] ?: '-' }}</td>
    </tr>
    <tr>
      <td colspan="2" style="padding: 0; border: none;">
        <table style="width: 100%; border-collapse: collapse;">
          <tr>
            <td style="width: 33.33%; font-weight: bold; border-right: 1px solid #000000; border-bottom: none; border-top: none; border-left: none;">ছাত্র: {{ isset($enrolment) ? '–' : '–' }}</td>
            <td style="width: 33.33%; font-weight: bold; border-right: 1px solid #000000; border-bottom: none; border-top: none; border-left: none;">ছাত্রী: {{ isset($enrolment) ? '–' : '–' }}</td>
            <td style="width: 33.33%; font-weight: bold; border: none;">মোট শিক্ষার্থী: {{ isset($enrolment) ? '–' : '–' }}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  @if($is_baseline)
    <div class="mb-4 text-center font-semibold text-gray-700" style="font-size: 10pt;">প্রথম মাসের কর্মসূচি শুরু হওয়ায় উদ্বোধনী স্টক = ০ (শূন্য)</div>
  @endif
  @if($is_partial)
    <div class="mb-2 text-right" style="font-size: 9pt; color: #555; font-style: italic;">* আজকের তারিখ পর্যন্ত প্রকৃত তথ্য। ভবিষ্যতে বিতরণ প্রক্ষেপণ করা হয়নি।</div>
  @endif

  <table class="print-table striped" aria-label="বিদ্যালয়ের মাসিক স্টক প্রতিবেদন">
    <thead>
      <tr>
        <th rowspan="2" style="width: 12%;">পণ্যের বিবরণ</th>
        @foreach($items as $item)
          <th colspan="3" style="border-bottom: 2px solid #000000; background: #e5e7eb;">{{ $item['name'] }} {{ ($item['weight'] ?? null) ? "({$item['weight']} গ্রাম)" : '' }}</th>
        @endforeach
      </tr>
      <tr>
        @foreach($items as $item)
          <th>পূর্ববর্তী মাসের স্থিতিসহ গৃহীত</th>
          <th>বিতরণ</th>
          <th>মাস শেষে স্থিতি</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="text-center font-bold">মোট</td>
        @foreach($items as $item)
          @php
            $li = $line_items[$loop->index] ?? null;
          @endphp
          <td class="text-right font-bold">{{ $li['open_received_bangla'] ?? '০' }}</td>
          <td class="text-right font-bold">{{ $li['distribution_bangla'] ?? '০' }}</td>
          <td class="text-right font-bold">{{ $li['closing_bangla'] ?? '০' }}</td>
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
@endsection
