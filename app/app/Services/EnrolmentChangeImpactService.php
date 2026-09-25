<?php

namespace App\Services;

use App\Models\FeedingCycle;
use App\Models\School;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Builds the demand impact an Admin must see before a dated enrolment change is recorded.
 *
 * A count stays in force until it is superseded, so a change affects every cycle that still has
 * supply days on or after its effective date. Each affected cycle is projected on its final day,
 * which is the latest date the new count is certain to govern.
 */
class EnrolmentChangeImpactService
{
    public function __construct(
        private readonly EnrolmentProjectionService $projections,
        private readonly CycleDemandService $demand,
        private readonly DownstreamImpactService $downstream,
    ) {}

    /**
     * @return array{
     *     effective_on: string,
     *     pupil_count: int,
     *     current_count: int|null,
     *     first_effective_on: string|null,
     *     status: string,
     *     cycles: list<array<string, mixed>>,
     *     missing_factor_cycles: list<string>,
     *     downstream: array{allocations: int, receipts: int, stale: bool},
     *     requires_downstream_acknowledgement: bool,
     *     calendar_note: string
     * }
     */
    public function forChange(School $school, string $effectiveOn, int $pupilCount): array
    {
        $effectiveDate = CarbonImmutable::parse($effectiveOn)->startOfDay();
        $currentCount = $this->projections->currentCount($school);
        $firstEffectiveOn = $this->projections->firstEffectiveOn($school);

        $cycles = [];
        $missingFactorCycles = [];

        foreach ($this->affectedCycles($effectiveDate) as $cycle) {
            $rationFactor = $this->demand->rationFactorFor($cycle, $school);
            if ($rationFactor === null) {
                $missingFactorCycles[] = $cycle->title;

                continue;
            }

            $cycles[] = $this->cycleImpact($school, $cycle, $pupilCount, $rationFactor);
        }

        $downstream = $this->downstream->summaryFor($school);

        return [
            'effective_on' => $effectiveDate->toDateString(),
            'pupil_count' => $pupilCount,
            'current_count' => $currentCount,
            'first_effective_on' => $firstEffectiveOn?->toDateString(),
            'status' => $effectiveDate->isAfter(today()) ? 'Scheduled' : 'Effective today',
            'cycles' => $cycles,
            'missing_factor_cycles' => $missingFactorCycles,
            'downstream' => $downstream,
            'requires_downstream_acknowledgement' => $downstream['stale'],
            'calendar_note' => 'Cycle quantities are shown at the projected daily rate for the whole cycle. Supply-day and holiday calendars are not modelled yet, so a mid-cycle effective date reads as a full-cycle equivalent.',
        ];
    }

    /**
     * @return iterable<FeedingCycle>
     */
    private function affectedCycles(CarbonInterface $effectiveDate): iterable
    {
        return FeedingCycle::query()
            ->whereDate('ends_on', '>=', $effectiveDate)
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function cycleImpact(School $school, FeedingCycle $cycle, int $pupilCount, float $rationFactor): array
    {
        $evaluatedOn = $cycle->ends_on;
        $beforeCount = $this->projections->applicableCountOn($school, $evaluatedOn);
        $afterDaily = $this->demand->dailyDemandFor($pupilCount, $rationFactor);

        $beforeDaily = $beforeCount === null
            ? null
            : $this->demand->dailyDemandFor($beforeCount, $rationFactor);
        $beforeItems = $beforeDaily === null ? [] : $this->demand->itemQuantitiesFor($cycle, $beforeDaily);
        $afterItems = $this->demand->itemQuantitiesFor($cycle, $afterDaily);

        return [
            'cycle_id' => $cycle->id,
            'title' => $cycle->title,
            'starts_on' => $cycle->starts_on->toDateString(),
            'ends_on' => $cycle->ends_on->toDateString(),
            'evaluated_on' => $evaluatedOn->toDateString(),
            'ration_factor' => $rationFactor,
            'factor_frozen' => $this->demand->frozenRationFactorFor($cycle, $school) !== null,
            'before_count' => $beforeCount,
            'after_count' => $pupilCount,
            'before_daily_demand' => $beforeDaily,
            'after_daily_demand' => $afterDaily,
            'daily_demand_delta' => $beforeDaily === null ? null : $afterDaily - $beforeDaily,
            'items' => $cycle->items()->orderBy('sort_order')->get()->map(fn ($item): array => [
                'item_key' => $item->item_key,
                'name' => $item->name,
                'unit' => $item->unit,
                'supply_days' => $item->supply_days,
                'before_quantity' => $beforeItems[$item->item_key] ?? null,
                'after_quantity' => $afterItems[$item->item_key] ?? null,
                'delta' => ($beforeItems[$item->item_key] ?? null) === null
                    ? null
                    : $afterItems[$item->item_key] - $beforeItems[$item->item_key],
            ])->all(),
        ];
    }
}
