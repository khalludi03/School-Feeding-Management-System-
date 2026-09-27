<?php

namespace App\Http\Controllers;

use App\Services\Form13Service;
use App\Services\SimpleXlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Mpdf\Mpdf as MpdfBase;

class Form13Controller extends Controller
{
    public function index(): View
    {
        return view('admin.form13.index');
    }

    public function show(Request $request, Form13Service $form13): View
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form13->forPeriod($start, $end)
            : $form13->forMonth($year, $month);

        return view('admin.form13.register', $data);
    }

    public function pdf(Request $request, Form13Service $form13): Response
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form13->forPeriod($start, $end)
            : $form13->forMonth($year, $month);

        $html = view('admin.form13.register', $data)->render();

        $mpdf = new MpdfBase;
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->SetAutoPageBreak(true, 15);

        $mpdf->WriteHTML($html);

        $filename = $start && $end
            ? "form13-{$start->format('Ymd')}-{$end->format('Ymd')}.pdf"
            : "form13-{$year}-{$month}.pdf";

        $pdfContent = $mpdf->Output($filename, 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => (string) strlen($pdfContent),
        ]);
    }

    public function export(Request $request, Form13Service $form13, SimpleXlsxWriter $xlsx): Response
    {
        [$year, $month, $start, $end] = $this->parsePeriod($request);

        $data = $start && $end
            ? $form13->forPeriod($start, $end)
            : $form13->forMonth($year, $month);

        $headers = ['ক্রমিক', 'বিদ্যালয়ের নাম', 'ইএমআইএস কোড'];
        foreach ($data['items'] as $item) {
            $headers[] = "{$item['name']} গৃহীত";
            $headers[] = "{$item['name']} বিতরণ";
            $headers[] = "{$item['name']} স্থিতি";
        }

        $rows = [];
        foreach ($data['school_rows'] as $idx => $row) {
            $line = [
                $idx + 1,
                $row['school_name'],
                $row['emis_code'],
            ];
            foreach ($data['items'] as $item) {
                $key = $item['key'];
                $id = $row['item_data'][$key] ?? null;
                $line[] = $id['open_received_bangla'] ?? '০';
                $line[] = $id['distribution_bangla'] ?? '০';
                $line[] = $id['closing_bangla'] ?? '০';
            }
            $rows[] = $line;
        }

        $totalRow = ['মোট', '', ''];
        foreach ($data['line_items'] as $li) {
            $totalRow[] = $li['open_received_bangla'];
            $totalRow[] = $li['distribution_bangla'];
            $totalRow[] = $li['closing_bangla'];
        }
        $rows[] = $totalRow;

        $filename = $start && $end
            ? "form13-{$start->format('Ymd')}-{$end->format('Ymd')}.xlsx"
            : "form13-{$year}-{$month}.xlsx";

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
