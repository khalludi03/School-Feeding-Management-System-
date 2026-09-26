<?php

namespace Database\Factories;

use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\FeedingItemPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedingItemPriceFactory extends Factory
{
    protected $model = FeedingItemPrice::class;

    public function definition(): array
    {
        return [
            'feeding_cycle_id' => FeedingCycle::factory(),
            'feeding_item_id' => FeedingItem::factory(),
            'unit_price' => fake()->randomFloat(3, 1, 100),
            'effective_on' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'created_by' => null,
        ];
    }
}
