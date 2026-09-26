<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedingItemRation extends Model
{
    /** @use HasFactory<Database\Factories\FeedingItemRationFactory> */
    use HasFactory;

    protected $fillable = [
        'feeding_cycle_id', 'feeding_item_id', 'ration_factor', 'effective_on', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ration_factor' => 'decimal:3',
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
