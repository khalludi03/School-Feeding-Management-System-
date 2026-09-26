<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedingItemPrice extends Model
{
    /** @use HasFactory<Database\Factories\FeedingItemPriceFactory> */
    use HasFactory;

    protected $fillable = [
        'feeding_cycle_id', 'feeding_item_id', 'unit_price', 'effective_on', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:3',
            'effective_on' => 'date',
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
