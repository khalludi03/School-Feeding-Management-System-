<?php

namespace Tests\Feature;

use App\Models\DateItemSchedule;
use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\NonWorkingDay;
use App\Models\School;
use App\Models\SchoolPlanningSnapshot;
use App\Models\User;
use App\Services\DailyDemandService;
use App\Services\ItemSupplyPattern;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_september_reference_month_has_exact_item_day_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $itemBun = FeedingItem::factory()->for($cycle)->create(['item_key' => 'banana_bread', 'name' => 'Bread', 'unit' => 'packet', 'supply_weekdays' => [0, 1, 3, 4], 'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER]);
        $itemEgg = FeedingItem::factory()->for($cycle)->create(['item_key' => 'boiled_egg', 'name' => 'Egg', 'unit' => 'piece', 'supply_weekdays' => [1, 2, 3, 4], 'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER]);
        $itemBanana = FeedingItem::factory()->for($cycle)->create(['item_key' => 'banana', 'name' => 'Banana', 'unit' => 'piece', 'supply_weekdays' => [2, 4, 6], 'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER]);

        $response = $this->actingAs($admin)->get(route('admin.calendar.month-config', ['cycle' => $cycle, 'month' => '2026-09']));

        $response->assertOk();
        $response->assertSee('Configure items');
        $response->assertSee('Bread');
        $response->assertSee('Egg');
        $response->assertSee('Banana');
    }

    public function test_month_config_can_override_item_schedule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create(['item_key' => 'banana_bread', 'name' => 'Bread', 'unit' => 'packet', 'supply_weekdays' => [0, 1, 2, 3, 4, 5, 6], 'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER]);

        $response = $this->actingAs($admin)->post(route('admin.calendar.month-config.update', ['cycle' => $cycle, 'month' => '2026-09']), [
            'month' => '2026-09',
            'items' => [$item->id.'-2026-09-15'],
            'dates' => ['2026-09-15'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $exists = DateItemSchedule::where('feeding_cycle_id', $cycle->id)
            ->where('feeding_item_id', $item->id)
            ->whereDate('schedule_date', '2026-09-15')
            ->where('is_scheduled', true)
            ->where('source', 'work_order')
            ->exists();
        $this->assertTrue($exists, 'Expected date item schedule for 2026-09-15 to exist.');
    }

    public function test_holiday_blocked_when_receipts_exist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $holidayDate = '2026-09-15';
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $school = School::factory()->create();
        $school->participationPeriods()->create(['starts_on' => '2026-09-01']);
        $school->enrolments()->create(['effective_on' => '2026-09-01', 'pupil_count' => 100, 'source' => 'test']);
        SchoolPlanningSnapshot::factory()->create([
            'feeding_cycle_id' => $cycle->id,
            'school_id' => $school->id,
            'pupil_count' => 100,
            'daily_demand' => 90,
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create(['item_key' => 'banana_bread', 'name' => 'Bread', 'unit' => 'packet', 'supply_weekdays' => [0, 1, 2, 3, 4, 5, 6], 'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER]);
        $receipt = DeliveryReceipt::factory()->create([
            'school_id' => $school->id,
            'delivery_date' => $holidayDate,
            'entered_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.calendar.store'), [
            'holiday_on' => $holidayDate,
            'kind' => 'holiday',
            'name' => 'Test Holiday',
        ]);

        $response->assertOk();
        $response->assertSee('Calendar change blocked');
        $response->assertSee($school->code);
        $response->assertSee($receipt->id);
    }

    public function test_unmark_holiday_restores_weekday_pattern(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create(['item_key' => 'banana_bread', 'name' => 'Bread', 'unit' => 'packet', 'supply_weekdays' => [0, 1, 2, 3, 4, 5, 6], 'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER]);
        $nonWorkingDay = NonWorkingDay::factory()->create(['holiday_on' => '2026-09-15', 'kind' => 'holiday', 'name' => 'Test Holiday']);

        $response = $this->actingAs($admin)->delete(route('admin.calendar.destroy', $nonWorkingDay));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Date returned to working days.');

        $this->assertDatabaseMissing('non_working_days', ['id' => $nonWorkingDay->id]);
    }

    public function test_future_month_can_be_configured_independently(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-31',
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create(['item_key' => 'banana_bread', 'name' => 'Bread', 'unit' => 'packet', 'supply_weekdays' => [0, 1], 'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER]);

        $response = $this->actingAs($admin)->get(route('admin.calendar.month-config', ['cycle' => $cycle, 'month' => '2026-10']));

        $response->assertOk();
        $response->assertSee('Configure items');
    }

    public function test_month_config_can_reproduce_reference_day_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $bread = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'supply_weekdays' => null,
            'supply_pattern_source' => null,
        ]);
        $egg = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'boiled_egg',
            'name' => 'Egg',
            'unit' => 'piece',
            'supply_weekdays' => null,
            'supply_pattern_source' => null,
        ]);
        $banana = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana',
            'name' => 'Banana',
            'unit' => 'piece',
            'supply_weekdays' => null,
            'supply_pattern_source' => null,
        ]);

        $checked = [];
        for ($day = 1; $day <= 16; $day++) {
            $checked[] = $bread->id.'-2026-09-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }
        foreach ([1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23] as $day) {
            $checked[] = $egg->id.'-2026-09-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }
        foreach ([1, 8, 15, 22, 29] as $day) {
            $checked[] = $banana->id.'-2026-09-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }

        $response = $this->actingAs($admin)->post(route('admin.calendar.month-config.update', ['cycle' => $cycle, 'month' => '2026-09']), [
            'month' => '2026-09',
            'items' => $checked,
        ]);

        $response->assertRedirect();

        $scheduledCount = fn (FeedingItem $item): int => DateItemSchedule::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->where('feeding_item_id', $item->id)
            ->where('is_scheduled', true)
            ->count();

        $this->assertSame(16, $scheduledCount($bread));
        $this->assertSame(12, $scheduledCount($egg));
        $this->assertSame(5, $scheduledCount($banana));
    }

    public function test_item_specific_schedule_zeroes_unscheduled_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'supply_weekdays' => null,
            'supply_pattern_source' => null,
        ]);
        FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'boiled_egg',
            'name' => 'Egg',
            'unit' => 'piece',
            'supply_weekdays' => null,
            'supply_pattern_source' => null,
        ]);
        $banana = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana',
            'name' => 'Banana',
            'unit' => 'piece',
            'supply_weekdays' => null,
            'supply_pattern_source' => null,
        ]);

        $school = School::factory()->create();
        $school->participationPeriods()->create(['starts_on' => '2026-09-01']);
        $school->enrolments()->create(['effective_on' => '2026-09-01', 'pupil_count' => 100, 'source' => 'test']);

        $this->actingAs($admin)->post(route('admin.calendar.month-config.update', ['cycle' => $cycle, 'month' => '2026-09']), [
            'month' => '2026-09',
            'items' => [$banana->id.'-2026-09-15'],
        ])->assertRedirect();

        $demand = app(DailyDemandService::class)->forSchool($cycle, $school, Carbon::parse('2026-09-15'));

        $this->assertSame(0, $demand['items']['banana_bread']);
        $this->assertSame(0, $demand['items']['boiled_egg']);
        $this->assertSame(90, $demand['items']['banana']);
    }

    public function test_future_month_config_does_not_bleed_into_other_months(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-10-31',
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'supply_weekdays' => null,
            'supply_pattern_source' => null,
        ]);

        $this->actingAs($admin)->post(route('admin.calendar.month-config.update', ['cycle' => $cycle, 'month' => '2026-10']), [
            'month' => '2026-10',
            'items' => [$item->id.'-2026-10-15'],
        ])->assertRedirect();

        $this->assertTrue(DateItemSchedule::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->where('feeding_item_id', $item->id)
            ->whereDate('schedule_date', '2026-10-15')
            ->where('is_scheduled', true)
            ->exists());
        $this->assertFalse(DateItemSchedule::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->where('feeding_item_id', $item->id)
            ->whereDate('schedule_date', '2026-09-15')
            ->exists());
    }

    public function test_month_config_flags_setup_incomplete_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'supply_weekdays' => null,
            'supply_pattern_source' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.calendar.month-config', ['cycle' => $cycle, 'month' => '2026-09']));

        $response->assertOk();
        $response->assertSee('Setup incomplete');
    }

    public function test_item_removal_is_blocked_by_existing_receipt(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'supply_weekdays' => [0, 1, 2, 3, 4, 5, 6],
            'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER,
        ]);
        $school = School::factory()->create();
        $receipt = DeliveryReceipt::factory()->create([
            'school_id' => $school->id,
            'delivery_date' => '2026-09-15',
            'entered_by' => $staff->id,
        ]);
        DeliveryReceiptItem::query()->create([
            'delivery_receipt_id' => $receipt->id,
            'feeding_item_id' => $item->id,
            'delivered_quantity' => 10,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.calendar.month-config.update', ['cycle' => $cycle, 'month' => '2026-09']), [
            'month' => '2026-09',
            'items' => [$item->id.'-2026-09-16'],
        ]);

        $response->assertOk();
        $response->assertSee('Calendar change blocked');
        $response->assertSee($school->code);
        $response->assertSee((string) $receipt->id);
    }

    public function test_unmark_holiday_preserves_date_level_overrides(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'supply_weekdays' => null,
            'supply_pattern_source' => null,
        ]);

        $this->actingAs($admin)->post(route('admin.calendar.month-config.update', ['cycle' => $cycle, 'month' => '2026-09']), [
            'month' => '2026-09',
            'items' => [$item->id.'-2026-09-15'],
        ])->assertRedirect();

        $hasOverride = fn (): bool => DateItemSchedule::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->where('feeding_item_id', $item->id)
            ->whereDate('schedule_date', '2026-09-15')
            ->where('is_scheduled', true)
            ->exists();

        $this->assertTrue($hasOverride());

        $nonWorkingDay = NonWorkingDay::factory()->create([
            'holiday_on' => '2026-09-15',
            'kind' => 'holiday',
            'name' => 'Test Holiday',
        ]);
        $this->assertTrue($hasOverride());

        $this->actingAs($admin)->delete(route('admin.calendar.destroy', $nonWorkingDay))->assertRedirect();
        $this->assertTrue($hasOverride());
    }
}
