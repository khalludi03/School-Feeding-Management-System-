<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItemAllocation;
use App\Models\DeliveryReceiptItemZeroConfirmation;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Demand against delivery for one date, per school, per item, plus the upazila total.
 *
 * Shortfall follows the brief exactly: demand minus delivered. A demand that cannot be derived stays
 * null rather than becoming zero, because an unknown figure and a genuine zero are different facts, and
 * an upazila total built on unknowns is reported as incomplete instead of quietly understating demand.
 */
class DailyReportService
{
    public function __construct(
        private readonly WorkingDayCalendar $calendar,
        private readonly DailyDemandService $demands,
    ) {}

    /**
     * @return array{
     *     date: CarbonInterface,
     *     cycle: FeedingCycle|null,
     *     is_working_day: bool,
     *     items: list<FeedingItem>,
     *     rows: list<array<string, mixed>>,
     *     totals: array<string, mixed>,
     *     unconfigured_items: list<FeedingItem>
     * }
     */
    public function forDate(CarbonInterface $date): array
    {
        $cycle = $this->cycleFor($date);
        $isWorkingDay = $this->calendar->isWorkingDay($date);
        $items = $cycle?->items()
            ->where('item_key', '!=', 'related_service')
            ->orderBy('sort_order')
            ->get()->all() ?? [];

        $emptyTotals = [
            'schools' => 0,
            'entries_recorded' => 0,
            'entries_missing' => 0,
            'complete' => true,
            'demand' => [],
            'delivered' => [],
            'shortfall' => [],
            'demand_unknown_schools' => [],
            'shortage' => [],
            'excess' => [],
            'net_balance' => [],
            'confirmed_shortfall' => [],
            'total_shortage' => 0,
            'total_excess' => 0,
            'total_net_balance' => 0,
            'total_confirmed_shortfall' => 0,
            'unknown_demand_schools' => 0,
        ];
        foreach ($items as $item) {
            $emptyTotals['demand'][$item->item_key] = 0;
            $emptyTotals['delivered'][$item->item_key] = 0;
            $emptyTotals['shortfall'][$item->item_key] = 0;
            $emptyTotals['demand_unknown_schools'][$item->item_key] = 0;
            $emptyTotals['shortage'][$item->item_key] = 0;
            $emptyTotals['excess'][$item->item_key] = 0;
            $emptyTotals['net_balance'][$item->item_key] = 0;
            $emptyTotals['confirmed_shortfall'][$item->item_key] = 0;
        }

        if (! $isWorkingDay) {
            return [
                'date' => $date,
                'cycle' => $cycle,
                'is_working_day' => false,
                'is_future' => $date->isFuture(),
                'items' => $items,
                'rows' => [],
                'totals' => $emptyTotals,
                'unconfigured_items' => [],
            ];
        }

        // Participation on the reported date decides inclusion, not the school's current directory
        // status. A school deactivated after the fact must still appear in the reports it took part
        // in, otherwise closing a school would silently rewrite its own history.
        $schools = School::query()
            ->with(['participationPeriods'])
            ->orderBy('code')
            ->get()
            ->filter(fn (School $school): bool => $school->isParticipatingOn($date));

        $receipts = DeliveryReceipt::query()
            ->with('items.item')
            ->whereDate('delivery_date', $date->toDateString())
            ->get()
            ->keyBy('school_id');

        $allocations = $this->allocationsForDate($date);
        $zeroConfirmations = $this->zeroConfirmationsForDate($date);
        $allocatedSchoolItems = $this->allocatedSchoolItems($schools->pluck('id')->all());
        $zeroConfirmedSchoolItems = $this->zeroConfirmedSchoolItems($schools->pluck('id')->all());

        $rows = [];
        $isFuture = $date->isFuture();
        foreach ($schools as $school) {
            $rows[] = $this->rowFor(
                $school,
                $date,
                $cycle,
                $items,
                $receipts->get($school->id),
                $allocations,
                $zeroConfirmations,
                $allocatedSchoolItems,
                $zeroConfirmedSchoolItems,
                $isFuture,
            );
        }

        return [
            'date' => $date,
            'cycle' => $cycle,
            'is_working_day' => $isWorkingDay,
            'is_future' => $date->isFuture(),
            'items' => $items,
            'rows' => $rows,
            'totals' => $this->totalsFor($rows, $items),
            'unconfigured_items' => $cycle === null ? [] : app(ItemSupplyPattern::class)->unconfiguredItems($cycle),
        ];
    }

