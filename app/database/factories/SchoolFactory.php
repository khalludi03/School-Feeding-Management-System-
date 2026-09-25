<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'AN-'.fake()->unique()->numberBetween(100000, 999999),
            'bangla_name' => 'পরীক্ষা বিদ্যালয় '.fake()->unique()->numberBetween(1, 999999),
        ];
    }
}
