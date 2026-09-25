<?php

namespace App\Services;

use App\Models\FeedingCycle;
use App\Models\School;
use App\Models\SchoolPlanningSnapshot;

/**
 * Turns an absolute pupil count into a cycle demand using the ration factor frozen for that cycle.
 */
class CycleDemandService
{
    /**
     * The frozen factor recorded on the school's planning snapshot, or null when none was frozen.
     */
    public function frozenRationFactorFor(FeedingCycle $cycle, School $school): ?float
    {
        $factor = SchoolPlanningSnapshot::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->where('school_id', $school->id)
            ->value('ration_factor');

        return $factor === null ? null : (float) $factor;
    }

    /**
     * The frozen factor from the school's planning snapshot, falling back to the cycle policy.
     * Null means the factor was never assigned and no demand may be produced.
     */
    public function rationFactorFor(FeedingCycle $cycle, School $school): ?float
    {
        return $this->frozenRationFactorFor($cycle, $school)
            ?? ($cycle->ration_factor === null ? null : (float) $cycle->ration_factor);
    }

    public function dailyDemandFor(int $pupilCount, ?float $rationFactor): ?int
    {
        return $rationFactor === null ? null : (int) round($pupilCount * $rationFactor);
    }

    /**
     * Per-item cycle quantities for a school, each being the daily demand across the item's supply days.
     *
     * @return array<string, int|null>
     */
    public function itemQuantitiesFor(FeedingCycle $cycle, int $dailyDemand): array
    {
        $quantities = [];
        foreach ($cycle->items()->orderBy('sort_order')->get() as $item) {
            $quantities[$item->item_key] = $item->supply_days === null
                ? null
                : $dailyDemand * $item->supply_days;
        }

        return $quantities;
    }
}
