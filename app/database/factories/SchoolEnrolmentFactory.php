<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolEnrolment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolEnrolment>
 */
class SchoolEnrolmentFactory extends Factory
{
    protected $model = SchoolEnrolment::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'effective_on' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'pupil_count' => fake()->numberBetween(0, 1000000),
            'reason' => 'Scheduled from the factory.',
        ];
    }

    public function effective(string $date, int $pupilCount): static
    {
        return $this->state(fn (): array => [
            'effective_on' => $date,
            'pupil_count' => $pupilCount,
        ]);
    }
}
