<?php

namespace App\Services;

use App\Models\FeedingItem;
use App\Models\School;
use Carbon\CarbonInterface;

class RationChangeImpactService
{
    public function __construct(
        private readonly DailyDemandService $dailyDemands,
        private readonly ItemRationService $itemRations,
    ) {}

    /**
     * @return array{
     *     feeding_cycle_id: int,
     *     feeding_item_id: int,
     *     ration_factor: float,
     *     effective_on: string,
     *     schools: array<int, array{
     *         school_id: int,
     *         code: string,
     *         bangla_name: string,
     *         before_daily_demand: int|null,
     *         after_daily_demand: int|null,
     *         delta: int|null
     *     }>,
     *     total_before: int|null,
     *     total_after: int|null,
     *     total_delta: int|null,
     *     has_fractional_issue: bool
     * }
     */
    public function forChange(FeedingItem $item, float $rationFactor, CarbonInterface $effectiveOn): array
    {
        $cycle = $item->feedingCycle;
        $today = today();

        $schools = School::query()
            ->with(['participationPeriods'])
            ->orderBy('code')
            ->get()
            ->filter(fn (School $school): bool => $school->isParticipatingOn($effectiveOn));

        $beforeRation = $this->itemRations->rationFactorFor($item, $effectiveOn->copy()->subDay());
        $afterRation = $rationFactor;

        $schoolImpacts = [];
        $totalBefore = 0;
        $totalAfter = 0;
        $hasFractionalIssue = false;

        foreach ($schools as $school) {
            $pupilCount = app(EnrolmentProjectionService::class)->applicableCountOn($school, $effectiveOn);

            if ($pupilCount === null) {
                $schoolImpacts[] = [
                    'school_id' => $school->id,
                    'code' => $school->code,
                    'bangla_name' => $school->bangla_name,
                    'before_daily_demand' => null,
                    'after_daily_demand' => null,
                    'delta' => null,
                ];

                continue;
            }

            $beforeDaily = $beforeRation !== null ? (int) round($pupilCount * $beforeRation) : null;
            $afterDaily = (int) round($pupilCount * $afterRation);

            if ($beforeDaily !== null) {
                $totalBefore += $beforeDaily;
            }
            $totalAfter += $afterDaily;

            $schoolImpacts[] = [
                'school_id' => $school->id,
                'code' => $school->code,
                'bangla_name' => $school->bangla_name,
                'before_daily_demand' => $beforeDaily,
                'after_daily_demand' => $afterDaily,
                'delta' => $beforeDaily !== null ? $afterDaily - $beforeDaily : null,
            ];
        }

        return [
            'feeding_cycle_id' => $cycle->id,
            'feeding_item_id' => $item->id,
            'ration_factor' => $afterRation,
            'effective_on' => $effectiveOn->toDateString(),
            'schools' => $schoolImpacts,
            'total_before' => $beforeRation !== null ? $totalBefore : null,
            'total_after' => $totalAfter,
            'total_delta' => $beforeRation !== null ? $totalAfter - $totalBefore : null,
            'has_fractional_issue' => $hasFractionalIssue,
        ];
    }
}
