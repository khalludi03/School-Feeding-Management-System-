@extends('layouts.print', ['orientation' => 'A4 portrait'])
@section('title', 'ফরম-১০: সরবরাহ বিল সংক্রান্ত বিবরণী')

@section('styles')
<style>
  .invoice-meta, .parties, .bank-info {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 3mm;
    font-size: 10pt;
  }
  .invoice-meta td, .parties td, .bank-info td {
    border: 1px solid #000000;
    padding: 4px 8px;
    vertical-align: middle;
  }
  .label, .section-label { font-weight: 700; width: 22%; background: #f9fafb; }
  .section-label { vertical-align: top; }
  .value { font-weight: 500; }
  
  .account-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
  .account-row span:last-child { font-weight: 700; }
  .recommendation { margin-top: 5mm; font-size: 10pt; line-height: 1.6; text-align: justify; }
</style>
@endsection

@section('print-actions')
  <a href="{{ route('admin.form10.pdf', ['month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-outline" data-turbo="false">Download PDF</a>
@endsection

@section('content')
  <div class="doc-header">
    <div class="doc-form-box">ফরম-১০</div>
    <h1 class="doc-title">সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি</h1>
    <h2 class="doc-subtitle">উপজেলা প্রাথমিক শিক্ষা অফিস</h2>
    <div class="doc-meta">
      উপজেলা: {{ $upazila ?: 'আনোয়ারা' }} &nbsp;&nbsp;|&nbsp;&nbsp; জেলা: {{ $district ?: 'চট্টগ্রাম' }}<br>
      <span style="font-weight: normal; margin-top: 4px; display: inline-block;">{{ $month_name }} {{ $year_code }} মাসের সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচির চালান ভিত্তিক প্রকৃত রসদ সরবরাহ বিল সংক্রান্ত বিবরণী</span>
    </div>
  </div>

  <table class="invoice-meta">
    <tr>
      <td class="label">বিলের নম্বর</td>
      <td class="value bangla-val">{{ $invoice_number }}</td>
      <td class="label">বিলের তারিখ</td>
      <td class="value bangla-val">{{ $invoice_date_bangla }}</td>
    </tr>
    <tr>
      <td class="label">চুক্তিপত্র নং</td>
      <td class="value bangla-val">{{ $contract_number ?: '-' }}</td>
      <td class="label">মোট বিদ্যালয় সংখ্যা</td>
      <td class="value bangla-val">{{ $school_count_bangla }}</td>
    </tr>
    <tr>
      <td class="label">মোট চালান সংখ্যা</td>
      <td class="value bangla-val" colspan="3">{{ $chalan_count_bangla }}টি চালান</td>
    </tr>
  </table>

  <table class="parties">
    <tr>
      <td class="section-label">প্রাপক</td>
      <td>
        <div style="font-weight: 700;">{{ $recipient['name'] }}</div>
        <div>{{ $recipient['org'] }}</div>
        <div>{{ $recipient['address'] }}</div>
        <div style="margin-top:1mm;">{{ $recipient['via'] }}</div>
      </td>
    </tr>
    <tr>
      <td class="section-label">সরবরাহকারী</td>
      <td>
        <div style="font-weight:700;">{{ $supplier_name }}</div>
        <div>ঠিকানা: বৈরাগ, উপজেলা: আনোয়ারা, জেলা: চট্টগ্রাম</div>
      </td>
    </tr>
  </table>

  <table class="print-table striped mt-4" aria-label="সরবরাহ বিলের বিবরণী">
    <colgroup>
      <col style="width: 8%;">
      <col style="width: 38%;">
      <col style="width: 12%;">
      <col style="width: 12%;">
      <col style="width: 15%;">
      <col style="width: 15%;">
    </colgroup>
    <thead>
      <tr>
        <th>ক্রমিক<br>নং</th>
        <th>পণ্যের বিবরণ</th>
        <th>ইউনিট</th>
        <th>পরিমাণ</th>
        <th>ইউনিট মূল্য<br>(টাকা)</th>
        <th>মোট মূল্য<br>(টাকা)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($line_items as $idx => $item)
        <tr>
          <td class="text-center">{{ str_replace(range(0,9), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], (string) ($idx + 1)) }}</td>
          <td class="text-left font-bold">
            {{ $item['name'] }}
          </td>
          <td class="text-center">{{ $item['unit'] }}</td>
          <td class="text-right">{{ $item['quantity_bangla'] }}</td>
          <td class="text-right">{{ $item['unit_price_bangla'] ?? '-' }}</td>
          <td class="text-right font-bold">{{ $item['total_bangla'] }}</td>
        </tr>
      @endforeach
      <tr class="total-row">
        <td colspan="3" class="text-right">সর্বমোট (কোটিশন অনুযায়ী)</td>
        <td class="text-right">{{ $form7_totals['banana_bread'] ?? '০' }}</td>
        <td></td>
        <td></td>
      </tr>
      <tr class="total-row" style="background: #e8e8e8;">
        <td colspan="4" class="text-right">প্রকৃত সরবরাহের সামগ্রিক বিল</td>
        <td class="text-center" style="font-size:9pt;">সর্বমোট (টাকা)</td>
        <td class="text-right" style="font-size:12pt;">{{ $grand_total_bangla }}/=</td>
      </tr>
    </tbody>
  </table>

  <table class="bank-info mt-4">
    <tr>
      <td class="section-label">ব্যাংক তথ্য</td>
      <td>
        <div class="account-row"><span>হিসাবের নাম:</span> <span>{{ $bank_info['account_name'] }}</span></div>
        <div class="account-row"><span>হিসাব নং:</span> <span>{{ $bank_info['account_number'] }}</span></div>
        <div class="account-row"><span>ব্যাংকের নাম:</span> <span>{{ $bank_info['bank_name'] }}</span></div>
        <div class="account-row"><span>শাখা:</span> <span>{{ $bank_info['branch'] }}</span></div>
        <div class="account-row"><span>রাউটিং নং:</span> <span>{{ $bank_info['routing'] }}</span></div>
      </td>
    </tr>
  </table>

  <div class="recommendation">
    <strong>সুপারিশ:</strong> উপজেলা পর্যায়ে সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচির আওতাধীন সকল বিদ্যালয়ে উপর্যুক্ত বিলের প্রকৃত রসদ সরবরাহ সম্পন্ন হয়েছে মর্মে প্রত্যয়ন পূর্বক অনুমোদনের জন্য সংশ্লিষ্ট কর্তৃপক্ষের বরাবরে উপস্থাপন করা হলো।
  </div>

  <table style="width: 100%; margin-top: 15mm; font-size: 10pt; line-height: 1.6;">
    <tr>
      <td style="width: 50%; text-align: center;">
        <div style="font-weight: 700; margin-bottom: 12mm;">সরবরাহকারী ঠিকাদার</div>
        <div style="border-top: 1px solid #000000; width: 70%; margin: 0 auto; padding-top: 1mm;">স্বাক্ষর ও সীল</div>
      </td>
      <td style="width: 50%; text-align: center;">
        <div style="font-weight: 700; margin-bottom: 12mm;">উপজেলা প্রাথমিক শিক্ষা অফিসার</div>
        <div style="border-top: 1px solid #000000; width: 70%; margin: 0 auto; padding-top: 1mm;">স্বাক্ষর ও সীল</div>
      </td>
    </tr>
  </table>
@endsection
