<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SchoolEnrolment extends Model
{
    protected $fillable = ['effective_on', 'pupil_count', 'recorded_by'];

    protected function casts(): array
    {
        return ['effective_on' => 'date', 'pupil_count' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (SchoolEnrolment $enrolment): void {
            if (! $enrolment->school()->where('is_active', true)->exists()) {
                throw new LogicException('Inactive schools cannot receive new enrolment.');
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
