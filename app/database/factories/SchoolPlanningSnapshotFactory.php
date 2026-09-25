<?php

namespace Database\Factories;

use App\Models\FeedingCycle;
use App\Models\School;
use App\Models\SchoolPlanningSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolPlanningSnapshotFactory extends Factory
{
    protected $model = SchoolPlanningSnapshot::class;

    public function definition(): array
    {
        return [
            'feeding_cycle_id' => FeedingCycle::factory(),
            'school_id' => School::factory(),
            'source_serial' => fake()->unique()->numberBetween(1, 65535),
            'pupil_count' => fake()->numberBetween(1, 1000),
            'target_pupil_count' => fake()->randomFloat(1, 1, 1000),
            'daily_demand' => fake()->numberBetween(1, 1000),
            'bread_quantity' => fake()->numberBetween(1, 10000),
            'egg_quantity' => fake()->numberBetween(1, 10000),
            'banana_quantity' => fake()->numberBetween(1, 10000),
            'source_file' => 'schools.md',
            'source_flags' => [],
            'source_payload' => ['raw_row' => []],
        ];
    }
}