    /**
     * @param  list<FeedingItem>  $items
     * @param  Collection<int, DeliveryReceiptItemAllocation>  $allocations
     * @param  Collection<int, DeliveryReceiptItemZeroConfirmation>  $zeroConfirmations
     * @param  array<string, true>  $allocatedSchoolItems
     * @param  array<string, true>  $zeroConfirmedSchoolItems
     * @return array<string, mixed>
     */
    private function rowFor(
        School $school,
        CarbonInterface $date,
        ?FeedingCycle $cycle,
        array $items,
        ?DeliveryReceipt $receipt,
        $allocations,
        $zeroConfirmations,
        array $allocatedSchoolItems,
        array $zeroConfirmedSchoolItems,
        bool $isFuture,
    ): array {
        $demand = $cycle === null
            ? ['pupil_count' => null, 'items' => []]
            : $this->demands->forSchool($cycle, $school, $date);

        $allocationsByItem = $allocations
            ->where('receiptItem.receipt.school_id', $school->id)
            ->groupBy(fn ($allocation): string => $allocation->receiptItem->item->item_key)
            ->map(fn ($group): int => $group->sum('allocated_quantity'));

        $zerosByItem = $zeroConfirmations
            ->where('school_id', $school->id)
            ->keyBy(fn ($zero): string => $zero->item->item_key);

        $delivered = [];
        $statuses = [];
        $entryRecorded = false;
        $missingExpectedItems = 0;
        $expectedItems = 0;
        $hasUnknownDemand = false;

        foreach ($items as $item) {
            $itemKey = $item->item_key;
            $allocated = $allocationsByItem[$itemKey] ?? null;
            $zeroed = $zerosByItem[$itemKey] ?? null;
            $schoolItemKey = $school->id.'-'.$itemKey;

            if ($allocated !== null) {
                $delivered[$itemKey] = (int) $allocated;
                $entryRecorded = true;
            } elseif ($zeroed !== null) {
                $delivered[$itemKey] = 0;
                $entryRecorded = true;
            } elseif (isset($allocatedSchoolItems[$schoolItemKey])) {
                $delivered[$itemKey] = null;
                $entryRecorded = true;
            } elseif ($receipt !== null) {
                $line = $receipt->items->firstWhere('feeding_item_id', $item->id);

                if ($line !== null) {
                    $delivered[$itemKey] = $line->delivered_quantity;
                    $entryRecorded = true;
                } else {
                    $delivered[$itemKey] = null;
                }
            } else {
                $delivered[$itemKey] = null;
            }

            $demandValue = $demand['items'][$itemKey] ?? null;
            $deliveredValue = $delivered[$itemKey] ?? null;

            $status = match (true) {
                $demandValue === null => 'unknown_demand',
                $demandValue === 0 => 'not_scheduled',
                $isFuture => $deliveredValue !== null ? 'planned' : 'not_submitted',
                $allocated !== null => 'submitted',
                $zeroed !== null => 'confirmed_shortfall',
                default => 'not_submitted',
            };

            $statuses[$itemKey] = $status;

            if ($status === 'unknown_demand') {
                $hasUnknownDemand = true;
            }

            $isExpectedItem = $demandValue !== null && $demandValue > 0 && ! $isFuture;

            if ($isExpectedItem) {
                $expectedItems++;
                if ($status === 'not_submitted') {
                    $missingExpectedItems++;
                }
            }
        }

        $shortfall = [];
        foreach ($items as $item) {
            $itemKey = $item->item_key;
            $demandValue = $demand['items'][$itemKey] ?? null;
            $deliveredValue = $delivered[$itemKey] ?? null;

            $shortfall[$itemKey] = match (true) {
                $demandValue === null => null,
                $deliveredValue === null => $demandValue,
                default => $demandValue - $deliveredValue,
            };
        }

        return [
            'school' => $school,
            'entry_recorded' => $entryRecorded,
            'pupil_count' => $demand['pupil_count'] ?? null,
            'daily_demand' => $demand['daily_demand'] ?? null,
            'demand' => $demand['items'],
            'delivered' => $delivered,
            'shortfall' => $shortfall,
            'status' => $statuses,
            'expected_items' => $expectedItems,
            'missing_expected_items' => $missingExpectedItems,
            'has_unknown_demand' => $hasUnknownDemand,
            'is_future' => $isFuture,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<FeedingItem>  $items
     * @return array<string, mixed>
     */
    private function totalsFor(array $rows, array $items): array
    {
        $totals = [
            'demand' => [],
            'delivered' => [],
            'shortfall' => [],
            'demand_unknown_schools' => [],
            'shortage' => [],
            'excess' => [],
            'net_balance' => [],
            'confirmed_shortfall' => [],
        ];
        $unknownSchools = 0;
        $unknownDemandSchools = 0;
        $entriesRecorded = 0;
        $entriesMissing = 0;
        $totalShortage = 0;
        $totalExcess = 0;
        $totalConfirmedShortfall = 0;

        foreach ($items as $item) {
            $itemKey = $item->item_key;
            $knownDemand = 0;
            $unknown = 0;
            $delivered = 0;
            $shortfall = 0;
            $shortage = 0;
            $excess = 0;
            $confirmedShortfall = 0;

            foreach ($rows as $row) {
                $demandValue = $row['demand'][$itemKey] ?? null;
                $deliveredValue = $row['delivered'][$itemKey] ?? null;
                $shortfallValue = $row['shortfall'][$itemKey] ?? null;
                $status = $row['status'][$itemKey] ?? 'not_submitted';

                if ($demandValue === null) {
                    $unknown++;
                } else {
                    $knownDemand += $demandValue;
                }

                $delivered += $deliveredValue ?? 0;
                $shortfall += $shortfallValue ?? 0;

                if ($shortfallValue !== null && $shortfallValue > 0) {
                    $shortage += $shortfallValue;
                    if (! ($row['is_future'] ?? false)) {
                        $confirmedShortfall += $shortfallValue;
                    }
                } elseif ($shortfallValue !== null && $shortfallValue < 0) {
                    $excess += abs($shortfallValue);
                }

                if ($status === 'unknown_demand') {
                    $unknownSchools = max($unknownSchools, 1);
                }
            }

            $totals['demand'][$itemKey] = $knownDemand;
            $totals['delivered'][$itemKey] = $delivered;
            $totals['shortfall'][$itemKey] = $shortfall;
            $totals['demand_unknown_schools'][$itemKey] = $unknown;
            $totals['shortage'][$itemKey] = $shortage;
            $totals['excess'][$itemKey] = $excess;
            $totals['net_balance'][$itemKey] = $excess - $shortage;
            $totals['confirmed_shortfall'][$itemKey] = $confirmedShortfall;

            $totalShortage += $shortage;
            $totalExcess += $excess;
            $totalConfirmedShortfall += $confirmedShortfall;
        }

        foreach ($rows as $row) {
            if ($row['entry_recorded']) {
                $entriesRecorded++;
            }
            if (! ($row['is_future'] ?? false) && ($row['missing_expected_items'] ?? 0) > 0) {
                $entriesMissing++;
            }
            if (! ($row['is_future'] ?? false) && ($row['has_unknown_demand'] ?? false)) {
                $unknownDemandSchools++;
            }
        }

        $totals['schools'] = count($rows);
        $totals['entries_recorded'] = $entriesRecorded;
        $totals['entries_missing'] = $entriesMissing;
        $totals['unknown_demand_schools'] = $unknownDemandSchools;
        $totals['complete'] = $unknownSchools === 0 && $entriesMissing === 0;
        $totals['shortage'] = $totals['shortage'] ?: [];
        $totals['excess'] = $totals['excess'] ?: [];
        $totals['net_balance'] = $totals['net_balance'] ?: [];
        $totals['confirmed_shortfall'] = $totals['confirmed_shortfall'] ?: [];
        $totals['total_shortage'] = $totalShortage;
        $totals['total_excess'] = $totalExcess;
        $totals['total_net_balance'] = $totalExcess - $totalShortage;
        $totals['total_confirmed_shortfall'] = $totalConfirmedShortfall;

        return $totals;
    }

    /**
     * @return Collection<int, DeliveryReceiptItemAllocation>
     */
    private function allocationsForDate(CarbonInterface $date)
    {
        return DeliveryReceiptItemAllocation::query()
            ->whereDate('allocation_date', $date->toDateString())
            ->with(['receiptItem.receipt.school', 'receiptItem.item'])
            ->get();
    }

    /**
     * @param  list<int>  $schoolIds
     * @return array<string, true>
     */
    private function allocatedSchoolItems(array $schoolIds): array
    {
        if ($schoolIds === []) {
            return [];
        }

        $rows = DB::table('delivery_receipt_item_allocations')
            ->join('delivery_receipt_items', 'delivery_receipt_item_allocations.delivery_receipt_item_id', '=', 'delivery_receipt_items.id')
            ->join('delivery_receipts', 'delivery_receipt_items.delivery_receipt_id', '=', 'delivery_receipts.id')
            ->join('feeding_items', 'delivery_receipt_items.feeding_item_id', '=', 'feeding_items.id')
            ->whereIn('delivery_receipts.school_id', $schoolIds)
            ->select('delivery_receipts.school_id', 'feeding_items.item_key')
            ->distinct()
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->school_id.'-'.$row->item_key] = true;
        }

        return $map;
    }

    /**
     * @return Collection<int, DeliveryReceiptItemZeroConfirmation>
     */
    private function zeroConfirmationsForDate(CarbonInterface $date)
    {
        return DeliveryReceiptItemZeroConfirmation::query()
            ->whereDate('date', $date->toDateString())
            ->with(['item'])
            ->get();
    }

    /**
     * @param  list<int>  $schoolIds
     * @return array<string, true>
     */
    private function zeroConfirmedSchoolItems(array $schoolIds): array
    {
        if ($schoolIds === []) {
            return [];
        }

        $rows = DeliveryReceiptItemZeroConfirmation::query()
            ->whereIn('school_id', $schoolIds)
            ->with('item')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->school_id.'-'.$row->item->item_key] = true;
        }

        return $map;
    }

    private function cycleFor(CarbonInterface $date): ?FeedingCycle
    {
        return FeedingCycle::query()
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->orderByDesc('id')
            ->first();
    }
}
