<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItemAllocation;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\SchoolEnrolment;
use App\Models\SchoolParticipationPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Form12Service
{
    private const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    public function forSchoolMonth(School $school, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        return $this->forSchoolPeriod($school, $start, $end);
    }

    public function forSchoolPeriod(School $school, Carbon $start, Carbon $end): array
    {
        $today = Carbon::today();
        $year = (int) $start->format('Y');
        $month = (int) $start->format('n');

        $cycle = FeedingCycle::query()
            ->whereDate('starts_on', '<=', $end)
            ->orderByDesc('starts_on')
            ->first();

        $participation = SchoolParticipationPeriod::query()
            ->where('school_id', $school->id)
            ->whereDate('starts_on', '<=', $end)
            ->orderBy('starts_on')
            ->first();

        $items = $cycle
            ? FeedingItem::query()->where('feeding_cycle_id', $cycle->id)->orderBy('sort_order')->get()
            : collect();

        $itemsData = $items->isNotEmpty()
            ? $items->map(fn (FeedingItem $item): array => [
                'key' => $item->item_key,
                'name' => $item->name,
                'unit' => $item->unit,
                'weight' => $item->weight_grams,
            ])->all()
            : $this->defaultItems();

        $itemKeys = collect($itemsData)->pluck('key')->all();

        $isBaseline = $this->isBaselinePeriod($school, $cycle, $start);

        $openingStock = $isBaseline
            ? array_fill_keys($itemKeys, 0)
            : $this->calculateClosingStock($school, $items, $itemKeys, $start->copy()->subDay());

        $receipts = $this->calculateReceipts($school, $items, $itemKeys, $start, $end);
        $allocations = $this->calculateAllocations($school, $items, $itemKeys, $start, $end);

        $periodEndsToday = $end->gte($today);
        $receiptsUpToToday = $periodEndsToday
            ? $this->calculateReceipts($school, $items, $itemKeys, $start, $today)
            : $receipts;
        $distributionsUpToToday = $periodEndsToday
            ? $this->calculateAllocations($school, $items, $itemKeys, $start, $today)
            : null;

        $lineItems = [];
        foreach ($itemsData as $itemData) {
            $key = $itemData['key'];
            $open = $openingStock[$key] ?? 0;
            $recv = $receipts[$key] ?? 0;
            $dist = $allocations[$key] ?? 0;
            $closing = $open + $recv - $dist;

            $recvToday = $receiptsUpToToday[$key] ?? 0;
            $distToday = $distributionsUpToToday[$key] ?? $dist;
            $closingToday = $periodEndsToday ? ($open + $recvToday - $distToday) : $closing;

            $lineItems[] = [
                'key' => $key,
                'name' => $itemData['name'],
                'unit' => $itemData['unit'],
                'weight' => $itemData['weight'] ?? null,
                'opening' => $open,
                'opening_bangla' => $this->banglaNumber($open),
                'receipts' => $recv,
                'receipts_bangla' => $this->banglaNumber($recv),
                'open_received' => $open + $recv,
                'open_received_bangla' => $this->banglaNumber($open + $recv),
                'distribution' => $dist,
                'distribution_bangla' => $this->banglaNumber($dist),
                'closing' => $closing,
                'closing_bangla' => $this->banglaNumber($closing),
                'closing_actual' => $periodEndsToday ? $closingToday : null,
                'closing_actual_bangla' => $periodEndsToday ? $this->banglaNumber($closingToday) : null,
                'is_projected' => $periodEndsToday && $closing !== $closingToday,
            ];
        }

        $isIncomplete = false;

        $hasAnyReceipt = collect($lineItems)->sum('receipts') > 0;
        $hasAnyAllocation = collect($lineItems)->sum('distribution') > 0;
        if (! $hasAnyReceipt && ! $hasAnyAllocation) {
            $isIncomplete = true;
        }

        $enrolment = SchoolEnrolment::query()
            ->where('school_id', $school->id)
            ->whereDate('effective_on', '<=', $end)
            ->orderByDesc('effective_on')
            ->first();

        $enrolmentNext = SchoolEnrolment::query()
            ->where('school_id', $school->id)
            ->whereDate('effective_on', '>', $end)
            ->orderBy('effective_on')
            ->first();

        return [
            'school' => $school,
            'year' => $year,
            'month' => $month,
            'month_name' => $this->banglaMonth($month),
            'year_bangla' => $this->toBangla($year),
            'cycle' => $cycle,
            'meta' => [
                'school_name' => $school->bangla_name,
                'school_code' => $school->emis_code ?: $school->code,
                'district' => $school->district ?? '',
                'upazila' => $school->upazila ?? '',
                'union' => $school->union ?? '',
                'cluster' => $school->cluster ?? '',
            ],
            'enrolment' => $enrolment,
            'enrolment_next' => $enrolmentNext,
            'items' => $itemsData,
            'line_items' => $lineItems,
            'is_baseline' => $isBaseline,
            'is_partial' => $periodEndsToday && ! $end->isSameDay($today),
            'is_incomplete' => $isIncomplete,
        ];
    }

    private function isBaselinePeriod(School $school, ?FeedingCycle $cycle, Carbon $start): bool
    {
        if (! $cycle) {
            return true;
        }

        $participationStart = SchoolParticipationPeriod::query()
            ->where('school_id', $school->id)
            ->whereDate('starts_on', '<=', $start)
            ->orderBy('starts_on')
            ->first();

        if (! $participationStart) {
            return true;
        }

        $cycleStart = $cycle->starts_on;

        return $participationStart->starts_on->toDateString() === $cycleStart->toDateString()
            && $start->toDateString() === $cycleStart->toDateString();
    }

    private function calculateClosingStock(School $school, Collection $items, array $itemKeys, Carbon $asOfDate): array
    {
        $participation = SchoolParticipationPeriod::query()
            ->where('school_id', $school->id)
            ->whereDate('starts_on', '<=', $asOfDate)
            ->orderBy('starts_on')
            ->first();

        if (! $participation) {
            return array_fill_keys($itemKeys, 0);
        }

        $participationStart = $participation->starts_on;
        $periodEnd = $asOfDate;

        if ($periodEnd->lt($participationStart)) {
            return array_fill_keys($itemKeys, 0);
        }

        $receipts = $this->calculateReceipts($school, $items, $itemKeys, $participationStart, $periodEnd);
        $allocations = $this->calculateAllocations($school, $items, $itemKeys, $participationStart, $periodEnd);

        $closingStock = [];
        foreach ($itemKeys as $key) {
            $closingStock[$key] = max(0, ($receipts[$key] ?? 0) - ($allocations[$key] ?? 0));
        }

        return $closingStock;
    }

    private function calculateReceipts(School $school, Collection $items, array $itemKeys, Carbon $start, Carbon $end): array
    {
        $receipts = DeliveryReceipt::query()
            ->with(['items.item'])
            ->where('school_id', $school->id)
            ->whereDate('delivery_date', '>=', $start->toDateString())
            ->whereDate('delivery_date', '<=', $end->toDateString())
            ->get();

        $totals = array_fill_keys($itemKeys, 0);

        foreach ($itemKeys as $key) {
            $totals[$key] = $receipts->sum(fn (DeliveryReceipt $r) => $r->items
                ->filter(fn ($line) => $line->item && $line->item->item_key === $key)
                ->sum('delivered_quantity')
            );
        }

        return $totals;
    }

    private function calculateAllocations(School $school, Collection $items, array $itemKeys, Carbon $start, Carbon $end): array
    {
        $totals = array_fill_keys($itemKeys, 0);

        $allocations = DeliveryReceiptItemAllocation::query()
            ->with(['receiptItem.receipt', 'receiptItem.item'])
            ->whereHas('receiptItem.receipt', fn ($q) => $q->where('school_id', $school->id))
            ->whereDate('allocation_date', '>=', $start->toDateString())
            ->whereDate('allocation_date', '<=', $end->toDateString())
            ->get();

        foreach ($itemKeys as $key) {
            $totals[$key] = $allocations
                ->filter(fn ($a) => $a->receiptItem && $a->receiptItem->item && $a->receiptItem->item->item_key === $key)
                ->sum('allocated_quantity');
        }

        return $totals;
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

    private function defaultItems(): array
    {
        return [
            ['key' => 'banana_bread', 'name' => 'বনরুটি', 'unit' => 'প্যাকেট', 'weight' => 120],
            ['key' => 'boiled_egg', 'name' => 'সিদ্ধ ডিম', 'unit' => 'পিস', 'weight' => 60],
            ['key' => 'banana', 'name' => 'কলা', 'unit' => 'পিস', 'weight' => 100],
        ];
    }
}
