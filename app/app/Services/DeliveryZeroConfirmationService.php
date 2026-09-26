<?php

namespace App\Services;

use App\Models\DeliveryReceiptItemAllocation;
use App\Models\DeliveryReceiptItemZeroConfirmation;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class DeliveryZeroConfirmationService
{
    public function __construct(
        private readonly WorkingDayCalendar $calendar,
        private readonly ItemSupplyPattern $patterns,
    ) {}

    public function confirm(School $school, FeedingItem $item, CarbonInterface $date, string $reason, User $user): DeliveryReceiptItemZeroConfirmation
    {
        $errors = [];

        if ($date->greaterThan(CarbonImmutable::today())) {
            $errors['date'] = 'Zero confirmations cannot be recorded for future dates.';
        }

        if (! $this->calendar->isWorkingDay($date)) {
            $errors['date'] = 'The programme does not deliver on this date.';
        }

        if (! $school->isParticipatingOn($date)) {
            $errors['school_id'] = 'The school is not participating on this date.';
        }

        $supplied = $this->patterns->isSuppliedOn($item, $date);
        if ($supplied !== true) {
            $errors['feeding_item_id'] = $supplied === null
                ? 'The supply pattern for this item is not configured on this date.'
                : 'This item is not scheduled for supply on this date.';
        }

        if (DeliveryReceiptItemZeroConfirmation::query()
            ->where('school_id', $school->id)
            ->whereDate('date', $date->toDateString())
            ->where('feeding_item_id', $item->id)
            ->exists()) {
            $errors['feeding_item_id'] = 'A zero confirmation already exists for this school, date, and item.';
        }

        if (DeliveryReceiptItemAllocation::query()
            ->whereDate('allocation_date', $date->toDateString())
            ->whereHas('receiptItem', fn ($query) => $query->where('feeding_item_id', $item->id))
            ->whereHas('receiptItem.receipt', fn ($query) => $query->where('school_id', $school->id))
            ->exists()) {
            $errors['feeding_item_id'] = 'This item already has a delivery allocation on this date.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return DeliveryReceiptItemZeroConfirmation::query()->create([
            'school_id' => $school->id,
            'date' => $date->toDateString(),
            'feeding_item_id' => $item->id,
            'reason' => $reason,
            'confirmed_by' => $user->id,
        ]);
    }
}
