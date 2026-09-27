<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\SchoolParticipationPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Form10Service
{
    private const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    private ItemPriceService $priceService;

    public function __construct(ItemPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function forMonth(int $year, int $month, array $invoiceDetails = []): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        return $this->forPeriod($start, $end, $invoiceDetails);
    }

    public function forPeriod(Carbon $start, Carbon $end, array $invoiceDetails = []): array
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

        $receipts = DeliveryReceipt::query()
            ->with(['items.item'])
            ->whereIn('school_id', $schools->pluck('id'))
            ->whereDate('delivery_date', '>=', $start)
            ->whereDate('delivery_date', '<=', $end)
            ->get();

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

        $receiptsBySchool = $receipts->groupBy('school_id');

        $schoolTotals = [];
        $grandQty = array_fill_keys($itemKeys, 0);

        foreach ($schools as $school) {
            $schoolReceipts = $receiptsBySchool->get($school->id, collect());
            $schoolTotals[$school->id] = $this->schoolReceiptTotals($schoolReceipts, $items, $itemKeys);

            foreach ($itemKeys as $key) {
                $grandQty[$key] += $schoolTotals[$school->id]['qty_raw'][$key] ?? 0;
            }
        }

        $lineItems = [];
        $grandRawTotal = 0.0;
        $isIncomplete = false;

        foreach ($itemsData as $itemData) {
            $key = $itemData['key'];
            $qty = $grandQty[$key] ?? 0;

            $priceInfo = $qty > 0
                ? $this->priceForItemWithChalanDates($key, $schools->pluck('id'), $start, $end)
                : ['price' => null, 'effective_on' => null];

            $price = $priceInfo['price'] ?? null;
            $rawTotal = $price !== null ? round($qty * $price, 1) : 0.0;
            $grandRawTotal += $rawTotal;

            if ($price === null && $qty > 0) {
                $isIncomplete = true;
            }

            $lineItems[] = [
                'key' => $key,
                'name' => $itemData['name'],
                'unit' => $itemData['unit'],
                'weight' => $itemData['weight'] ?? null,
                'quantity' => $qty,
                'quantity_bangla' => $this->banglaNumber($qty),
                'unit_price' => $price,
                'unit_price_bangla' => $price !== null ? $this->toBanglaDecimal($price) : null,
                'raw_total' => $rawTotal,
                'total_bangla' => $rawTotal > 0 ? $this->banglaCurrency($rawTotal) : '০',
                'effective_on' => $priceInfo['effective_on'] ?? null,
                'has_price' => $price !== null,
            ];
        }

        $grandTotal = round($grandRawTotal, 0);

        $chalanCount = $receipts->pluck('chalan_number')->filter()->unique()->count();

        $district = $schools->first()?->district ?? '';
        $upazila = $schools->first()?->upazila ?? '';

        $invoiceNumber = $invoiceDetails['invoice_no'] ?? $this->generateInvoiceNumber($cycle, $year, $month);
        $invoiceDateBangla = $this->toBanglaDate(isset($invoiceDetails['invoice_date']) ? Carbon::parse($invoiceDetails['invoice_date']) : $start->copy()->addMonthNoOverflow()->setDay(1));

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $this->banglaMonth($month),
            'month_code' => $this->banglaMonthCode($month),
            'year_code' => $this->banglaYearCode($year),
            'cycle' => $cycle,
            'invoice_number' => $invoiceNumber,
            'invoice_date_bangla' => $invoiceDateBangla,
            'contract_number' => $invoiceDetails['contract_number'] ?? ($cycle?->circular_reference ?? ''),
            'contractor_name' => config('sfp.contractor_name', 'স্বদেশশ্রী'),
            'district' => $district,
            'upazila' => $upazila,
            'recipient' => [
                'name' => 'প্রকল্প পরিচালক',
                'org' => 'সরকারি প্রাথমিক বিদ্যালয়ে ফিডিং কর্মসূচি',
                'address' => 'প্রাথমিক শিক্ষা অধিদপ্তর, সেকশন-২, মিরপুর, ঢাকা',
                'via' => "মাধ্যম: উপজেলা প্রাথমিক শিক্ষা অফিসার, উপজেলা: {$upazila}, জেলা: {$district}",
            ],
            'supplier_name' => config('sfp.supplier_name', 'স্বদেশশ্রী'),
            'bank_info' => [
                'account_name' => $invoiceDetails['bank_account_name'] ?? config('sfp.bank_account_name', 'Shadesh Palli Ltd'),
                'account_number' => $invoiceDetails['bank_account_number'] ?? config('sfp.bank_account_number', '0792101000003304'),
                'bank_name' => $invoiceDetails['bank_name'] ?? config('sfp.bank_name', 'United Commercial Bank Limited'),
                'branch' => $invoiceDetails['bank_branch'] ?? config('sfp.bank_branch', 'Bahaddarhat'),
                'routing' => $invoiceDetails['bank_routing'] ?? config('sfp.bank_routing', '245150799'),
            ],
            'upeo_mobile' => $invoiceDetails['upeo_mobile'] ?? null,
            'items' => $itemsData,
            'line_items' => $lineItems,
            'grand_total' => $grandTotal,
            'grand_total_bangla' => $this->banglaCurrency($grandTotal),
            'grand_total_raw' => $grandRawTotal,
            'chalan_count' => $chalanCount,
            'chalan_count_bangla' => $this->toBangla($chalanCount),
            'school_count' => $schools->count(),
            'school_count_bangla' => $this->toBangla($schools->count()),
            'is_incomplete' => $isIncomplete,
            'schools' => $schools,
            'school_totals' => $schoolTotals,
            'form7_totals' => $grandQty,
        ];
    }

    private function priceForItemWithChalanDates(string $itemKey, Collection $schoolIds, Carbon $start, Carbon $end): array
    {
        $receipts = DeliveryReceipt::query()
            ->with(['items.item'])
            ->whereIn('school_id', $schoolIds)
            ->whereDate('delivery_date', '>=', $start)
            ->whereDate('delivery_date', '<=', $end)
            ->get();

        $qtyByChalanDate = [];

        foreach ($receipts as $receipt) {
            $chalanDate = $receipt->chalan_date;
            if (! $chalanDate) {
                continue;
            }

            $itemQty = $receipt->items
                ->filter(fn ($line) => $line->item && $line->item->item_key === $itemKey)
                ->sum('delivered_quantity');

            if ($itemQty > 0) {
                $dateKey = $chalanDate->toDateString();
                $qtyByChalanDate[$dateKey] = ($qtyByChalanDate[$dateKey] ?? 0) + $itemQty;
            }
        }

        if (empty($qtyByChalanDate)) {
            return ['price' => null, 'effective_on' => null];
        }

        $totalQty = array_sum($qtyByChalanDate);

        $weightedSum = 0.0;
        $latestEffectiveOn = null;

        $item = FeedingItem::query()->where('item_key', $itemKey)->first();
        if (! $item) {
            return ['price' => null, 'effective_on' => null];
        }

        foreach ($qtyByChalanDate as $dateStr => $qty) {
            $date = Carbon::parse($dateStr);
            $priceInfo = $this->priceService->priceFor($item, $date);

            if ($priceInfo['price'] !== null) {
                $weightedSum += $qty * $priceInfo['price'];
                if ($latestEffectiveOn === null || $priceInfo['effective_on'] > $latestEffectiveOn) {
                    $latestEffectiveOn = $priceInfo['effective_on'];
                }
            }
        }

        if ($weightedSum <= 0 || $totalQty <= 0) {
            return ['price' => null, 'effective_on' => $latestEffectiveOn];
        }

        return [
            'price' => round($weightedSum / $totalQty, 3),
            'effective_on' => $latestEffectiveOn,
        ];
    }

    private function schoolReceiptTotals(Collection $receipts, Collection $items, array $itemKeys): array
    {
        $qty = array_fill_keys($itemKeys, 0);
        $incomplete = false;

        foreach ($itemKeys as $key) {
            $qty[$key] = $receipts->sum(fn (DeliveryReceipt $r) => $r->items
                ->filter(fn ($line) => $line->item && $line->item->item_key === $key)
                ->sum('delivered_quantity')
            );
        }

        foreach ($itemKeys as $key) {
            $item = $items->firstWhere('item_key', $key);
            if ($item && ($qty[$key] ?? 0) > 0) {
                $priceInfo = $this->priceService->priceFor($item, Carbon::today());
                if (($priceInfo['price'] ?? null) === null) {
                    $incomplete = true;
                }
            }
        }

        return [
            'qty' => array_map(fn ($v) => $this->banglaNumber($v), $qty),
            'qty_raw' => $qty,
            'incomplete' => $incomplete,
        ];
    }

    private function generateInvoiceNumber(?FeedingCycle $cycle, int $year, int $month): string
    {
        $prefix = $cycle?->slug
            ? preg_replace('/[^A-Za-z0-9]/', '', strtoupper(substr($cycle->slug, 0, 8)))
            : 'INV';
        $ym = sprintf('%04d%02d', $year, $month);

        return "{$prefix}-{$ym}-0001";
    }

    private function toBangla(int $number): string
    {
        if ($number === 0) {
            return '০';
        }

        return str_replace(range(0, 9), self::BANGLA_DIGITS, (string) $number);
    }

    private function toBanglaDecimal(float $number): string
    {
        $str = number_format(round($number, 3), 3, '.', '');

        return str_replace(range(0, 9), self::BANGLA_DIGITS, $str);
    }

    private function banglaNumber(int $number): string
    {
        if ($number === 0) {
            return '০';
        }

        $formatted = number_format($number, 0, '', ',');

        return str_replace(range(0, 9), self::BANGLA_DIGITS, $formatted);
    }

    private function banglaCurrency(float $amount): string
    {
        if ($amount <= 0) {
            return '০';
        }

        $rounded = round($amount, 0);
        $formatted = number_format($rounded, 0, '', ',');

        return str_replace(range(0, 9), self::BANGLA_DIGITS, $formatted);
    }

    private function toBanglaDate(Carbon $date): string
    {
        $d = $this->toBangla((int) $date->format('d'));
        $monthNames = [
            1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ',
            4 => 'এপ্রিল', 5 => 'মে', 6 => 'জুন',
            7 => 'জুলাই', 8 => 'আগস্ট', 9 => 'সেপ্টেম্বর',
            10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
        ];
        $m = $monthNames[(int) $date->format('n')] ?? '';
        $y = $this->toBangla((int) $date->format('Y'));

        return "{$d} {$m} {$y}";
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

        return str_replace(range(0, 9), self::BANGLA_DIGITS, $last2);
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
