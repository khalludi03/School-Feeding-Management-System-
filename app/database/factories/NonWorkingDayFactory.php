<?php

namespace Database\Factories;

use App\Models\NonWorkingDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NonWorkingDay>
 */
class NonWorkingDayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'holiday_on' => fake()->unique()->date(),
            'kind' => NonWorkingDay::KIND_HOLIDAY,
            'name' => fake()->randomElement(['Public holiday', 'Weekly off', 'National event']),
        ];
    }

    public function weeklyOff(): static
    {
        return $this->state(fn (): array => ['kind' => NonWorkingDay::KIND_WEEKLY_OFF, 'name' => 'Weekly off']);
    }
}
