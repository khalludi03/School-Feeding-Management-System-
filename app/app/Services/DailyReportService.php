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
        $items = $cycle?->items()->orderBy('sort_order')->get()->all() ?? [];

        $emptyTotals = [
            'schools' => 0,
            'entries_recorded' => 0,
            'entries_missing' => 0,
            'complete' => true,
            'demand' => [],
            'delivered' => [],
            'shortfall' => [],
            'demand_unknown_schools' => [],
        ];
        foreach ($items as $item) {
            $emptyTotals['demand'][$item->item_key] = 0;
            $emptyTotals['delivered'][$item->item_key] = 0;
            $emptyTotals['shortfall'][$item->item_key] = 0;
            $emptyTotals['demand_unknown_schools'][$item->item_key] = 0;
        }

        if (! $isWorkingDay) {
            return [
                'date' => $date,
                'cycle' => $cycle,
                'is_working_day' => false,
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
            );
        }

        return [
            'date' => $date,
            'cycle' => $cycle,
            'is_working_day' => $isWorkingDay,
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
        $entryRecorded = false;

        foreach ($items as $item) {
            $allocated = $allocationsByItem[$item->item_key] ?? null;
            $zeroed = $zerosByItem[$item->item_key] ?? null;
            $schoolItemKey = $school->id.'-'.$item->item_key;

            if ($allocated !== null) {
                $delivered[$item->item_key] = (int) $allocated;
                $entryRecorded = true;
            } elseif ($zeroed !== null) {
                $delivered[$item->item_key] = 0;
                $entryRecorded = true;
            } elseif (isset($allocatedSchoolItems[$schoolItemKey])) {
                $delivered[$item->item_key] = null;
                $entryRecorded = true;
            } elseif ($receipt !== null) {
                $line = $receipt->items->firstWhere('feeding_item_id', $item->id);

                if ($line !== null) {
                    $delivered[$item->item_key] = $line->delivered_quantity;
                    $entryRecorded = true;
                } else {
                    $delivered[$item->item_key] = null;
                }
            } else {
                $delivered[$item->item_key] = null;
            }
        }

        $shortfall = [];
        foreach ($items as $item) {
            $demandValue = $demand['items'][$item->item_key] ?? null;
            $deliveredValue = $delivered[$item->item_key] ?? null;

            $shortfall[$item->item_key] = match (true) {
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
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<FeedingItem>  $items
     * @return array<string, mixed>
     */
    private function totalsFor(array $rows, array $items): array
    {
        $totals = ['demand' => [], 'delivered' => [], 'shortfall' => [], 'demand_unknown_schools' => []];
        $unknownSchools = 0;
        $entriesRecorded = 0;

        foreach ($items as $item) {
            $knownDemand = 0;
            $unknown = 0;
            $delivered = 0;
            $shortfall = 0;

            foreach ($rows as $row) {
                $demandValue = $row['demand'][$item->item_key] ?? null;
                if ($demandValue === null) {
                    $unknown++;
                } else {
                    $knownDemand += $demandValue;
                }

                $deliveredValue = $row['delivered'][$item->item_key] ?? null;
                $delivered += $deliveredValue ?? 0;

                $shortfallValue = $row['shortfall'][$item->item_key] ?? null;
                $shortfall += $shortfallValue ?? 0;
            }

            $totals['demand'][$item->item_key] = $knownDemand;
            $totals['delivered'][$item->item_key] = $delivered;
            $totals['shortfall'][$item->item_key] = $shortfall;
            $totals['demand_unknown_schools'][$item->item_key] = $unknown;
            $unknownSchools = max($unknownSchools, $unknown);
        }

        foreach ($rows as $row) {
            if ($row['entry_recorded']) {
                $entriesRecorded++;
            }
        }

        $totals['schools'] = count($rows);
        $totals['entries_recorded'] = $entriesRecorded;
        $totals['entries_missing'] = count($rows) - $entriesRecorded;
        $totals['complete'] = $unknownSchools === 0;

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
