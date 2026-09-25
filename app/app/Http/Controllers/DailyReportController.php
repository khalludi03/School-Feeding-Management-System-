<?php

namespace App\Http\Controllers;

use App\Services\DailyReportService;
use App\Services\SimpleXlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DailyReportController extends Controller
{
    public function index(Request $request, DailyReportService $reports): View
    {
        $date = $this->requestedDate($request);

        return view('reports.daily', [
            'date' => $date,
            'report' => $reports->forDate($date),
        ]);
    }

    public function export(Request $request, DailyReportService $reports, SimpleXlsxWriter $xlsx): Response
    {
        $date = $this->requestedDate($request);
        $report = $reports->forDate($date);

        $headers = ['School Code', 'School Name', 'Pupils', 'Entry Recorded'];
        foreach ($report['items'] as $item) {
            $headers[] = $item->name.' Demand';
            $headers[] = $item->name.' Delivered';
            $headers[] = $item->name.' Shortfall';
        }

        $rows = [];
        foreach ($report['rows'] as $row) {
            $line = [
                $row['school']->code,
                $row['school']->bangla_name,
                $row['pupil_count'],
                $row['entry_recorded'] ? 'Yes' : 'No',
            ];

            foreach ($report['items'] as $item) {
                $line[] = $row['demand'][$item->item_key] ?? null;
                $line[] = $row['delivered'][$item->item_key] ?? null;
                $line[] = $row['shortfall'][$item->item_key] ?? null;
            }

            $rows[] = $line;
        }

        $totals = ['Upazila total', '', '', $report['totals']['entries_recorded'].' of '.$report['totals']['schools']];
        foreach ($report['items'] as $item) {
            $totals[] = $report['totals']['demand'][$item->item_key] ?? null;
            $totals[] = $report['totals']['delivered'][$item->item_key] ?? null;
            $totals[] = $report['totals']['shortfall'][$item->item_key] ?? null;
        }
        $rows[] = $totals;

        $contents = $xlsx->write('daily-delivery-'.$date->toDateString().'.xlsx', $headers, $rows);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="daily-delivery-'.$date->toDateString().'.xlsx"',
            'Content-Length' => (string) strlen($contents),
        ]);
    }

    private function requestedDate(Request $request): Carbon
    {
        $raw = $request->query('date');

        return is_string($raw) && Carbon::hasFormat($raw, 'Y-m-d')
            ? Carbon::parse($raw)->startOfDay()
            : Carbon::today();
    }
}
