<?php

namespace Database\Factories;

use App\Models\DateItemSchedule;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DateItemSchedule>
 */
class DateItemScheduleFactory extends Factory
{
    protected $model = DateItemSchedule::class;

    public function definition(): array
    {
        return [
            'feeding_cycle_id' => FeedingCycle::factory(),
            'feeding_item_id' => FeedingItem::factory(),
            'schedule_date' => $this->faker->date(),
            'is_scheduled' => $this->faker->boolean(80),
            'source' => $this->faker->randomElement(['work_order', 'assumed']),
            'created_by' => User::factory(),
        ];
    }
}
