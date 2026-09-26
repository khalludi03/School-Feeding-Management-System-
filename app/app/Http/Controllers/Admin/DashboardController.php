<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DailyReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DailyReportService $reports,
    ) {}

    public function __invoke(): View
    {
        $today = today();
        $report = $this->reports->forDate($today);

        $confirmedShortfalls = $this->confirmedShortfalls($report);
        $missingSubmissions = $this->missingSubmissions($report);

        $completion = [
            'submitted' => $report['totals']['entries_recorded'] ?? 0,
            'expected' => $report['totals']['schools'] ?? 0,
            'missing' => $report['totals']['entries_missing'] ?? 0,
            'complete' => $report['totals']['complete'] ?? true,
            'unknown_schools' => $report['totals']['demand_unknown_schools'] ?? [],
        ];

        return view('admin.dashboard', [
            'today' => $today,
            'report' => $report,
            'confirmedShortfalls' => $confirmedShortfalls,
            'missingSubmissions' => $missingSubmissions,
            'completion' => $completion,
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return list<array{school_code: string, school_name: string, item_key: string, item_name: string, demand: int, delivered: int, shortfall: int}>
     */
    private function confirmedShortfalls(array $report): array
    {
        if (! ($report['is_working_day'] ?? false)) {
            return [];
        }

        $list = [];
        foreach ($report['rows'] as $row) {
            if ($row['is_future'] ?? false) {
                continue;
            }
            foreach ($report['items'] as $item) {
                $itemKey = $item->item_key;
                $demand = $row['demand'][$itemKey] ?? null;
                $delivered = $row['delivered'][$itemKey] ?? null;
                $shortfall = $row['shortfall'][$itemKey] ?? null;

                if ($demand === null || $demand <= 0 || $delivered === null) {
                    continue;
                }

                $gap = $demand - (int) $delivered;
                if ($gap > 0) {
                    $list[] = [
                        'school_code' => $row['school']->code,
                        'school_name' => $row['school']->bangla_name,
                        'item_key' => $itemKey,
                        'item_name' => $item->name,
                        'demand' => (int) $demand,
                        'delivered' => (int) $delivered,
                        'shortfall' => $gap,
                    ];
                }
            }
        }

        return $list;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return list<array{school_code: string, school_name: string, items: list<array{item_key: string, item_name: string, demand: int}>}>
     */
    private function missingSubmissions(array $report): array
    {
        if (! ($report['is_working_day'] ?? false)) {
            return [];
        }

        $list = [];
        foreach ($report['rows'] as $row) {
            if ($row['is_future'] ?? false) {
                continue;
            }
            $missingItems = [];
            foreach ($report['items'] as $item) {
                $itemKey = $item->item_key;
                $demand = $row['demand'][$itemKey] ?? null;
                $status = $row['status'][$itemKey] ?? 'not_submitted';

                if ($status !== 'not_submitted' || $demand === null || $demand <= 0) {
                    continue;
                }

                $missingItems[] = [
                    'item_key' => $itemKey,
                    'item_name' => $item->name,
                    'demand' => (int) $demand,
                ];
            }

            if ($missingItems !== []) {
                $list[] = [
                    'school_code' => $row['school']->code,
                    'school_name' => $row['school']->bangla_name,
                    'items' => $missingItems,
                ];
            }
        }

        return $list;
    }
}
