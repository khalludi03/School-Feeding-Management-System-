<?php

namespace App\Services;

use App\Models\FeedingCycle;
use App\Models\School;
use App\Models\SchoolParticipationPeriod;
use Carbon\Carbon;

class Form13Service
{
    private const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    private Form12Service $form12;

    public function __construct(Form12Service $form12)
    {
        $this->form12 = $form12;
    }

    public function forMonth(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        return $this->forPeriod($start, $end);
    }

    public function forPeriod(Carbon $start, Carbon $end): array
    {
        $year = (int) $start->format('Y');
        $month = (int) $start->format('n');

        $cycle = FeedingCycle::query()
            ->whereDate('starts_on', '<=', $end)
            ->orderByDesc('starts_on')
            ->first();

        $participatingSchoolIds = SchoolParticipationPeriod::query()
            ->whereDate('starts_on', '<=', $end)
            ->pluck('school_id')
            ->unique();

        $schools = School::query()
            ->whereIn('id', $participatingSchoolIds)
            ->whereHas('participationPeriods', fn ($q) => $q->whereDate('starts_on', '<=', $end))
            ->orderBy('bangla_name')
            ->get();

        $schoolRows = [];
        $grandTotals = [];

        foreach ($schools as $school) {
            $f12 = $this->form12->forSchoolPeriod($school, $start, $end);
            $schoolRows[] = $this->buildSchoolRow($school, $f12);

            foreach ($f12['line_items'] as $li) {
                $key = $li['key'];
                if (! isset($grandTotals[$key])) {
                    $grandTotals[$key] = ['opening' => 0, 'receipts' => 0, 'distribution' => 0, 'closing' => 0];
                }
                $grandTotals[$key]['opening'] += $li['opening'];
                $grandTotals[$key]['receipts'] += $li['receipts'];
                $grandTotals[$key]['distribution'] += $li['distribution'];
                $grandTotals[$key]['closing'] += $li['closing'];
            }
        }

        $itemsData = $schools->isNotEmpty()
            ? ($schoolRows[0]['items'] ?? [])
            : [];

        $lineItems = [];
        foreach ($itemsData as $itemData) {
            $key = $itemData['key'];
            $totals = $grandTotals[$key] ?? ['opening' => 0, 'receipts' => 0, 'distribution' => 0, 'closing' => 0];

            $lineItems[] = [
                'key' => $key,
                'name' => $itemData['name'],
                'unit' => $itemData['unit'],
                'weight' => $itemData['weight'] ?? null,
                'opening' => $totals['opening'],
                'opening_bangla' => $this->banglaNumber($totals['opening']),
                'receipts' => $totals['receipts'],
                'receipts_bangla' => $this->banglaNumber($totals['receipts']),
                'open_received' => $totals['opening'] + $totals['receipts'],
                'open_received_bangla' => $this->banglaNumber($totals['opening'] + $totals['receipts']),
                'distribution' => $totals['distribution'],
                'distribution_bangla' => $this->banglaNumber($totals['distribution']),
                'closing' => $totals['closing'],
                'closing_bangla' => $this->banglaNumber($totals['closing']),
            ];
        }

        $district = $schools->first()?->district ?? '';
        $upazila = $schools->first()?->upazila ?? '';
        $hasPartial = collect($schoolRows)->contains(fn ($r) => $r['is_partial']);
        $hasIncomplete = collect($schoolRows)->contains(fn ($r) => $r['is_incomplete']);

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $this->banglaMonth($month),
            'year_bangla' => $this->toBangla($year),
            'cycle' => $cycle,
            'contractor_name' => config('sfp.contractor_name', 'স্বদেশশ্রী'),
            'district' => $district,
            'upazila' => $upazila,
            'items' => $itemsData,
            'line_items' => $lineItems,
            'school_rows' => $schoolRows,
            'school_count' => $schools->count(),
            'school_count_bangla' => $this->toBangla($schools->count()),
            'grand_totals' => $grandTotals,
            'has_projection' => $hasPartial,
            'has_incomplete' => $hasIncomplete,
        ];
    }

    private function buildSchoolRow(School $school, array $f12Data): array
    {
        $itemKeys = collect($f12Data['items'])->pluck('key')->all();

        $items = [];
        foreach ($f12Data['line_items'] as $li) {
            $key = $li['key'];
            $items[$key] = [
                'open_received_bangla' => $li['open_received_bangla'] ?? $this->banglaNumber(($li['opening'] ?? 0) + ($li['receipts'] ?? 0)),
                'distribution_bangla' => $li['distribution_bangla'] ?? '০',
                'closing_bangla' => $li['closing_bangla'] ?? '০',
                'opening' => $li['opening'] ?? 0,
                'receipts' => $li['receipts'] ?? 0,
                'distribution' => $li['distribution'] ?? 0,
                'closing' => $li['closing'] ?? 0,
            ];
        }

        return [
            'school' => $school,
            'school_name' => $school->bangla_name,
            'emis_code' => $school->emis_code ?: $school->code,
            'items' => $f12Data['items'],
            'item_data' => $items,
            'is_baseline' => $f12Data['is_baseline'],
            'is_partial' => $f12Data['is_partial'],
            'is_incomplete' => $f12Data['is_incomplete'],
        ];
    }

    private function banglaMonth(int $month): string
    {
        return [
            1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ',
            4 => 'এপ্রিল', 5 => 'মে', 6 => 'জুন',
            7 => 'জুলাই', 8 => 'আগস্ট', 9 => 'সেপ্টেম্বর',
            10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
        ][$month] ?? '';
    }

    private function toBangla(int $number): string
    {
        if ($number === 0) {
            return '০';
        }

        return str_replace(range(0, 9), self::BANGLA_DIGITS, (string) $number);
    }

    private function banglaNumber(int $number): string
    {
        if ($number === 0) {
            return '০';
        }

        $formatted = number_format($number, 0, '', ',');

        return str_replace(range(0, 9), self::BANGLA_DIGITS, $formatted);
    }
}
