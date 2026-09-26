<?php

namespace App\Services;

use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use Carbon\CarbonInterface;

/**
 * Per-date, per-item demand for one school.
 *
 * Demand is zero on non-working days, zero for items not handed over that weekday, and unknown when
 * the school has no recorded pupil count yet. Participation is independent of enrolment: a school that
 * has left the programme still shows its last count but generates no demand.
 */
class DailyDemandService
{
    public function __construct(
        private readonly WorkingDayCalendar $calendar,
        private readonly ItemSupplyPattern $patterns,
        private readonly CycleDemandService $demand,
        private readonly EnrolmentProjectionService $projections,
        private readonly ItemRationService $itemRations,
    ) {}

    /**
     * @return array{
     *     date: string,
     *     is_working_day: bool,
     *     participating: bool,
     *     pupil_count: int|null,
     *     daily_demand: int|null,
     *     items: array<string, int|null>
     * }
     */
    public function forSchool(FeedingCycle $cycle, School $school, CarbonInterface $date): array
    {
        $isWorkingDay = $this->calendar->isWorkingDay($date);
        $participating = $school->isParticipatingOn($date);
        $pupilCount = $this->projections->applicableCountOn($school, $date);

        $producesDemand = $isWorkingDay && $participating && $pupilCount !== null;

        $items = [];
        foreach ($cycle->items()->orderBy('sort_order')->get() as $item) {
            $supplied = $this->patterns->isSuppliedOn($item, $date);

            $items[$item->item_key] = match (true) {
                ! $producesDemand => null,
                $supplied === false => 0,
                $supplied === null => null,
                default => $this->demand->dailyDemandForItem($item, $pupilCount, $date),
            };
        }

        $dailyDemand = $producesDemand
            ? $this->demand->dailyDemandFor($pupilCount, $this->demand->rationFactorFor($cycle, $school))
            : null;

        return [
            'date' => $date->toDateString(),
            'is_working_day' => $isWorkingDay,
            'participating' => $participating,
            'pupil_count' => $pupilCount,
            'daily_demand' => $dailyDemand,
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     date: string,
     *     cycle_title: string,
     *     school_code: string,
     *     school_name: string,
     *     is_working_day: bool,
     *     working_day_reason: string|null,
     *     is_participating: bool,
     *     participation_reason: string|null,
     *     pupil_count: int|null,
     *     pupil_count_effective_on: string|null,
     *     pupil_count_source: string|null,
     *     ration_factor: float|null,
     *     ration_source: string|null,
     *     ration_effective_on: string|null,
     *     daily_demand: int|null,
     *     items: array<int, array{
     *         item_key: string,
     *         name: string,
     *         unit: string,
     *         supply_days: int|null,
     *         supplied_on_date: bool|null,
     *         demand: int|null,
     *         zero_reason: string|null,
     *         unknown_reason: string|null,
     *     }>,
     *     setup_incomplete: bool,
     *     setup_incomplete_reasons: list<string>,
     * }
     */
    public function explainForSchool(FeedingCycle $cycle, School $school, CarbonInterface $date): array
    {
        $isWorkingDay = $this->calendar->isWorkingDay($date);
        $participating = $school->isParticipatingOn($date);
        $enrolment = $this->projections->applicableEnrolmentOn($school, $date);
        $pupilCount = $enrolment?->pupil_count;

        $producesDemand = $isWorkingDay && $participating && $pupilCount !== null;

        $rationResult = $this->itemRations->rationFactorWithDateFor($cycle->items()->firstOrFail(), $date);
        $rationFactor = $rationResult['factor'];
        $rationSource = $rationResult['source'];
        $rationEffectiveOn = $rationResult['effective_on'];

        $dailyDemand = $producesDemand && $rationFactor !== null
            ? (int) round($pupilCount * $rationFactor)
            : null;

        $items = [];
        $setupIncompleteReasons = [];
        foreach ($cycle->items()->orderBy('sort_order')->get() as $item) {
            $supplied = $this->patterns->isSuppliedOn($item, $date);
            $supplyConfigured = $this->patterns->isConfiguredFor($item);

            if (! $supplyConfigured) {
                $setupIncompleteReasons[] = 'Supply weekdays not configured for '.$item->name;
            }

            if ($rationFactor === null && $producesDemand) {
                $setupIncompleteReasons[] = 'No ration factor available for '.$item->name;
            }

            if ($pupilCount === null && $producesDemand) {
                $setupIncompleteReasons[] = 'No enrolment count recorded for this date';
            }

            $demand = match (true) {
                ! $producesDemand => null,
                $supplied === false => 0,
                $supplied === null => null,
                default => $dailyDemand,
            };

            $zeroReason = match (true) {
                ! $isWorkingDay => 'holiday',
                ! $participating => 'not_participating',
                $supplied === false => 'item_not_scheduled',
                default => null,
            };

            $unknownReason = match (true) {
                $supplied === null && $producesDemand => 'no_supply_pattern',
                $rationFactor === null && $producesDemand => 'missing_ration',
                $pupilCount === null && $producesDemand => 'no_pupil_count',
                default => null,
            };

            $items[] = [
                'item_key' => $item->item_key,
                'name' => $item->name,
                'unit' => $item->unit,
                'supply_days' => $item->supply_days,
                'supplied_on_date' => $supplied,
                'demand' => $demand,
                'zero_reason' => $zeroReason,
                'unknown_reason' => $unknownReason,
            ];
        }

        return [
            'date' => $date->toDateString(),
            'cycle_title' => $cycle->title,
            'school_code' => $school->code,
            'school_name' => $school->bangla_name,
            'is_working_day' => $isWorkingDay,
            'working_day_reason' => $isWorkingDay ? null : 'Non-working day — no demand generated',
            'is_participating' => $participating,
            'participation_reason' => $participating ? null : 'School is not participating on this date',
            'pupil_count' => $pupilCount,
            'pupil_count_effective_on' => $enrolment?->effective_on->toDateString(),
            'pupil_count_source' => $enrolment?->source,
            'ration_factor' => $rationFactor,
            'ration_source' => $rationSource,
            'ration_effective_on' => $rationEffectiveOn,
            'daily_demand' => $dailyDemand,
            'items' => $items,
            'setup_incomplete' => $setupIncompleteReasons !== [],
            'setup_incomplete_reasons' => array_values(array_unique($setupIncompleteReasons)),
        ];
    }

    /**
     * Items whose quantity must be entered on a date, i.e. those actually handed over that weekday.
     *
     * @return list<FeedingItem>
     */
    public function itemsExpectedOn(FeedingCycle $cycle, CarbonInterface $date): array
    {
        return $this->patterns->suppliedItemsOn($cycle, $date)->all();
    }
}
