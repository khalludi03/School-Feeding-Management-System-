<?php

namespace App\Http\Controllers;

use App\Services\Form10Service;
use App\Services\SimpleXlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Mpdf\Mpdf as MpdfBase;

class Form10Controller extends Controller
{
    public function index(): View
    {
        return view('admin.form10.index');
    }

    public function show(Request $request, Form10Service $form10): View
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form10->forPeriod($start, $end)
            : $form10->forMonth($year, $month);

        return view('admin.form10.register', $data);
    }

    public function pdf(Request $request, Form10Service $form10): Response
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form10->forPeriod($start, $end)
            : $form10->forMonth($year, $month);

        $html = view('admin.form10.register', $data)->render();

        $mpdf = new MpdfBase;
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->SetAutoPageBreak(true, 15);

        $mpdf->WriteHTML($html);

        $filename = "form10-{$year}-{$month}.pdf";

        $pdfContent = $mpdf->Output($filename, 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => (string) strlen($pdfContent),
        ]);
    }

    public function export(Request $request, Form10Service $form10, SimpleXlsxWriter $xlsx): Response
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form10->forPeriod($start, $end)
            : $form10->forMonth($year, $month);

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

        $contents = $xlsx->write("form10-{$year}-{$month}.xlsx", $headers, $rows);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"form10-{$year}-{$month}.xlsx\"",
            'Content-Length' => (string) strlen($contents),
        ]);
    }

    private function parsePeriod(Request $request): array
    {
        $month = $request->query('month');
        $start = $request->query('start');
        $end = $request->query('end');

        if (is_string($month) && preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
            return [(int) $matches[1], (int) $matches[2], null, null];
        }

        if (is_string($start) && is_string($end)) {
            return [null, null, Carbon::parse($start), Carbon::parse($end)];
        }

        $now = Carbon::now();

        return [$now->year, $now->month, null, null];
    }
}
