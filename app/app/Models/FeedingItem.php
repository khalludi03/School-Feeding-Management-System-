<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_key', 'name', 'unit', 'weight_grams', 'daily_quantity', 'supply_days',
        'total_quantity', 'unit_price', 'total_value', 'sort_order',
        'supply_weekdays', 'supply_pattern_source',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:3',
            'total_value' => 'decimal:2',
            'supply_days' => 'integer',
            'supply_weekdays' => 'array',
        ];
    }

    public function feedingCycle(): BelongsTo
    {
        return $this->belongsTo(FeedingCycle::class);
    }
}
