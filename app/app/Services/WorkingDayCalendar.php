<?php

namespace App\Services;

use App\Models\NonWorkingDay;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for "is the programme delivering on this date?".
 *
 * A date with no row in non_working_days is a working day. Public holidays and weekly off days are
 * both stored as concrete dates rather than derived from a recurring rule, so the September 2026
 * figures can be traced back to the exact day the Admin marked.
 */
class WorkingDayCalendar
{
    public function isNonWorking(CarbonInterface $date): bool
    {
        return NonWorkingDay::query()
            ->whereDate('holiday_on', $date->toDateString())
            ->exists();
    }

    public function isWorkingDay(CarbonInterface $date): bool
    {
        return ! $this->isNonWorking($date);
    }

    /**
     * @return array<string, NonWorkingDay> keyed by Y-m-d for the whole range, in one query.
     */
    public function nonWorkingDaysBetween(CarbonInterface $from, CarbonInterface $to): array
    {
        return NonWorkingDay::query()
            ->whereBetween('holiday_on', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn (NonWorkingDay $day): string => $day->holiday_on->toDateString())
            ->all();
    }

    public function workingDaysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        $nonWorking = $this->nonWorkingDaysBetween($from, $to);

        $total = $from->diffInDays($to) + 1;

        return max(0, $total - count($nonWorking));
    }

    /**
     * @return list<Carbon>
     */
    public function workingDays(CarbonInterface $from, CarbonInterface $to): array
    {
        $nonWorking = $this->nonWorkingDaysBetween($from, $to);

        $days = [];
        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            if (! isset($nonWorking[$date->toDateString()])) {
                $days[] = $date->copy();
            }
        }

        return $days;
    }
}
