<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolPlanningSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_serial', 'pupil_count', 'target_pupil_count', 'daily_demand', 'bread_quantity',
        'egg_quantity', 'banana_quantity', 'source_file', 'source_flags', 'source_payload', 'ration_factor',
    ];

    protected function casts(): array
    {
        return [
            'target_pupil_count' => 'decimal:1',
            'ration_factor' => 'decimal:3',
            'source_flags' => 'array',
            'source_payload' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feedingCycle(): BelongsTo
    {
        return $this->belongsTo(FeedingCycle::class);
    }
}
