<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use App\Models\FeedingItem;
use App\Models\School;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        foreach ($this->allocationConflictsForDate($date) as $allocation) {
            $conflicts[] = $allocation;
        }

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

        foreach ($this->allocationConflictsForItem($item, $date) as $allocation) {
            $conflicts[] = $allocation;
        }

        return [
            'blocked' => count($conflicts) > 0,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allocationConflictsForDate(CarbonInterface $date): array
    {
        $table = 'allocations';
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'allocation_date')) {
            return [];
        }

        return DB::table($table)
            ->whereDate('allocation_date', $date->toDateString())
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => [
                'school_id' => property_exists($row, 'school_id') ? (int) $row->school_id : 0,
                'school_code' => '—',
                'school_name' => 'Allocation #'.$row->id,
                'receipt_id' => 0,
                'entered_by' => property_exists($row, 'entered_by') ? (int) $row->entered_by : null,
                'entered_by_name' => 'Unknown',
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allocationConflictsForItem(FeedingItem $item, CarbonInterface $date): array
    {
        $table = 'allocations';
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'allocation_date')
            || ! Schema::hasColumn($table, 'feeding_item_id')) {
            return [];
        }

        return DB::table($table)
            ->where('feeding_item_id', $item->id)
            ->whereDate('allocation_date', $date->toDateString())
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => [
                'school_id' => property_exists($row, 'school_id') ? (int) $row->school_id : 0,
                'school_code' => '—',
                'school_name' => 'Allocation #'.$row->id,
                'receipt_id' => 0,
                'entered_by' => property_exists($row, 'entered_by') ? (int) $row->entered_by : null,
                'entered_by_name' => 'Unknown',
            ])
            ->all();
    }
}
