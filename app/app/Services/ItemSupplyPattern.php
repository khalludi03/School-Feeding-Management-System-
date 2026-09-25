<?php

namespace App\Services;

use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Decides whether an item is actually handed over on a given date.
 *
 * The September 2026 call-off notice only supplies bread on Sunday, Monday, Wednesday and Thursday,
 * so a per-item total cannot be spread evenly across the month. The weekday set is stored on the item
 * and never inferred from supply_days, and supply_pattern_source records whether the pattern came from
 * the work order or is a provisional assumption, so provisional figures stay visible in the reports.
 */
class ItemSupplyPattern
{
    public const SOURCE_WORK_ORDER = 'work_order';

    public const SOURCE_ASSUMED = 'assumed';

    /**
     * @return list<int>|null Carbon weekday numbers (0 = Sunday), or null when never configured.
     */
    public function weekdaysFor(FeedingItem $item): ?array
    {
        $weekdays = $item->supply_weekdays;

        if (! is_array($weekdays) || $weekdays === []) {
            return null;
        }

        return array_values(array_map(intval(...), $weekdays));
    }

    public function isConfiguredFor(FeedingItem $item): bool
    {
        return $this->weekdaysFor($item) !== null;
    }

    /**
     * Null means the pattern is unknown, which is different from "not supplied on this date".
     */
    public function isSuppliedOn(FeedingItem $item, CarbonInterface $date): ?bool
    {
        $weekdays = $this->weekdaysFor($item);

        if ($weekdays === null) {
            return null;
        }

        return in_array($date->dayOfWeek, $weekdays, true);
    }

    public function isProvisional(FeedingItem $item): bool
    {
        return $item->supply_pattern_source === self::SOURCE_ASSUMED;
    }

    /**
     * @return Collection<int, FeedingItem>
     */
    public function suppliedItemsOn(FeedingCycle $cycle, CarbonInterface $date): Collection
    {
        return $cycle->items()
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (FeedingItem $item): bool => $this->isSuppliedOn($item, $date) === true);
    }

    /**
     * Deliverable items whose pattern is still unknown, which blocks report figures. Lines without
     * supply_days are cost entries such as the related service charge, never handed to a school.
     *
     * @return list<FeedingItem>
     */
    public function unconfiguredItems(FeedingCycle $cycle): array
    {
        return $cycle->items()
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (FeedingItem $item): bool => $item->supply_days !== null)
            ->reject(fn (FeedingItem $item): bool => $this->isConfiguredFor($item))
            ->values()
            ->all();
    }
}
