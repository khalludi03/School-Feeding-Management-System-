<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<title>@yield('title', 'SFP Report')</title>
<style>
  :root {
    --ink: #111111;
    --grid: #000000;
    --head: #f8f9fa;
  }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #fff; }
  body {
    color: #111111;
    font-family: "Noto Sans Bengali", "SolaimanLipi", "Kalpurush", "Siyam Rupali", Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    text-rendering: geometricPrecision;
  }
  @if(!isset($isPdf) || !$isPdf)
    @media print {
      @page {
        size: {{ $orientation ?? 'A4 portrait' }};
        margin: 15mm 15mm 20mm 15mm;
      }
    }
  @endif
  .page-container {
    background: white;
    margin: 0 auto;
    position: relative;
  }

  @media print {
    html, body { width: auto; height: auto; padding: 0; margin: 0; background: white; }
    .page-container { margin: 0; padding: 0; width: auto; box-shadow: none; }
    .print-tools, .no-print { display: none !important; }
  }
  
  @media screen {
    body { background: #e2e8f0; padding: 20px 0; }
    .page-container {
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      padding: 15mm;
      margin-bottom: 20px;
    }
    .portrait {
      width: 210mm;
      min-height: 297mm;
    }
    .landscape {
      width: 297mm;
      min-height: 210mm;
    }
    
    .print-tools {
      position: fixed;
      top: 16px;
      right: 16px;
      z-index: 50;
      display: flex;
      gap: 12px;
      background: white;
      padding: 8px 12px;
      border-radius: 8px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      border: 1px solid #e2e8f0;
    }
    
    /* Shadcn-like button styling for screen */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      line-height: 1.25;
      padding: 8px 16px;
      transition: colors 0.15s ease;
      cursor: pointer;
      text-decoration: none;
    }
    .btn svg {
      width: 16px;
      height: 16px;
    }
    .btn-primary {
      background: #0f172a;
      color: #f8fafc;
      border: 1px solid #0f172a;
    }
    .btn-primary:hover { background: #1e293b; }
    
    .btn-outline {
      background: transparent;
      color: #0f172a;
      border: 1px solid #e2e8f0;
    }
    .btn-outline:hover { background: #f8fafc; }
  }

  /* Document Header block */
  .doc-header {
    text-align: center;
    margin-bottom: 6mm;
    position: relative;
  }
  .doc-form-box {
    position: absolute;
    top: 0;
    right: 0;
    border: 1px solid #000000;
    padding: 6px 12px;
    font-size: 11pt;
    font-weight: 700;
  }
  .doc-title {
    font-size: 16pt;
    font-weight: 800;
    margin: 0 0 2mm 0;
  }
  .doc-subtitle {
    font-size: 13pt;
    font-weight: 700;
    margin: 0 0 1.5mm 0;
  }
  .doc-meta {
    font-size: 11pt;
    font-weight: 600;
    line-height: 1.4;
  }
  
  hr.header-divider {
    border: 0;
    border-top: 2px solid #000000;
    margin: 4mm 0;
  }

  /* Print Tables */
  .print-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 4mm 0;
    table-layout: fixed;
  }
  .print-table th, .print-table td {
    border: 1px solid #000000;
    padding: 4px 5px;
    font-size: 9pt;
    vertical-align: middle;
  }
  
  .print-table thead {
    display: table-header-group;
  }
  
  .print-table thead th {
    background: #f8f9fa;
    font-weight: 700;
    text-align: center;
  }
  
  .print-table tbody tr {
    page-break-inside: avoid;
  }
  
  /* Subtle alternating row shade */
  .print-table.striped tbody tr:nth-child(even) {
    background-color: #f9fafb;
  }
  
  .print-table .total-row td {
    font-weight: 700;
    background: #f8f9fa;
  }
  
  /* Text alignments */
  .text-left { text-align: left !important; }
  .text-center { text-align: center !important; }
  .text-right { text-align: right !important; }
  .font-bold { font-weight: bold !important; }
  .nowrap { white-space: nowrap !important; }
  
  /* Utilities for spacing */
  .mb-2 { margin-bottom: 2mm; }
  .mb-4 { margin-bottom: 4mm; }
  .mt-4 { margin-top: 4mm; }
  
  /* Signature Blocks */
  .signature-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8mm;
    page-break-inside: avoid;
  }
  .signature-table td {
    width: 50%;
    border: 1px solid #000000;
    height: 25mm;
    padding: 6px;
    vertical-align: top;
    font-size: 9pt;
    font-weight: 600;
    line-height: 1.6;
  }

  /* Footer styling for mPDF */
  .footer-content {
    font-size: 8pt;
    color: #444;
    border-top: 1px solid #ccc;
    padding-top: 2mm;
    display: flex;
    justify-content: space-between;
  }
  
  @media screen {
    .mpdf-footer { display: none; }
  }
</style>
@yield('styles')
</head>
<body>

<div class="print-tools">
  <a href="javascript:history.back()" class="btn btn-outline" data-turbo="false">Back</a>
  @yield('print-actions')
  <button type="button" onclick="window.print()" class="btn btn-primary" data-turbo="false">Print</button>
</div>

@php
  $pageClass = ($orientation ?? 'A4 portrait') === 'A4 landscape' ? 'landscape' : 'portrait';
@endphp

<div class="page-container {{ $pageClass }}">
  @yield('content')
</div>

<!-- mPDF uses these HTML tags for headers/footers -->
<htmlpagefooter name="pageFooter" style="display:none" class="mpdf-footer">
  <table width="100%" style="border-top: 1px solid #ccc; padding-top: 2mm; font-size: 8pt; color: #444;">
    <tr>
      <td width="33%" align="left">Generated: {{ now()->format('d M Y, h:i A') }}</td>
      <td width="33%" align="center">@yield('footer-center')</td>
      <td width="33%" align="right">Page {PAGENO} of {nbpg}</td>
    </tr>
  </table>
</htmlpagefooter>

</body>
</html>
