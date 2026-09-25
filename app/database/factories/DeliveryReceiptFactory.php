<?php

namespace Database\Factories;

use App\Models\DeliveryReceipt;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryReceipt>
 */
class DeliveryReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'delivery_date' => fake()->dateTimeBetween('-10 days', '-1 day')->format('Y-m-d'),
            'entered_by' => User::factory()->state(['role' => 'field_staff']),
            'notes' => null,
        ];
    }
}
