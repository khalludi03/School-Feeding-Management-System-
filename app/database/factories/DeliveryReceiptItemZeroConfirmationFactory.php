<?php

namespace Database\Factories;

use App\Models\DeliveryReceiptItemZeroConfirmation;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryReceiptItemZeroConfirmation>
 */
class DeliveryReceiptItemZeroConfirmationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'date' => today()->toDateString(),
            'feeding_item_id' => FeedingItem::factory(),
            'reason' => fake()->sentence(),
            'confirmed_by' => User::factory()->state(['role' => 'field_staff']),
        ];
    }
}
