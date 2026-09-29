<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItemZeroConfirmation;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\SchoolParticipationPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Form7Service
{
    private const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    public function forMonth(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        return $this->forPeriod($start, $end);
    }

    public function forPeriod(Carbon $start, Carbon $end): array
    {
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

        $receipts = DeliveryReceipt::query()
            ->with(['items.item'])
            ->whereIn('school_id', $schools->pluck('id'))
            ->whereDate('delivery_date', '>=', $start)
            ->whereDate('delivery_date', '<=', $end)
            ->get();

        $zeroConfirmations = DeliveryReceiptItemZeroConfirmation::query()
            ->whereIn('school_id', $schools->pluck('id'))
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->get();

        $items = $cycle
            ? FeedingItem::query()->where('feeding_cycle_id', $cycle->id)->orderBy('sort_order')->get()
            : collect();

        $zeroConfirmSchoolItem = $zeroConfirmations
            ->groupBy(fn ($z) => $z->school_id.'-'.$z->feeding_item_id)
            ->map(fn ($group) => true);

        $receiptsBySchool = $receipts->groupBy('school_id');

        $schoolRows = [];
        foreach ($schools as $school) {
            $schoolReceipts = $receiptsBySchool->get($school->id, collect());
            $schoolRows[] = $this->buildSchoolRow($school, $schoolReceipts, $items, $zeroConfirmSchoolItem);
        }

        $itemsData = $items->isNotEmpty()
            ? $items->map(fn (FeedingItem $item): array => [
                'key' => $item->item_key,
                'name' => $item->name,
                'unit' => $item->unit,
                'weight' => $item->weight_grams,
            ])->all()
            : $this->defaultItems();

        $grandTotals = ['chalan_count' => [], 'quantity' => []];
        foreach ($itemsData as $item) {
            $grandTotals['chalan_count'][$item['key']] = 0;
            $grandTotals['quantity'][$item['key']] = 0;
        }
        foreach ($schoolRows as $row) {
            foreach ($itemsData as $item) {
                $key = $item['key'];
                $grandTotals['chalan_count'][$key] += $row['chalan_counts_raw'][$key] ?? 0;
                $grandTotals['quantity'][$key] += $row['quantities_raw'][$key] ?? 0;
            }
        }

        return [
            'year' => (int) $start->format('Y'),
            'month' => (int) $start->format('n'),
            'month_name' => $this->banglaMonth((int) $start->format('n')),
            'month_code' => $this->banglaMonthCode((int) $start->format('n')),
            'year_code' => $this->banglaYearCode((int) $start->format('Y')),
            'cycle' => $cycle,
            'contractor_name' => config('sfp.contractor_name', 'স্বদেশশ্রী'),
            'items' => $itemsData,
            'schools' => $schoolRows,
            'grand_totals' => $grandTotals,
            'district' => $schools->first()?->district ?? '',
        ];
    }

    private function buildSchoolRow(School $school, Collection $receipts, Collection $items, Collection $zeroConfirmMap): array
    {
        $itemKeys = $items->isNotEmpty()
            ? $items->pluck('item_key')->all()
            : ['banana_bread', 'boiled_egg', 'banana'];

        $chalanCounts = array_fill_keys($itemKeys, 0);
        $quantities = array_fill_keys($itemKeys, 0);

        foreach ($itemKeys as $key) {
            $relevantReceipts = $receipts->filter(fn (DeliveryReceipt $r) => $r->items->contains(fn ($line) => $line->item && $line->item->item_key === $key && $line->delivered_quantity > 0
            )
            );
            $chalanCounts[$key] = $relevantReceipts->count();
            $quantities[$key] = $receipts->sum(fn (DeliveryReceipt $r) => $r->items->filter(fn ($line) => $line->item && $line->item->item_key === $key
            )->sum('delivered_quantity')
            );
        }

        $rowChalanCounts = [];
        $rowQuantities = [];
        foreach ($itemKeys as $key) {
            $rowChalanCounts[$key] = $this->toBangla($chalanCounts[$key]);
            $rowQuantities[$key] = $this->banglaNumber($quantities[$key]);
        }

        return [
            'school' => $school,
            'school_name' => $school->bangla_name,
            'emis_code' => $school->emis_code ?: $school->code,
            'chalan_counts' => $rowChalanCounts,
            'chalan_counts_raw' => $chalanCounts,
            'quantities' => $rowQuantities,
            'quantities_raw' => $quantities,
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

    private function banglaMonthCode(int $month): string
    {
        return [
            1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ',
            4 => 'এপ্রিল', 5 => 'মে', 6 => 'জুন',
            7 => 'জুলাই', 8 => 'আগস্ট', 9 => 'সেপ্টেম্বর',
            10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
        ][$month] ?? '';
    }

    private function banglaYearCode(int $year): string
    {
        $last2 = substr((string) $year, -2);

        return str_replace(
            range(0, 9),
            self::BANGLA_DIGITS,
            $last2
        );
    }

    private function toBangla(int $number): string
    {
        if ($number === 0) {
            return '০';
        }

        return str_replace(
            range(0, 9),
            self::BANGLA_DIGITS,
            (string) $number
        );
    }

    private function banglaNumber(int $number): string
    {
        if ($number === 0) {
            return '০';
        }

        $formatted = number_format($number, 0, '', ',');

        return str_replace(
            range(0, 9),
            self::BANGLA_DIGITS,
            $formatted
        );
    }

    private function defaultItems(): array
    {
        return [
            ['key' => 'banana_bread', 'name' => 'বনরুটি ১২০ গ্রাম (প্যাকেট)', 'unit' => 'প্যাকেট', 'weight' => 120],
            ['key' => 'boiled_egg', 'name' => 'সিদ্ধ ডিম ৬০ গ্রাম (পিস)', 'unit' => 'পিস', 'weight' => 60],
            ['key' => 'banana', 'name' => 'কলা ১০০ গ্রাম (পিস)', 'unit' => 'পিস', 'weight' => 100],
        ];
    }
}
