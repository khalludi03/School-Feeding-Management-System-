<?php

namespace Database\Factories;

use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedingItemFactory extends Factory
{
    protected $model = FeedingItem::class;

    public function definition(): array
    {
        return [
            'feeding_cycle_id' => FeedingCycle::factory(),
            'item_key' => fake()->unique()->slug(),
            'name' => fake()->words(2, true),
            'unit' => fake()->randomElement(['packet', 'piece', 'service']),
            'weight_grams' => fake()->numberBetween(50, 200),
            'daily_quantity' => fake()->numberBetween(1, 100000),
            'supply_days' => fake()->numberBetween(1, 30),
            'total_quantity' => fake()->numberBetween(1, 1000000),
            'unit_price' => fake()->randomFloat(3, 1, 100),
            'total_value' => fake()->randomFloat(2, 1, 100000),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    /**
     * @param  list<int>  $weekdays  Carbon weekday numbers, 0 = Sunday.
     */
    public function suppliedOn(array $weekdays, string $source = 'work_order'): static
    {
        return $this->state(fn (): array => [
            'supply_weekdays' => $weekdays,
            'supply_pattern_source' => $source,
        ]);
    }

    public function withoutSupplyPattern(): static
    {
        return $this->state(fn (): array => ['supply_weekdays' => null, 'supply_pattern_source' => null]);
    }
}
