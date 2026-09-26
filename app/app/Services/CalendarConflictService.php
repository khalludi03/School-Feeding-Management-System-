<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use App\Models\School;
use Carbon\CarbonInterface;

class CalendarConflictService
{
    /**
     * @return array{blocked: bool, conflicts: array<int, array{school_id: int, school_code: string, school_name: string, receipt_id: int, entered_by: int, entered_by_name: string}>}
     */
    public function checkNonWorkingConflict(CarbonInterface $date): array
    {
        $conflicts = DeliveryReceipt::query()
            ->whereDate('delivery_date', $date->toDateString())
            ->with(['school', 'enteredBy'])
            ->get()
            ->map(fn (DeliveryReceipt $receipt): array => [
                'school_id' => $receipt->school_id,
                'school_code' => $receipt->school->code,
                'school_name' => $receipt->school->bangla_name,
                'receipt_id' => $receipt->id,
                'entered_by' => $receipt->entered_by,
                'entered_by_name' => $receipt->enteredBy?->name ?? 'Unknown',
            ])
            ->values()
            ->all();

        return [
            'blocked' => count($conflicts) > 0,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * @return array{blocked: bool, conflicts: array<int, array{school_id: int, school_code: string, school_name: string, receipt_id: int, entered_by: int, entered_by_name: string}>}
     */
    public function checkItemRemovalConflict(FeedingItem $item, CarbonInterface $date): array
    {
        $schools = School::query()
            ->whereHas('deliveryReceipts', function ($query) use ($date, $item) {
                $query->whereDate('delivery_date', $date->toDateString())
                    ->whereHas('items', function ($query) use ($item) {
                        $query->where('feeding_item_id', $item->id);
                    });
            })
            ->get();

        $conflicts = [];

        foreach ($schools as $school) {
            $receipts = DeliveryReceipt::query()
                ->where('school_id', $school->id)
                ->whereDate('delivery_date', $date->toDateString())
                ->whereHas('items', fn ($query) => $query->where('feeding_item_id', $item->id))
                ->with(['enteredBy'])
                ->get();

            foreach ($receipts as $receipt) {
                $conflicts[] = [
                    'school_id' => $school->id,
                    'school_code' => $school->code,
                    'school_name' => $school->bangla_name,
                    'receipt_id' => $receipt->id,
                    'entered_by' => $receipt->entered_by,
                    'entered_by_name' => $receipt->enteredBy?->name ?? 'Unknown',
                ];
            }
        }

        return [
            'blocked' => count($conflicts) > 0,
            'conflicts' => $conflicts,
        ];
    }
}
