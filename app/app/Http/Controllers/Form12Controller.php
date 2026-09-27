<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\Form12Service;
use App\Services\SimpleXlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Mpdf\Mpdf as MpdfBase;

class Form12Controller extends Controller
{
    public function index(): View
    {
        $schools = School::query()
            ->where('is_active', true)
            ->orderBy('bangla_name')
            ->pluck('bangla_name', 'id');

        return view('admin.form12.index', [
            'schools' => $schools,
            'selectedSchool' => null,
            'selectedMonth' => null,
            'selectedYear' => null,
        ]);
    }

    public function show(Request $request, School $school, Form12Service $form12): View
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form12->forSchoolPeriod($school, $start, $end)
            : $form12->forSchoolMonth($school, $year, $month);

        return view('admin.form12.register', $data);
    }

    public function pdf(Request $request, School $school, Form12Service $form12): Response
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form12->forSchoolPeriod($school, $start, $end)
            : $form12->forSchoolMonth($school, $year, $month);

        $html = view('admin.form12.register', $data)->render();

        $mpdf = new MpdfBase([
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->SetAutoPageBreak(true, 15);

        $mpdf->WriteHTML($html);

        $filename = $start && $end
            ? "form12-{$school->code}-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf"
            : "form12-{$school->code}-{$year}-{$month}.pdf";

        $pdfContent = $mpdf->Output($filename, 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => (string) strlen($pdfContent),
        ]);
    }

    public function export(Request $request, School $school, Form12Service $form12, SimpleXlsxWriter $xlsx): Response
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form12->forSchoolPeriod($school, $start, $end)
            : $form12->forSchoolMonth($school, $year, $month);

        $headers = ['পণ্য', 'ইউনিট', 'পূর্ববর্তী স্থিতি', 'গৃহীত', 'বিতরণ', 'মাস শেষে স্থিতি'];

        $rows = [];
        foreach ($data['line_items'] as $item) {
            $rows[] = [
                $item['name'],
                $item['unit'],
                $item['opening_bangla'],
                $item['receipts_bangla'],
                $item['distribution_bangla'],
                $item['closing_bangla'],
            ];
        }

        $filename = $start && $end
            ? "form12-{$school->code}-{$start->format('Ymd')}-{$end->format('Ymd')}.xlsx"
            : "form12-{$school->code}-{$year}-{$month}.xlsx";

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
