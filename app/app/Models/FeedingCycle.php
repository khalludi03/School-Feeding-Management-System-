<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedingCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'title', 'scope', 'starts_on', 'ends_on', 'tender_id', 'circular_reference',
        'school_source_file', 'item_source_file', 'regional_daily_quantity', 'total_value', 'ration_factor',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'total_value' => 'decimal:2',
            'ration_factor' => 'decimal:3',
        ];
    }

    public function hasRationFactor(): bool
    {
        return $this->ration_factor !== null;
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeedingItem::class);
    }

    public function itemRations(): HasMany
    {
        return $this->hasMany(FeedingItemRation::class);
    }

    public function itemPrices(): HasMany
    {
        return $this->hasMany(FeedingItemPrice::class);
    }

    public function schoolPlanningSnapshots(): HasMany
    {
        return $this->hasMany(SchoolPlanningSnapshot::class);
    }

    public function scopeOpenOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString());
    }
}
