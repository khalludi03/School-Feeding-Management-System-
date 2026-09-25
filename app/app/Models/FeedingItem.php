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
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:3',
            'total_value' => 'decimal:2',
        ];
    }

    public function feedingCycle(): BelongsTo
    {
        return $this->belongsTo(FeedingCycle::class);
    }
}
