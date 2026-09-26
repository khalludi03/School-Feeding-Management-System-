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

        $headers = ['School Code', 'School Name', 'Pupils'];
        foreach ($report['items'] as $item) {
            $headers[] = $item->name.' Demand';
            $headers[] = $item->name.' Delivered';
            $headers[] = $item->name.' Shortfall';
            $headers[] = $item->name.' Status';
        }

        $rows = [];
        foreach ($report['rows'] as $row) {
            $line = [
                $row['school']->code,
                $row['school']->bangla_name,
                $row['pupil_count'],
            ];

            foreach ($report['items'] as $item) {
                $itemKey = $item->item_key;
                $line[] = $row['demand'][$itemKey] ?? null;
                $line[] = $row['delivered'][$itemKey] ?? null;
                $line[] = $row['shortfall'][$itemKey] ?? null;
                $line[] = $this->statusLabel($row['status'][$itemKey] ?? 'not_submitted');
            }

            $rows[] = $line;
        }

        $totals = ['Upazila total', '', ''];
        foreach ($report['items'] as $item) {
            $itemKey = $item->item_key;
            $totals[] = $report['totals']['demand'][$itemKey] ?? null;
            $totals[] = $report['totals']['delivered'][$itemKey] ?? null;
            $totals[] = $report['totals']['shortfall'][$itemKey] ?? null;
            $totals[] = null;
        }
        $rows[] = $totals;

        $statusTotals = ['Status', '', ''];
        foreach ($report['items'] as $item) {
            $itemKey = $item->item_key;
            $statusTotals[] = null;
            $statusTotals[] = null;
            $statusTotals[] = null;
            $statusTotals[] = $this->statusSummary($report, $itemKey);
        }
        $rows[] = $statusTotals;

        $contents = $xlsx->write('daily-delivery-'.$date->toDateString().'.xlsx', $headers, $rows);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="daily-delivery-'.$date->toDateString().'.xlsx"',
            'Content-Length' => (string) strlen($contents),
        ]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'submitted' => 'Submitted',
            'planned' => 'Planned',
            'confirmed_shortfall' => 'Confirmed shortfall',
            'not_scheduled' => 'Not scheduled',
            'unknown_demand' => 'Unknown demand',
            default => 'Not submitted',
        };
    }

    private function statusSummary(array $report, string $itemKey): string
    {
        $shortage = $report['totals']['shortage'][$itemKey] ?? 0;
        $excess = $report['totals']['excess'][$itemKey] ?? 0;
        $net = $report['totals']['net_balance'][$itemKey] ?? 0;

        return sprintf('Shortage %d · Excess %d · Net %d', $shortage, $excess, $net);
    }

    private function requestedDate(Request $request): Carbon
    {
        $raw = $request->query('date');

        return is_string($raw) && Carbon::hasFormat($raw, 'Y-m-d')
            ? Carbon::parse($raw)->startOfDay()
            : Carbon::today();
    }
}
