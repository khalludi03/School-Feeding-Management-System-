<?php

namespace App\Models;

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
        'code', 'bangla_name', 'union', 'cluster', 'teacher_name', 'teacher_phone',
        'emis_code', 'emis_source', 'emis_verified_at', 'emis_verified_by',
    ];

    protected function casts(): array
    {
        return ['emis_verified_at' => 'datetime'];
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

    public function participationPeriods(): HasMany
    {
        return $this->hasMany(SchoolParticipationPeriod::class);
    }
}
