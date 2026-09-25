<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SchoolEnrolmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SchoolEnrolment extends Model
{
    /** @use HasFactory<SchoolEnrolmentFactory> */
    use HasFactory;

    protected $fillable = [
        'effective_on', 'pupil_count', 'recorded_by', 'reason', 'source', 'cancelled_at', 'cancelled_by', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'effective_on' => 'date',
            'pupil_count' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SchoolEnrolment $enrolment): void {
            if (! $enrolment->school()->where('is_active', true)->exists()) {
                throw new LogicException('Inactive schools cannot receive new enrolment.');
            }
        });

        static::saving(function (SchoolEnrolment $enrolment): void {
            $enrolment->active_key = $enrolment->cancelled_at === null
                ? $enrolment->effective_on->toDateString()
                : null;

            if (! $enrolment->exists) {
                return;
            }

            if ($enrolment->isDirty(['effective_on', 'pupil_count'])) {
                throw new LogicException(
                    'A recorded enrolment count is append-only. Cancel a scheduled change and record a replacement instead.',
                );
            }

            if ($enrolment->isDirty('cancelled_at') && $enrolment->effective_on->lte(today())) {
                throw new LogicException(
                    'An enrolment count that has taken effect is immutable and cannot be cancelled.',
                );
            }
        });
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('cancelled_at');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isEffective(?CarbonInterface $on = null): bool
    {
        return ! $this->isCancelled() && $this->effective_on->lte($on ?? today());
    }

    public function isScheduled(?CarbonInterface $on = null): bool
    {
        return ! $this->isCancelled() && $this->effective_on->gt($on ?? today());
    }

    public function statusLabel(?CarbonInterface $on = null): string
    {
        return match (true) {
            $this->isCancelled() => 'Cancelled',
            $this->isScheduled($on) => 'Scheduled',
            default => 'Effective',
        };
    }
}
