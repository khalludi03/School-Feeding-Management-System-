<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'source_key', 'bangla_name', 'union', 'cluster', 'upazila', 'district', 'teacher_name', 'teacher_phone',
        'emis_code', 'emis_source', 'emis_verified_at', 'emis_verified_by', 'is_active', 'emis_is_provisional',
    ];

    protected function casts(): array
    {
        return [
            'emis_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'emis_is_provisional' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (School $school): void {
            if ($school->isDirty('code')) {
                throw new LogicException('School codes cannot be changed.');
            }
        });
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(SchoolEnrolment::class);
    }

    public function scheduledEnrolments(): HasMany
    {
        return $this->hasMany(SchoolEnrolment::class)
            ->active()
            ->whereDate('effective_on', '>', today());
    }

    public function participationPeriods(): HasMany
    {
        return $this->hasMany(SchoolParticipationPeriod::class);
    }

    public function planningSnapshots(): HasMany
    {
        return $this->hasMany(SchoolPlanningSnapshot::class);
    }

    public function isParticipatingOn(CarbonInterface $date): bool
    {
        return $this->participationPeriods->contains(
            fn (SchoolParticipationPeriod $period): bool => $period->starts_on->lte($date)
                && ($period->ends_on === null || $period->ends_on->gte($date)),
        );
    }

    public function participationHasStartedOn(CarbonInterface $date): bool
    {
        return $this->participationPeriods->isNotEmpty()
            && $this->participationPeriods->contains(fn (SchoolParticipationPeriod $period): bool => $period->starts_on->lte($date));
    }

    public function hasOverlappingParticipationPeriods(): bool
    {
        $periods = $this->participationPeriods->sortBy('starts_on')->values();
        for ($first = 0; $first < $periods->count(); $first++) {
            for ($second = $first + 1; $second < $periods->count(); $second++) {
                if ($periods[$first]->ends_on === null || $periods[$second]->starts_on->lte($periods[$first]->ends_on)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function nextScheduledEnrolment(): ?SchoolEnrolment
    {
        return $this->scheduledEnrolments
            ->sortBy([['effective_on', 'asc'], ['id', 'asc']])
            ->first();
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }
}
