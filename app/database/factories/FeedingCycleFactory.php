<?php

namespace Database\Factories;

use App\Models\FeedingCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedingCycleFactory extends Factory
{
    protected $model = FeedingCycle::class;

    public function definition(): array
    {
        return [
            'slug' => 'cycle-'.fake()->unique()->slug(),
            'title' => fake()->sentence(),
            'scope' => fake()->sentence(),
            'starts_on' => now()->startOfMonth()->toDateString(),
            'ends_on' => now()->endOfMonth()->toDateString(),
            'tender_id' => fake()->numerify('TENDER-#####'),
            'circular_reference' => fake()->numerify('CIRCULAR-#####'),
            'school_source_file' => 'schools.md',
            'item_source_file' => 'items.md',
            'regional_daily_quantity' => fake()->numberBetween(1, 100000),
            'total_value' => fake()->randomFloat(2, 1, 100000),
        ];
    }

    public function withRationFactor(float $factor = 0.9): static
    {
        return $this->state(fn (): array => ['ration_factor' => $factor]);
    }

    public function withoutRationFactor(): static
    {
        return $this->state(fn (): array => ['ration_factor' => null]);
    }
}
