<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use Carbon\CarbonInterface;

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

        $schools = School::query()
            ->where('is_active', true)
            ->with(['participationPeriods'])
            ->orderBy('code')
            ->get()
            ->filter(fn (School $school): bool => $school->isParticipatingOn($date));

        $receipts = DeliveryReceipt::query()
            ->with('items')
            ->whereDate('delivery_date', $date->toDateString())
            ->get()
            ->keyBy('school_id');

        $rows = [];
        foreach ($schools as $school) {
            $rows[] = $this->rowFor($school, $date, $cycle, $items, $receipts->get($school->id));
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
     * @return array<string, mixed>
     */
    private function rowFor(
        School $school,
        CarbonInterface $date,
        ?FeedingCycle $cycle,
        array $items,
        ?DeliveryReceipt $receipt,
    ): array {
        $demand = $cycle === null
            ? ['pupil_count' => null, 'items' => []]
            : $this->demands->forSchool($cycle, $school, $date);

        $delivered = collect($items)
            ->mapWithKeys(function (FeedingItem $item) use ($receipt): array {
                $line = $receipt?->items->firstWhere('feeding_item_id', $item->id);

                return [$item->item_key => $line?->delivered_quantity];
            });

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
            'entry_recorded' => $receipt !== null,
            'pupil_count' => $demand['pupil_count'] ?? null,
            'daily_demand' => $demand['daily_demand'] ?? null,
            'demand' => $demand['items'],
            'delivered' => $delivered->all(),
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

    private function cycleFor(CarbonInterface $date): ?FeedingCycle
    {
        return FeedingCycle::query()
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->orderByDesc('id')
            ->first();
    }
}
