<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DateItemSchedule extends Model
{
    /** @use HasFactory<Database\Factories\DateItemScheduleFactory> */
    use HasFactory;

    protected $table = 'feeding_date_item_schedules';

    public const SOURCE_WORK_ORDER = 'work_order';

    public const SOURCE_ASSUMED = 'assumed';

    protected $fillable = [
        'feeding_cycle_id', 'feeding_item_id', 'schedule_date', 'is_scheduled', 'source', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'schedule_date' => 'date',
            'is_scheduled' => 'boolean',
        ];
    }

    public function feedingCycle(): BelongsTo
    {
        return $this->belongsTo(FeedingCycle::class);
    }

    public function feedingItem(): BelongsTo
    {
        return $this->belongsTo(FeedingItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
