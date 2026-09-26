<?php

namespace App\Services;

use App\Models\FeedingItem;
use App\Models\FeedingItemPrice;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RuntimeException;

class ItemPriceService
{
    /**
     * @return Collection<int, FeedingItemPrice>
     */
    public function historyFor(FeedingItem $item): Collection
    {
        return FeedingItemPrice::query()
            ->where('feeding_item_id', $item->id)
            ->orderByDesc('effective_on')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array{price: float|null, effective_on: string|null, source: string|null}
     */
    public function priceFor(FeedingItem $item, CarbonInterface $date): array
    {
        $price = FeedingItemPrice::query()
            ->where('feeding_item_id', $item->id)
            ->where('effective_on', '<=', $date->toDateString())
            ->orderByDesc('effective_on')
            ->orderByDesc('id')
            ->first();

        if ($price !== null) {
            return [
                'price' => (float) $price->unit_price,
                'effective_on' => $price->effective_on->toDateString(),
                'source' => 'dated',
            ];
        }

        $current = $item->unit_price;

        return [
            'price' => $current === null ? null : (float) $current,
            'effective_on' => $item->feedingCycle->starts_on->toDateString(),
            'source' => 'item_default',
        ];
    }

    public function validatePrice(float $price): void
    {
        if ($price <= 0) {
            throw new RuntimeException('Price must be greater than 0.');
        }
    }
}
