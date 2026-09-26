<?php

namespace App\Services;

use App\Models\FeedingItem;
use App\Models\FeedingItemRation;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RuntimeException;

class ItemRationService
{
    /**
     * @return Collection<int, FeedingItemRation>
     */
    public function historyFor(FeedingItem $item): Collection
    {
        return FeedingItemRation::query()
            ->where('feeding_item_id', $item->id)
            ->orderByDesc('effective_on')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Ration factor applicable for an item on a given date.
     *
     * Resolution order:
     * 1. Latest dated item-specific ration where effective_on <= date
     * 2. Cycle-level ration_factor fallback
     * 3. null when no ration is available
     */
    public function rationFactorFor(FeedingItem $item, CarbonInterface $date): ?float
    {
        $result = $this->rationFactorWithDateFor($item, $date);

        return $result['factor'] ?? null;
    }

    /**
     * @return array{factor: float|null, effective_on: string|null, source: string|null}
     */
    public function rationFactorWithDateFor(FeedingItem $item, CarbonInterface $date): array
    {
        $ration = FeedingItemRation::query()
            ->where('feeding_item_id', $item->id)
            ->where('effective_on', '<=', $date->toDateString())
            ->orderByDesc('effective_on')
            ->orderByDesc('id')
            ->first();

        if ($ration !== null) {
            return [
                'factor' => (float) $ration->ration_factor,
                'effective_on' => $ration->effective_on->toDateString(),
                'source' => 'item_dated',
            ];
        }

        $cycle = $item->feedingCycle;

        if ($cycle->ration_factor === null) {
            return ['factor' => null, 'effective_on' => null, 'source' => null];
        }

        return [
            'factor' => (float) $cycle->ration_factor,
            'effective_on' => $cycle->starts_on->toDateString(),
            'source' => 'cycle_fallback',
        ];
    }

    /**
     * Validate that a ration factor is acceptable for the item.
     *
     * Rules:
     * - Ration factor must be > 0 and <= 1
     * - Must produce whole packet/piece demand per student for the item's supply_days
     *   where whole demand = round(pupil_count * ration_factor)
     *   and total quantity = daily demand * supply_days
     */
    public function validateRationFactor(float $rationFactor, FeedingItem $item, int $pupilCount = 100): void
    {
        if ($rationFactor <= 0 || $rationFactor > 1) {
            throw new RuntimeException('Ration factor must be greater than 0 and at most 1.');
        }

        if ($item->supply_days === null || $item->supply_days <= 0) {
            return;
        }

        $dailyDemand = (int) round($pupilCount * $rationFactor);
        $totalQuantity = $dailyDemand * $item->supply_days;

        if ($totalQuantity <= 0) {
            throw new RuntimeException('The supplied ration would produce unsupported zero demand for '.$item->name.'.');
        }
    }
}
