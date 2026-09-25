<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SchoolParticipationPeriod extends Model
{
    protected $fillable = ['starts_on', 'ends_on', 'recorded_by'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (SchoolParticipationPeriod $period): void {
            if (! $period->school()->where('is_active', true)->exists()) {
                throw new LogicException('Inactive schools cannot receive new participation.');
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
