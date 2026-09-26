<?php

namespace App\Services;

use App\Models\DateItemSchedule;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DateItemScheduleService
{
    /**
     * @return Collection<int, DateItemSchedule>
     */
    public function scheduledItemsOn(FeedingCycle $cycle, CarbonInterface $date): Collection
    {
        return DateItemSchedule::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->whereDate('schedule_date', $date->toDateString())
            ->where('is_scheduled', true)
            ->get();
    }

    /**
     * @return Collection<int, DateItemSchedule>
     */
    public function monthConfigs(FeedingCycle $cycle, CarbonInterface $month): Collection
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        return DateItemSchedule::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->whereDate('schedule_date', '>=', $from->toDateString())
            ->whereDate('schedule_date', '<=', $to->toDateString())
            ->orderBy('schedule_date')
            ->get();
    }

    public function isSuppliedOnDate(FeedingItem $item, CarbonInterface $date): ?bool
    {
        $config = DateItemSchedule::query()
            ->where('feeding_item_id', $item->id)
            ->whereDate('schedule_date', $date->toDateString())
            ->first();

        if ($config !== null) {
            return $config->is_scheduled;
        }

        return null;
    }

    public function setItemForDate(
        FeedingCycle $cycle,
        FeedingItem $item,
        CarbonInterface $date,
        bool $scheduled,
        string $source,
        int $userId,
    ): DateItemSchedule {
        $existing = DateItemSchedule::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->where('feeding_item_id', $item->id)
            ->whereDate('schedule_date', $date->toDateString())
            ->first();

        if ($existing !== null) {
            $existing->update([
                'is_scheduled' => $scheduled,
                'source' => $source,
                'created_by' => $userId,
            ]);

            return $existing->refresh();
        }

        return DateItemSchedule::query()->create([
            'feeding_cycle_id' => $cycle->id,
            'feeding_item_id' => $item->id,
            'schedule_date' => $date->toDateString(),
            'is_scheduled' => $scheduled,
            'source' => $source,
            'created_by' => $userId,
        ]);
    }

    /**
     * @param  array<int, array{feeding_item_id: int, is_scheduled: bool, schedule_date: string}>  $dateItems
     */
    public function upsertMonth(
        FeedingCycle $cycle,
        CarbonInterface $month,
        array $dateItems,
        string $source,
        int $userId,
    ): void {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $keepIds = [];

        foreach ($dateItems as $entry) {
            $dateStr = $entry['schedule_date'] ?? $from->toDateString();
            $key = $entry['feeding_item_id'].'-'.$dateStr;

            if (isset($keepIds[$key])) {
                continue;
            }

            $existing = DateItemSchedule::query()
                ->where('feeding_cycle_id', $cycle->id)
                ->where('feeding_item_id', $entry['feeding_item_id'])
                ->whereDate('schedule_date', $dateStr)
                ->first();

            if ($existing !== null) {
                $existing->update([
                    'is_scheduled' => $entry['is_scheduled'],
                    'source' => $source,
                    'created_by' => $userId,
                ]);
                $keepIds[$key] = $existing->id;
            } else {
                $model = DateItemSchedule::query()->create([
                    'feeding_cycle_id' => $cycle->id,
                    'feeding_item_id' => $entry['feeding_item_id'],
                    'schedule_date' => $dateStr,
                    'is_scheduled' => $entry['is_scheduled'],
                    'source' => $source,
                    'created_by' => $userId,
                ]);
                $keepIds[$key] = $model->id;
            }
        }

        DateItemSchedule::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->whereDate('schedule_date', '>=', $from->toDateString())
            ->whereDate('schedule_date', '<=', $to->toDateString())
            ->whereNotIn('id', array_values($keepIds))
            ->delete();
    }

    public function restoreFromWeekdayPattern(FeedingCycle $cycle, CarbonInterface $date, int $userId): void
    {
        // Date-level schedules created by an Admin are intentionally preserved when a holiday is
        // removed. Falling back to the weekday pattern only happens when no override exists.
    }
}
