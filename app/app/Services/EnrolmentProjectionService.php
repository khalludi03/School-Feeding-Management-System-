<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolEnrolment;
use Carbon\CarbonInterface;

/**
 * Resolves the applicable enrolment count for a school on any given date.
 */
class EnrolmentProjectionService
{
    /**
     * The count in force on a date, or null when it is unknown because no count has been recorded yet.
     */
    public function applicableCountOn(School $school, CarbonInterface $date): ?int
    {
        return $this->applicableEnrolmentOn($school, $date)?->pupil_count;
    }

    public function applicableEnrolmentOn(School $school, CarbonInterface $date): ?SchoolEnrolment
    {
        return $school->enrolments()
            ->active()
            ->whereDate('effective_on', '<=', $date)
            ->orderByDesc('effective_on')
            ->orderByDesc('id')
            ->first();
    }

    public function currentCount(School $school): ?int
    {
        return $this->applicableCountOn($school, today());
    }

    /**
     * The earliest recorded count. Any date before it is unknown rather than zero.
     */
    public function firstEffectiveOn(School $school): ?CarbonInterface
    {
        $enrolment = $school->enrolments()->active()->orderBy('effective_on')->orderBy('id')->first();

        return $enrolment?->effective_on;
    }

    /**
     * @return list<SchoolEnrolment>
     */
    public function scheduledEnrolments(School $school): array
    {
        return $school->enrolments()
            ->active()
            ->whereDate('effective_on', '>', today())
            ->orderBy('effective_on')
            ->orderBy('id')
            ->get()
            ->all();
    }
}
