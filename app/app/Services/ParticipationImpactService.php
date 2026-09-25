<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use App\Models\School;
use App\Models\SchoolParticipationPeriod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * Works out what a dated participation change would do, and what it would invalidate.
 *
 * Participation is the single source of truth for whether a school is in the programme on a given
 * date, so a deactivation is expressed as the day the currently open period stops applying rather
 * than as a flag on the school. The day before the effective date is the last day the school
 * participates, which keeps every earlier demand and report intact.
 *
 * Records dated on or after the effective date would fall outside the closed period, so they are
 * conflicts: an Admin must resolve them with the responsible Field Staff before the close can be
 * saved. Those records are never modified here.
 */
class ParticipationImpactService
{
    private const ALLOCATION_TABLE = 'allocations';

    /**
     * @return array{
     *     effective_on: string,
     *     last_participating_on: string,
     *     period_id: int|null,
     *     period_starts_on: string|null,
     *     already_closed: bool,
     *     starts_after_effective_date: bool,
     *     conflicts: list<array<string, mixed>>,
     *     conflict_count: int,
     *     has_conflicts: bool
     * }
     */
    public function forDeactivation(School $school, string $effectiveOn): array
    {
        $effectiveDate = CarbonImmutable::parse($effectiveOn)->startOfDay();
        $period = $this->openPeriod($school);
        $lastParticipatingOn = $effectiveDate->subDay();
        $conflicts = $this->conflictsFrom($school, $effectiveDate);

        return [
            'effective_on' => $effectiveDate->toDateString(),
            'last_participating_on' => $lastParticipatingOn->toDateString(),
            'period_id' => $period?->id,
            'period_starts_on' => $period?->starts_on->toDateString(),
            'already_closed' => $period === null,
            'starts_after_effective_date' => $period !== null && $period->starts_on->gt($lastParticipatingOn),
            'conflicts' => $conflicts,
            'conflict_count' => count($conflicts),
            'has_conflicts' => $conflicts !== [],
        ];
    }

    /**
     * Records that a deactivation would strand, newest last, each naming the staff who must act.
     *
     * @return list<array<string, mixed>>
     */
    public function conflictsFrom(School $school, CarbonInterface $effectiveDate): array
    {
        $conflicts = [];

        $receipts = DeliveryReceipt::query()
            ->with('enteredBy')
            ->where('school_id', $school->id)
            ->whereDate('delivery_date', '>=', $effectiveDate->toDateString())
            ->orderBy('delivery_date')
            ->orderBy('id')
            ->get();

        foreach ($receipts as $receipt) {
            $conflicts[] = [
                'kind' => 'receipt',
                'id' => $receipt->id,
                'date' => $receipt->delivery_date->toDateString(),
                'reference' => 'Delivery entry #'.$receipt->id,
                'responsible_staff_id' => $receipt->entered_by,
                'responsible_staff_name' => $receipt->enteredBy?->name,
            ];
        }

        if (Schema::hasTable(self::ALLOCATION_TABLE) && Schema::hasColumn(self::ALLOCATION_TABLE, 'school_id')) {
            foreach ($this->allocationConflicts($school, $effectiveDate) as $allocation) {
                $conflicts[] = $allocation;
            }
        }

        return $conflicts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allocationConflicts(School $school, CarbonInterface $effectiveDate): array
    {
        $dateColumn = Schema::hasColumn(self::ALLOCATION_TABLE, 'allocation_date')
            ? 'allocation_date'
            : 'delivery_date';

        if (! Schema::hasColumn(self::ALLOCATION_TABLE, $dateColumn)) {
            return [];
        }

        return DB::table(self::ALLOCATION_TABLE)
            ->where('school_id', $school->id)
            ->whereDate($dateColumn, '>=', $effectiveDate->toDateString())
            ->orderBy($dateColumn)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => [
                'kind' => 'allocation',
                'id' => (int) $row->id,
                'date' => CarbonImmutable::parse($row->{$dateColumn})->toDateString(),
                'reference' => 'Allocation #'.$row->id,
                'responsible_staff_id' => null,
                'responsible_staff_name' => null,
            ])
            ->all();
    }

    /**
     * The period that currently has no end, which is the one a deactivation must close.
     */
    private function openPeriod(School $school): ?SchoolParticipationPeriod
    {
        return $school->participationPeriods()
            ->whereNull('ends_on')
            ->orderByDesc('starts_on')
            ->first();
    }

    /**
     * @throws LogicException when there is no open period to close.
     */
    public function requireOpenPeriod(School $school): SchoolParticipationPeriod
    {
        $period = $this->openPeriod($school);

        if ($period === null) {
            throw new LogicException('This school has no open participation period, so there is nothing to stop.');
        }

        return $period;
    }
}
