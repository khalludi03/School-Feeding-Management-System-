<?php

namespace App\Http\Controllers;

use App\Models\Form10Invoice;
use App\Services\Form10Service;
use App\Services\SimpleXlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Mpdf\Mpdf as MpdfBase;

class Form10Controller extends Controller
{
    public function index(): View
    {
        $latestInvoice = Form10Invoice::latest('id')->first();
        $nextInvoiceNo = $this->getNextInvoiceNo($latestInvoice);

        return view('admin.form10.index', compact('latestInvoice', 'nextInvoiceNo'));
    }

    private function getNextInvoiceNo(?Form10Invoice $latestInvoice): string
    {
        if (! $latestInvoice || ! preg_match('/^AN-(\d+)$/', $latestInvoice->invoice_no, $matches)) {
            return 'AN-00001';
        }
        $number = (int) $matches[1];

        return 'AN-'.str_pad((string) ($number + 1), 5, '0', STR_PAD_LEFT);
    }

    private function getInvoiceDetailsFromRequest(Request $request): array
    {
        return $request->only([
            'contract_number',
            'bank_account_name',
            'bank_account_number',
            'bank_name',
            'bank_branch',
            'bank_routing',
            'upeo_mobile',
        ]);
    }

    public function show(Request $request, Form10Service $form10): View
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $latestInvoice = Form10Invoice::latest('id')->first();
        $invoiceDetails = $this->getInvoiceDetailsFromRequest($request);
        $invoiceDetails['invoice_no'] = $this->getNextInvoiceNo($latestInvoice);
        $invoiceDetails['invoice_date'] = today()->toDateString();

        $data = $start && $end
            ? $form10->forPeriod($start, $end, $invoiceDetails)
            : $form10->forMonth($year, $month, $invoiceDetails);

        return view('admin.form10.register', $data);
    }

    private function storeInvoiceAndGetDetails(Request $request): array
    {
        $request->validate([
            'contract_number' => 'required|string',
            'month' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $latestInvoice = Form10Invoice::lockForUpdate()->latest('id')->first();
            $invoiceNo = $this->getNextInvoiceNo($latestInvoice);

            $invoiceDetails = $this->getInvoiceDetailsFromRequest($request);
            $invoiceDetails['invoice_no'] = $invoiceNo;
            $invoiceDetails['invoice_date'] = today()->toDateString();
            $invoiceDetails['month'] = $request->input('month');
            $invoiceDetails['created_by'] = $request->user()->id;

            Form10Invoice::create($invoiceDetails);

            return $invoiceDetails;
        });
    }

    public function pdf(Request $request, Form10Service $form10): Response
    {
        $invoiceDetails = $this->storeInvoiceAndGetDetails($request);
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form10->forPeriod($start, $end, $invoiceDetails)
            : $form10->forMonth($year, $month, $invoiceDetails);

        $html = view('admin.form10.register', array_merge($data, ['isPdf' => true]))->render();

        $mpdf = new MpdfBase([
            'format' => 'A4',
            'margin_left' => 17.7,
            'margin_right' => 18.2,
            'margin_top' => 8.5,
            'margin_bottom' => 9,
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->SetAutoPageBreak(true, 15);

        $mpdf->WriteHTML($html);

        $filename = "form10-{$year}-{$month}-{$invoiceDetails['invoice_no']}.pdf";

        $pdfContent = $mpdf->Output($filename, 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => (string) strlen($pdfContent),
        ]);
    }

    public function export(Request $request, Form10Service $form10, SimpleXlsxWriter $xlsx): Response
    {
        $invoiceDetails = $this->storeInvoiceAndGetDetails($request);
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form10->forPeriod($start, $end, $invoiceDetails)
            : $form10->forMonth($year, $month, $invoiceDetails);

        $headers = ['ক্রমিক নং', 'পণ্যের বিবরণ', 'ইউনিট', 'পরিমাণ', 'ইউনিট মূল্য (টাকা)', 'মোট (টাকা)'];

        $rows = [];
        foreach ($data['line_items'] as $idx => $item) {
            $rows[] = [
                $idx + 1,
                $item['name'],
                $item['unit'],
                $item['quantity_bangla'],
                $item['unit_price_bangla'] ?? '-',
                $item['total_bangla'],
            ];
        }

        $rows[] = [
            '', '', '', '', 'সর্বমোট (টাকা)', $data['grand_total_bangla'],
        ];

        $filename = "form10-{$year}-{$month}-{$invoiceDetails['invoice_no']}.xlsx";
        $contents = $xlsx->write($filename, $headers, $rows);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => (string) strlen($contents),
        ]);
    }

    private function parsePeriod(Request $request): array
    {
        $month = $request->query('month') ?? $request->input('month');
        $start = $request->query('start') ?? $request->input('start');
        $end = $request->query('end') ?? $request->input('end');

        if (! $start && ! $end && ! $month) {
            $month = Carbon::today()->format('Y-m');
        }

        if ($month && ! $start && ! $end) {
            $parts = explode('-', $month);
            $year = (int) $parts[0];
            $monthInt = (int) $parts[1];

            return [$year, $monthInt, null, null];
        }

        $startDate = $start ? Carbon::parse($start) : null;
        $endDate = $end ? Carbon::parse($end) : null;
        $year = $startDate ? (int) $startDate->format('Y') : (int) date('Y');
        $monthInt = $startDate ? (int) $startDate->format('m') : (int) date('m');

        return [$year, $monthInt, $startDate, $endDate];
    }
}
