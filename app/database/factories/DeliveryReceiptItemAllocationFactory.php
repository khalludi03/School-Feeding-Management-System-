<?php

namespace Database\Factories;

use App\Models\DeliveryReceiptItem;
use App\Models\DeliveryReceiptItemAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryReceiptItemAllocation>
 */
class DeliveryReceiptItemAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_receipt_item_id' => DeliveryReceiptItem::factory(),
            'allocation_date' => today()->toDateString(),
            'allocated_quantity' => 100,
        ];
    }
}
