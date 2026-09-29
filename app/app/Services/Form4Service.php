<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Form4Service
{
    private const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    private const PROGRAMME_ITEM_KEYS = ['banana_bread', 'boiled_egg', 'banana'];

    public function forSchoolMonth(School $school, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        return $this->forSchoolPeriod($school, $start, $end);
    }

    public function forSchoolPeriod(School $school, Carbon $start, Carbon $end): array
    {
        $year = (int) $start->format('Y');
        $month = (int) $start->format('n');

        $cycle = FeedingCycle::query()
            ->whereDate('starts_on', '<=', $end)
            ->orderByDesc('starts_on')
            ->first();

        $items = $cycle
            ? FeedingItem::query()->where('feeding_cycle_id', $cycle->id)->orderBy('sort_order')->get()
            : collect();

        $itemKeys = self::PROGRAMME_ITEM_KEYS;

        $receipts = DeliveryReceipt::query()
            ->with(['items.item'])
            ->where('school_id', $school->id)
            ->whereDate('delivery_date', '>=', $start)
            ->whereDate('delivery_date', '<=', $end)
            ->orderBy('delivery_date')
            ->get();

        $receiptsByDate = $receipts->groupBy(fn (DeliveryReceipt $r) => $r->delivery_date->toDateString());

        $days = [];
        $day = $start->copy();
        $totalQty = array_fill_keys($itemKeys, 0);
        $totalBiscuit = 0;
        $totalMilk = 0;

        while ($day->lte($end)) {
            $dateStr = $day->toDateString();
            $dayReceipts = $receiptsByDate->get($dateStr, collect());

            $row = $this->buildRow($day, $dayReceipts, $items, $itemKeys);
            $days[] = $row;

            foreach ($itemKeys as $key) {
                $totalQty[$key] += $row['totals'][$key] ?? 0;
            }
            $totalBiscuit += $row['biscuit_qty_raw'] ?? 0;
            $totalMilk += $row['milk_qty_raw'] ?? 0;

            $day->addDay();
        }

        $itemsData = $items->isNotEmpty()
            ? $items->map(fn (FeedingItem $item): array => [
                'key' => $item->item_key,
                'name' => $item->name,
                'unit' => $item->unit,
            ])->all()
            : $this->defaultItems();

        return [
            'school' => $school,
            'year' => $year,
            'month' => $month,
            'month_name' => $this->banglaMonth($month),
            'year_bangla' => $this->toBangla($year),
            'meta' => [
                'school_name' => $school->bangla_name,
                'school_code' => $school->emis_code ?: $school->code,
                'district' => $school->district ?? '',
                'upazila' => $school->upazila ?? '',
                'union' => $school->union ?? '',
                'cluster' => $school->cluster ?? '',
            ],
            'days' => $days,
            'total_qty' => array_merge($totalQty, [
                'biscuit' => $totalBiscuit,
                'milk' => $totalMilk,
            ]),
            'items' => $itemsData,
        ];
    }

    private function buildRow(Carbon $date, Collection $dayReceipts, Collection $items, array $itemKeys): array
    {
        if ($dayReceipts->isEmpty()) {
            return $this->emptyRow($date, $itemKeys);
        }

        $chalanNumbers = [];
        $chalanDates = [];
        $itemQty = array_fill_keys($itemKeys, 0);
        $biscuitQty = 0;
        $milkQty = 0;

        foreach ($dayReceipts as $receipt) {
            $chalanNumbers[] = $receipt->chalan_number ?: '-';
            $chalanDates[] = $receipt->chalan_date
                ? $this->formatDateBangla($receipt->chalan_date)
                : '-';

            foreach ($itemKeys as $key) {
                foreach ($receipt->items as $line) {
                    if ($line->item && $line->item->item_key === $key) {
                        $itemQty[$key] += $line->delivered_quantity;
                    }
                }
            }

            foreach ($receipt->items as $line) {
                if (! $line->item) {
                    continue;
                }
                $k = $line->item->item_key;
                if ($k === 'biscuit' || $k === 'fortified_biscuit') {
                    $biscuitQty += $line->delivered_quantity;
                }
                if ($k === 'uht_milk' || $k === 'milk') {
                    $milkQty += $line->delivered_quantity;
                }
            }
        }

        $chalanNumbersStr = implode(', ', $chalanNumbers);
        $chalanDates = array_unique($chalanDates);
        $chalanDatesStr = implode(', ', $chalanDates);

        $rowQty = [];
        $rowTotals = [];
        foreach ($itemKeys as $key) {
            $rowQty[$key] = $this->toBangla($itemQty[$key]);
            $rowTotals[$key] = $itemQty[$key];
        }

        return [
            'date' => $date,
            'date_bangla' => $this->formatDateBangla($date),
            'chalan_numbers' => $chalanNumbersStr,
            'chalan_dates' => $chalanDatesStr,
            'quantities' => $rowQty,
            'totals' => $rowTotals,
            'biscuit_qty' => $this->toBangla($biscuitQty),
            'milk_qty' => $this->toBangla($milkQty),
            'biscuit_qty_raw' => $biscuitQty,
            'milk_qty_raw' => $milkQty,
            'is_empty' => false,
        ];
    }

    private function emptyRow(Carbon $date, array $itemKeys): array
    {
        $rowQty = [];
        $rowTotals = [];
        foreach ($itemKeys as $key) {
            $rowQty[$key] = '০';
            $rowTotals[$key] = 0;
        }

        return [
            'date' => $date,
            'date_bangla' => $this->formatDateBangla($date),
            'chalan_numbers' => '-',
            'chalan_dates' => '-',
            'quantities' => $rowQty,
            'totals' => $rowTotals,
            'biscuit_qty' => '০',
            'milk_qty' => '০',
            'biscuit_qty_raw' => 0,
            'milk_qty_raw' => 0,
            'is_empty' => true,
        ];
    }

    private function defaultItems(): array
    {
        return [
            ['key' => 'banana_bread', 'name' => 'বনরুটি ১২০ গ্রাম (প্যাকেট)', 'unit' => 'প্যাকেট'],
            ['key' => 'boiled_egg', 'name' => 'সিদ্ধ ডিম ৬০ গ্রাম (পিস)', 'unit' => 'পিস'],
            ['key' => 'banana', 'name' => 'কলা ১০০ গ্রাম (পিস)', 'unit' => 'পিস'],
        ];
    }

    private function formatDateBangla(Carbon $date): string
    {
        $d = $this->toBangla((int) $date->format('d'));
        $m = $this->toBangla((int) $date->format('m'));
        $y = $this->toBangla((int) $date->format('Y'));

        return "{$d}/{$m}/{$y}";
    }

    private function banglaMonth(int $month): string
    {
        $months = [
            1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ',
            4 => 'এপ্রিল', 5 => 'মে', 6 => 'জুন',
            7 => 'জুলাই', 8 => 'আগস্ট', 9 => 'সেপ্টেম্বর',
            10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
        ];

        return $months[$month] ?? '';
    }

    public function toBangla(int $number): string
    {
        return str_replace(
            range(0, 9),
            self::BANGLA_DIGITS,
            (string) $number
        );
    }
}
