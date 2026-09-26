<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\DeliveryReceiptItemAllocation;
use App\Models\DeliveryReceiptItemZeroConfirmation;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\NonWorkingDay;
use App\Models\School;
use App\Models\User;
use App\Services\DailyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZeroConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_confirm_zero_for_a_scheduled_item(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = $this->item($cycle);

        $this->actingAs($staff)->post(route('field.zero-confirmation.store'), [
            'school_id' => $school->id,
            'feeding_item_id' => $item->id,
            'date' => today()->toDateString(),
            'reason' => 'Supplier did not deliver bread today.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('delivery_receipt_item_zero_confirmations', [
            'school_id' => $school->id,
            'feeding_item_id' => $item->id,
            'reason' => 'Supplier did not deliver bread today.',
        ]);

        $row = app(DailyReportService::class)->forDate(today())['rows'][0];
        $this->assertTrue($row['entry_recorded']);
        $this->assertSame(0, $row['delivered']['bread']);
        $this->assertSame(90, $row['shortfall']['bread']);
    }

    public function test_future_dates_are_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = $this->item($cycle);

        $this->actingAs($staff)->post(route('field.zero-confirmation.store'), [
            'school_id' => $school->id,
            'feeding_item_id' => $item->id,
            'date' => today()->addDay()->toDateString(),
            'reason' => 'Too early.',
        ])->assertSessionHasErrors('date');

        $this->assertSame(0, DeliveryReceiptItemZeroConfirmation::query()->count());
    }

    public function test_holidays_are_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = $this->item($cycle);
        NonWorkingDay::factory()->create(['holiday_on' => today()->toDateString()]);

        $this->actingAs($staff)->post(route('field.zero-confirmation.store'), [
            'school_id' => $school->id,
            'feeding_item_id' => $item->id,
            'date' => today()->toDateString(),
            'reason' => 'Holiday.',
        ])->assertSessionHasErrors('date');

        $this->assertSame(0, DeliveryReceiptItemZeroConfirmation::query()->count());
    }

    public function test_non_participating_schools_are_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = School::factory()->create();
        $item = $this->item($cycle);

        $this->actingAs($staff)->post(route('field.zero-confirmation.store'), [
            'school_id' => $school->id,
            'feeding_item_id' => $item->id,
            'date' => today()->toDateString(),
            'reason' => 'Not participating.',
        ])->assertSessionHasErrors('school_id');

        $this->assertSame(0, DeliveryReceiptItemZeroConfirmation::query()->count());
    }

    public function test_unscheduled_items_are_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = FeedingItem::factory()->for($cycle)->withoutSupplyPattern()->create();

        $this->actingAs($staff)->post(route('field.zero-confirmation.store'), [
            'school_id' => $school->id,
            'feeding_item_id' => $item->id,
            'date' => today()->toDateString(),
            'reason' => 'Unscheduled.',
        ])->assertSessionHasErrors('feeding_item_id');

        $this->assertSame(0, DeliveryReceiptItemZeroConfirmation::query()->count());
    }

    public function test_duplicate_zero_confirmations_are_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = $this->item($cycle);

        DeliveryReceiptItemZeroConfirmation::query()->create([
            'school_id' => $school->id,
            'feeding_item_id' => $item->id,
            'date' => today()->toDateString(),
            'reason' => 'First.',
            'confirmed_by' => $staff->id,
        ]);

        $this->actingAs($staff)->post(route('field.zero-confirmation.store'), [
            'school_id' => $school->id,
            'feeding_item_id' => $item->id,
            'date' => today()->toDateString(),
            'reason' => 'Second.',
        ])->assertSessionHasErrors('feeding_item_id');

        $this->assertSame(1, DeliveryReceiptItemZeroConfirmation::query()->count());
    }

    public function test_zero_confirmation_is_rejected_when_an_allocation_exists(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $item = $this->item($cycle);

        $receipt = DeliveryReceipt::factory()->create([
            'school_id' => $school->id,
            'delivery_date' => today()->toDateString(),
            'entered_by' => $staff->id,
            'responsible_by' => $staff->id,
        ]);
        $line = DeliveryReceiptItem::factory()->create([
            'delivery_receipt_id' => $receipt->id,
            'feeding_item_id' => $item->id,
            'delivered_quantity' => 90,
        ]);
        DeliveryReceiptItemAllocation::factory()->create([
            'delivery_receipt_item_id' => $line->id,
            'allocation_date' => today()->toDateString(),
            'allocated_quantity' => 90,
        ]);

        $this->actingAs($staff)->post(route('field.zero-confirmation.store'), [
            'school_id' => $school->id,
            'feeding_item_id' => $item->id,
            'date' => today()->toDateString(),
            'reason' => 'Already supplied.',
        ])->assertSessionHasErrors('feeding_item_id');

        $this->assertSame(0, DeliveryReceiptItemZeroConfirmation::query()->count());
    }

    private function openCycle(): FeedingCycle
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => today()->subDays(5)->toDateString(),
            'ends_on' => today()->addDays(20)->toDateString(),
        ]);
        $cycle->forceFill(['ration_factor' => 0.9])->save();

        return $cycle->refresh();
    }

    private function participatingSchool(FeedingCycle $cycle): School
    {
        $school = School::factory()->create();
        $school->participationPeriods()->create(['starts_on' => $cycle->starts_on->toDateString()]);
        $school->enrolments()->create([
            'effective_on' => $cycle->starts_on->toDateString(),
            'pupil_count' => 100,
            'source' => 'test',
        ]);

        return $school->refresh();
    }

    private function item(FeedingCycle $cycle): FeedingItem
    {
        return FeedingItem::factory()
            ->for($cycle)
            ->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'bread', 'name' => 'Bread', 'sort_order' => 1, 'supply_days' => 16]);
    }
}
