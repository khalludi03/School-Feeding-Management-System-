<?php

namespace Database\Factories;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\FeedingItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryReceiptItem>
 */
class DeliveryReceiptItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_receipt_id' => DeliveryReceipt::factory(),
            'feeding_item_id' => FeedingItem::factory(),
            'delivered_quantity' => 100,
        ];
    }
}
