<?php

namespace App\Services;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItemAllocation;
use App\Models\FeedingCycle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DeliveryAllocationService
{
    public function __construct(
        private readonly WorkingDayCalendar $calendar,
        private readonly ItemSupplyPattern $patterns,
    ) {}

    /**
     * Validate and persist allocations for every positive item on a receipt.
     *
     * @param  array<int, list<array{date: string, quantity: int}>>  $allocations
     *
     * @throws ValidationException
     */
    public function replaceForReceipt(DeliveryReceipt $receipt, FeedingCycle $cycle, array $allocations): void
    {
        $errors = [];
        $items = $cycle->items()->orderBy('sort_order')->get()->keyBy('id');
        $receiptItems = $receipt->items()->with('item')->get()->keyBy('feeding_item_id');
        $receiptDate = CarbonImmutable::parse($receipt->delivery_date->toDateString());

        $records = [];

        foreach ($items as $itemId => $item) {
            $itemAllocations = $allocations[$itemId] ?? $allocations[(string) $itemId] ?? [];
            $delivered = $receiptItems->get($itemId)?->delivered_quantity ?? 0;

            if ($delivered <= 0) {
                continue;
            }

            if ($itemAllocations === [] && $this->patterns->isSuppliedOn($item, $receiptDate) === null) {
                continue;
            }

            $total = 0;
            $seenDates = [];

            foreach ($itemAllocations as $index => $row) {
                $date = is_array($row) ? ($row['date'] ?? null) : null;
                $quantity = is_array($row) ? (int) ($row['quantity'] ?? 0) : 0;

                if (! is_string($date) || $date === '' || ! CarbonImmutable::hasFormat($date, 'Y-m-d')) {
                    $errors["allocations.{$itemId}.{$index}.date"] = 'A valid allocation date is required.';

                    continue;
                }

                if ($quantity <= 0) {
                    $errors["allocations.{$itemId}.{$index}.quantity"] = 'Allocation quantity must be a positive integer.';

                    continue;
                }

                $allocationDate = CarbonImmutable::parse($date);

                if ($allocationDate->lessThan($receiptDate)) {
                    $errors["allocations.{$itemId}.{$index}.date"] = 'Allocation date cannot be before the receipt date.';

                    continue;
                }

                if (! $this->calendar->isWorkingDay($allocationDate)) {
                    $errors["allocations.{$itemId}.{$index}.date"] = 'The programme does not deliver on this date.';

                    continue;
                }

                if (! $receipt->school->isParticipatingOn($allocationDate)) {
                    $errors["allocations.{$itemId}.{$index}.date"] = 'The school is not participating on this date.';

                    continue;
                }

                $supplied = $this->patterns->isSuppliedOn($item, $allocationDate);

                if ($supplied === null) {
                    $errors["allocations.{$itemId}.{$index}.date"] = 'The supply pattern for this item is not configured on this date.';

                    continue;
                }

                if ($supplied === false) {
                    $errors["allocations.{$itemId}.{$index}.date"] = 'This item is not scheduled for supply on this date.';

                    continue;
                }

                if (isset($seenDates[$date])) {
                    $errors["allocations.{$itemId}.{$index}.date"] = 'Each date can only be used once per item.';

                    continue;
                }

                $seenDates[$date] = true;
                $total += $quantity;
                $records[] = [
                    'delivery_receipt_item_id' => $receiptItems->get($itemId)->id,
                    'allocation_date' => $date,
                    'allocated_quantity' => $quantity,
                ];
            }

            if ($total !== $delivered) {
                $errors["allocations.{$itemId}.total"] = 'Allocations for this item must total '.$delivered.' (currently '.$total.').';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DeliveryReceiptItemAllocation::query()
            ->whereIn('delivery_receipt_item_id', $receiptItems->pluck('id'))
            ->delete();

        foreach ($records as $record) {
            DeliveryReceiptItemAllocation::query()->create($record);
        }
    }

    /**
     * @return Collection<int, DeliveryReceiptItemAllocation>
     */
    public function forDate(CarbonInterface $date): Collection
    {
        return DeliveryReceiptItemAllocation::query()
            ->whereDate('allocation_date', $date->toDateString())
            ->with(['receiptItem.receipt.school', 'receiptItem.item'])
            ->get();
    }
}
