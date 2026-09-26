<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\Form4Service;
use App\Services\SimpleXlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Mpdf\Mpdf;

class Form4Controller extends Controller
{
    public function index(): View
    {
        $schools = School::query()
            ->where('is_active', true)
            ->orderBy('bangla_name')
            ->pluck('bangla_name', 'id');

        return view('admin.form4.index', [
            'schools' => $schools,
            'selectedSchool' => null,
            'selectedMonth' => null,
            'selectedYear' => null,
        ]);
    }

    public function show(Request $request, School $school, Form4Service $form4): View
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form4->forSchoolPeriod($school, $start, $end)
            : $form4->forSchoolMonth($school, $year, $month);

        return view('admin.form4.register', $data);
    }

    public function pdf(Request $request, School $school, Form4Service $form4, Mpdf $mpdf): Response
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form4->forSchoolPeriod($school, $start, $end)
            : $form4->forSchoolMonth($school, $year, $month);

        $html = view('admin.form4.register', $data)->render();

        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->setFooter('{PAGENO}');

        $mpdf->WriteHTML($html);

        $filename = $start && $end
            ? "form4-{$school->code}-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf"
            : "form4-{$school->code}-{$year}-{$month}.pdf";

        $pdfContent = $mpdf->Output($filename, 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => (string) strlen($pdfContent),
        ]);
    }

    public function export(Request $request, School $school, Form4Service $form4, SimpleXlsxWriter $xlsx): Response
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form4->forSchoolPeriod($school, $start, $end)
            : $form4->forSchoolMonth($school, $year, $month);

        $headers = ['তারিখ', 'চালান নম্বর', 'চালানের তারিখ'];
        $itemHeaders = [];
        foreach ($data['items'] as $item) {
            $itemHeaders[] = "{$item['name']} ({$item['unit']})";
        }
        $headers = array_merge($headers, $itemHeaders, ['মন্তব্য']);

        $rows = [];
        foreach ($data['days'] as $idx => $day) {
            $serial = $form4->toBangla($idx + 1);
            $line = [
                $day['date_bangla'],
                $day['chalan_numbers'],
                $day['chalan_dates'],
            ];
            foreach ($data['items'] as $item) {
                $key = $item['key'];
                $line[] = $day['is_empty'] ? '-' : ($day['quantities'][$key] ?? '-');
            }
            $line[] = '-';
            $rows[] = $line;
        }

        $totalRow = ['মোট', '', ''];
        foreach ($data['items'] as $item) {
            $key = $item['key'];
            $totalRow[] = $form4->toBangla($data['total_qty'][$key] ?? 0);
        }
        $totalRow[] = '';
        $rows[] = $totalRow;

        $filename = $start && $end
            ? "form4-{$school->code}-{$start->format('Ymd')}-{$end->format('Ymd')}.xlsx"
            : "form4-{$school->code}-{$year}-{$month}.xlsx";

        $contents = $xlsx->write($filename, $headers, $rows);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
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
