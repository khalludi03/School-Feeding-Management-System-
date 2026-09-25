<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\NonWorkingDay;
use App\Models\School;
use App\Models\User;
use App\Services\DailyDemandService;
use App\Services\ItemSupplyPattern;
use App\Services\WorkingDayCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DeliveryEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_records_a_delivery_with_a_chalan_photo(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [
                (string) $items[0]->id => 170,
                (string) $items[1]->id => 0,
                (string) $items[2]->id => 172,
            ],
            'chalan_photo' => UploadedFile::fake()->image('chalan.jpg'),
        ])->assertRedirect(route('field.entries'))->assertSessionHasNoErrors();

        $receipt = DeliveryReceipt::query()->sole();
        $this->assertSame($school->id, $receipt->school_id);
        $this->assertSame($staff->id, $receipt->entered_by);
        $this->assertNotNull($receipt->chalan_photo_path);
        $this->assertEqualsCanonicalizing(
            ['banana' => 172, 'bread' => 170, 'egg' => 0],
            $receipt->quantitiesByItemKey(),
        );
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'action' => 'delivery_recorded',
        ]);
    }

    public function test_a_duplicate_entry_for_the_same_school_and_date_is_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);
        $this->recordEntry($staff, $school, $items);

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $items[0]->id => 1],
        ])->assertSessionHasErrors('school_id');

        $this->assertSame(1, DeliveryReceipt::query()->count());
    }

    public function test_future_dates_are_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle(endsOn: today()->addDays(10));
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->addDay()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $items[0]->id => 1],
        ])->assertSessionHasErrors('delivery_date');

        $this->assertSame(0, DeliveryReceipt::query()->count());
    }

    public function test_negative_quantities_are_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $items[0]->id => -5],
        ])->assertSessionHasErrors('quantities.'.$items[0]->id);

        $this->assertSame(0, DeliveryReceipt::query()->count());
    }

    public function test_no_entry_is_accepted_on_a_day_the_programme_does_not_deliver(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);

        $holiday = NonWorkingDay::factory()->create(['holiday_on' => today()->toDateString()]);

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => $holiday->holiday_on->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $items[0]->id => 10],
        ])->assertSessionHasErrors('delivery_date');

        $this->assertSame(0, DeliveryReceipt::query()->count());
    }

    public function test_a_school_that_is_not_participating_cannot_receive_an_entry(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = School::factory()->create();
        $items = $this->items($cycle);

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $items[0]->id => 10],
        ])->assertSessionHasErrors('school_id');

        $this->assertSame(0, DeliveryReceipt::query()->count());
    }

    public function test_an_inactive_school_cannot_receive_an_entry(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);
        $school->forceFill(['is_active' => false])->save();

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $items[0]->id => 10],
        ])->assertSessionHasErrors('school_id');
    }

    public function test_a_quantity_for_an_item_outside_the_cycle_is_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);
        $otherCycle = $this->openCycle(endsOn: today()->subYear()->toDateString());
        $otherItem = FeedingItem::factory()->for($otherCycle)->create();

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $otherItem->id => 10],
        ])->assertSessionHasErrors('quantities');
    }

    public function test_staff_can_correct_an_entry_and_the_change_is_audited(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);
        $receipt = $this->recordEntry($staff, $school, $items, [(string) $items[0]->id => 170]);

        $this->actingAs($staff)->put(route('field.delivery.update', $receipt), [
            'delivery_date' => $receipt->delivery_date->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $items[0]->id => 120, (string) $items[1]->id => 0],
            'notes' => 'Two packets short on arrival.',
        ])->assertRedirect(route('field.entries'))->assertSessionHasNoErrors();

        $this->assertSame(120, $receipt->fresh()->quantitiesByItemKey()['bread']);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'action' => 'delivery_corrected',
        ]);
    }

    public function test_staff_cannot_correct_someone_elses_entry(): void
    {
        $owner = User::factory()->create(['role' => 'field_staff']);
        $other = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);
        $receipt = $this->recordEntry($owner, $school, $items);

        $this->actingAs($other)->get(route('field.delivery.edit', $receipt))->assertNotFound();
        $this->actingAs($other)->put(route('field.delivery.update', $receipt), [
            'delivery_date' => $receipt->delivery_date->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $items[0]->id => 999],
        ])->assertNotFound();

        $this->assertSame(170, $receipt->fresh()->quantitiesByItemKey()['bread']);
    }

    public function test_an_entry_cannot_be_moved_to_another_school_or_date(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);
        $receipt = $this->recordEntry($staff, $school, $items);

        $this->expectException(\LogicException::class);

        $receipt->forceFill(['delivery_date' => today()->subDay()])->save();
    }

    public function test_admins_cannot_use_the_field_delivery_entry_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = $this->openCycle();

        $this->actingAs($admin)->get(route('field.delivery.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('field.entries'))->assertForbidden();
    }

    public function test_demand_is_zero_on_a_non_working_day(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $this->items($cycle);
        $date = Carbon::today();

        $before = app(DailyDemandService::class)->forSchool($cycle, $school, $date);
        $this->assertSame(90, $before['daily_demand']);

        NonWorkingDay::factory()->create(['holiday_on' => $date->toDateString()]);

        $after = app(DailyDemandService::class)->forSchool($cycle, $school, $date);
        $this->assertFalse($after['is_working_day']);
        $this->assertNull($after['daily_demand']);
        $this->assertSame(['bread' => null, 'egg' => null, 'banana' => null], $after['items']);
    }

    public function test_an_item_not_supplied_that_weekday_has_zero_demand(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $this->items($cycle, [Carbon::SUNDAY, Carbon::MONDAY, Carbon::WEDNESDAY, Carbon::THURSDAY]);
        $tuesday = Carbon::today()->next(Carbon::TUESDAY);

        $demand = app(DailyDemandService::class)->forSchool($cycle, $school, $tuesday);

        $this->assertSame(90, $demand['daily_demand']);
        $this->assertSame(0, $demand['items']['bread'], 'Bread is not supplied on a Tuesday.');
        $this->assertNull($demand['items']['egg'], 'Egg pattern is unconfigured, so demand is unknown.');
    }

    public function test_bread_is_supplied_on_sunday_monday_wednesday_and_thursday(): void
    {
        $cycle = $this->openCycle();
        $bread = FeedingItem::factory()
            ->for($cycle)
            ->suppliedOn([Carbon::SUNDAY, Carbon::MONDAY, Carbon::WEDNESDAY, Carbon::THURSDAY], ItemSupplyPattern::SOURCE_WORK_ORDER)
            ->create(['item_key' => 'banana_bread', 'supply_days' => 16]);

        $patterns = app(ItemSupplyPattern::class);
        $this->assertTrue($patterns->isSuppliedOn($bread, Carbon::parse('2026-09-06')));  // Sunday
        $this->assertTrue($patterns->isSuppliedOn($bread, Carbon::parse('2026-09-07')));  // Monday
        $this->assertFalse($patterns->isSuppliedOn($bread, Carbon::parse('2026-09-08'))); // Tuesday
        $this->assertTrue($patterns->isSuppliedOn($bread, Carbon::parse('2026-09-09')));  // Wednesday
        $this->assertTrue($patterns->isSuppliedOn($bread, Carbon::parse('2026-09-10')));  // Thursday
        $this->assertFalse($patterns->isSuppliedOn($bread, Carbon::parse('2026-09-11'))); // Friday
    }

    public function test_the_working_day_count_excludes_marked_days(): void
    {
        $calendar = app(WorkingDayCalendar::class);
        $from = Carbon::parse('2026-09-01');
        $to = Carbon::parse('2026-09-30');

        $this->assertSame(30, $calendar->workingDaysBetween($from, $to));

        NonWorkingDay::factory()->create(['holiday_on' => '2026-09-15']);
        NonWorkingDay::factory()->weeklyOff()->create(['holiday_on' => '2026-09-19']);

        $this->assertSame(28, $calendar->workingDaysBetween($from, $to));
        $this->assertCount(28, $calendar->workingDays($from, $to));
        $this->assertFalse($calendar->isWorkingDay(Carbon::parse('2026-09-15')));
    }

    public function test_the_entry_form_lists_participating_schools_only(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $participating = $this->participatingSchool($cycle);
        $absent = School::factory()->create();

        $response = $this->actingAs($staff)->get(route('field.delivery.create', [
            'delivery_date' => today()->toDateString(),
        ]));

        $response->assertOk()->assertSee($participating->code);
        $this->assertStringNotContainsString($absent->code, $response->getContent() ?? '');
    }

    public function test_admin_marks_and_unmarks_a_non_working_day(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.calendar.store'), [
            'holiday_on' => '2026-09-15',
            'kind' => 'holiday',
            'name' => 'Public holiday',
        ])->assertRedirect(route('admin.calendar'))->assertSessionHasNoErrors();

        $marked = NonWorkingDay::query()->sole();
        $this->assertSame('2026-09-15', $marked->holiday_on->toDateString());
        $this->assertSame($admin->id, $marked->recorded_by);

        $this->actingAs($admin)->get(route('admin.calendar'))->assertOk()->assertSee('Public holiday');

        $this->actingAs($admin)->delete(route('admin.calendar.destroy', $marked))->assertRedirect(route('admin.calendar'));
        $this->assertSame(0, NonWorkingDay::query()->count());
    }

    public function test_a_date_cannot_be_marked_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        NonWorkingDay::factory()->create(['holiday_on' => '2026-09-15']);

        $this->actingAs($admin)->post(route('admin.calendar.store'), [
            'holiday_on' => '2026-09-15',
            'kind' => 'holiday',
            'name' => 'Again',
        ])->assertSessionHasErrors('holiday_on');

        $this->assertSame(1, NonWorkingDay::query()->count());
    }

    public function test_a_newly_marked_day_immediately_blocks_a_delivery_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle);
        $items = $this->items($cycle);

        $this->actingAs($admin)->post(route('admin.calendar.store'), [
            'holiday_on' => today()->toDateString(),
            'kind' => 'holiday',
            'name' => 'Declared holiday',
        ])->assertSessionHasNoErrors();

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => [(string) $items[0]->id => 10],
        ])->assertSessionHasErrors('delivery_date');
    }

    public function test_field_staff_cannot_manage_the_calendar(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);

        $this->actingAs($staff)->get(route('admin.calendar'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.calendar.store'), [
            'holiday_on' => '2026-09-15',
            'kind' => 'holiday',
            'name' => 'Nope',
        ])->assertForbidden();
    }

    /**
     * @param  list<string>  $quantities
     */
    private function recordEntry(User $staff, School $school, array $items, array $quantities = []): DeliveryReceipt
    {
        $quantities = $quantities === []
            ? collect($items)->mapWithKeys(fn (FeedingItem $item): array => [(string) $item->id => 170])->all()
            : $quantities;

        $this->actingAs($staff)->post(route('field.delivery.store'), [
            'delivery_date' => today()->toDateString(),
            'school_id' => $school->id,
            'quantities' => $quantities,
        ])->assertSessionHasNoErrors();

        return DeliveryReceipt::query()->latest('id')->firstOrFail();
    }

    private function openCycle(?string $endsOn = null): FeedingCycle
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => today()->subDays(10)->toDateString(),
            'ends_on' => $endsOn ?? today()->addDays(20)->toDateString(),
        ]);
        $cycle->forceFill(['ration_factor' => 0.9])->save();

        return $cycle->refresh();
    }

    /**
     * Egg and banana deliberately ship without a supply pattern, mirroring the work order, which gives
     * their totals but never names their weekdays.
     *
     * @param  list<int>|null  $breadWeekdays
     * @return list<FeedingItem>
     */
    private function items(FeedingCycle $cycle, ?array $breadWeekdays = null): array
    {
        $bread = FeedingItem::factory()
            ->for($cycle)
            ->suppliedOn($breadWeekdays ?? [0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'bread', 'name' => 'Bread', 'sort_order' => 1, 'supply_days' => 16]);

        $egg = FeedingItem::factory()
            ->for($cycle)
            ->withoutSupplyPattern()
            ->create(['item_key' => 'egg', 'name' => 'Egg', 'sort_order' => 2, 'supply_days' => 12]);

        $banana = FeedingItem::factory()
            ->for($cycle)
            ->withoutSupplyPattern()
            ->create(['item_key' => 'banana', 'name' => 'Banana', 'sort_order' => 3, 'supply_days' => 5]);

        return [$bread, $egg, $banana];
    }

    private function participatingSchool(FeedingCycle $cycle, int $pupils = 100): School
    {
        $school = School::factory()->create();
        $school->participationPeriods()->create([
            'starts_on' => $cycle->starts_on->toDateString(),
        ]);
        $school->enrolments()->create([
            'effective_on' => $cycle->starts_on->toDateString(),
            'pupil_count' => $pupils,
            'source' => 'test',
        ]);

        return $school->refresh();
    }
}
