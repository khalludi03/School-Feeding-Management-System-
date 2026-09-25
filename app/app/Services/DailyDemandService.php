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
        $dailyDemand = $producesDemand
            ? $this->demand->dailyDemandFor($pupilCount, $this->demand->rationFactorFor($cycle, $school))
            : null;

        $items = [];
        foreach ($cycle->items()->orderBy('sort_order')->get() as $item) {
            $supplied = $this->patterns->isSuppliedOn($item, $date);

            $items[$item->item_key] = match (true) {
                $dailyDemand === null => null,
                $supplied === false => 0,
                $supplied === null => null,
                default => $dailyDemand,
            };
        }

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
     * Items whose quantity must be entered on a date, i.e. those actually handed over that weekday.
     *
     * @return list<FeedingItem>
     */
    public function itemsExpectedOn(FeedingCycle $cycle, CarbonInterface $date): array
    {
        return $this->patterns->suppliedItemsOn($cycle, $date)->all();
    }
}
