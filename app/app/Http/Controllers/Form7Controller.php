<?php

namespace App\Http\Controllers;

use App\Services\Form7Service;
use App\Services\SimpleXlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Mpdf\Mpdf;

class Form7Controller extends Controller
{
    public function index(): View
    {
        return view('admin.form7.index');
    }

    public function show(Request $request, Form7Service $form7): View
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form7->forPeriod($start, $end)
            : $form7->forMonth($year, $month);

        return view('admin.form7.register', $data);
    }

    public function pdf(Request $request, Form7Service $form7, Mpdf $mpdf): Response
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form7->forPeriod($start, $end)
            : $form7->forMonth($year, $month);

        $html = view('admin.form7.register', $data)->render();

        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDisplayMode('fullpage');

        $mpdf->WriteHTML($html);

        $filename = "form7-{$year}-{$month}.pdf";

        $pdfContent = $mpdf->Output($filename, 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => (string) strlen($pdfContent),
        ]);
    }

    public function export(Request $request, Form7Service $form7, SimpleXlsxWriter $xlsx): Response
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form7->forPeriod($start, $end)
            : $form7->forMonth($year, $month);

        $headers = ['ক্রমিক নং', 'বিদ্যালয়ের নাম', 'ইএমআইএস কোড'];
        foreach ($data['items'] as $item) {
            $headers[] = "{$item['name']} চালান সংখ্যা";
            $headers[] = "{$item['name']} পরিমাণ ({$item['unit']})";
        }

        $rows = [];
        foreach ($data['schools'] as $idx => $school) {
            $line = [
                $idx + 1,
                $school['school_name'],
                $school['emis_code'],
            ];
            foreach ($data['items'] as $item) {
                $key = $item['key'];
                $line[] = $school['chalan_counts'][$key] ?? '০';
                $line[] = $school['quantities'][$key] ?? '০';
            }
            $rows[] = $line;
        }

        $totalRow = ['মোট', '', ''];
        foreach ($data['items'] as $item) {
            $key = $item['key'];
            $totalRow[] = $data['grand_totals']['chalan_count'][$key] ?? 0;
            $totalRow[] = $data['grand_totals']['quantity'][$key] ?? 0;
        }
        $rows[] = $totalRow;

        $contents = $xlsx->write("form7-{$year}-{$month}.xlsx", $headers, $rows);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"form7-{$year}-{$month}.xlsx\"",
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
