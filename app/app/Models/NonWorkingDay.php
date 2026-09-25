<?php

namespace App\Models;

use Database\Factories\NonWorkingDayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A date the programme does not deliver. Public holidays and recurring weekly off days are both
 * recorded as concrete dates so the September 2026 figures can be audited line by line.
 */
class NonWorkingDay extends Model
{
    /** @use HasFactory<NonWorkingDayFactory> */
    use HasFactory;

    public const KIND_HOLIDAY = 'holiday';

    public const KIND_WEEKLY_OFF = 'weekly_off';

    protected $fillable = ['holiday_on', 'kind', 'name', 'recorded_by'];

    protected function casts(): array
    {
        return ['holiday_on' => 'date'];
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isWeeklyOff(): bool
    {
        return $this->kind === self::KIND_WEEKLY_OFF;
    }
}
