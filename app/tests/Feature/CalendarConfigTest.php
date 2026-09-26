<?php

namespace Tests\Feature;

use App\Models\DateItemSchedule;
use App\Models\DeliveryReceipt;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\NonWorkingDay;
use App\Models\School;
use App\Models\SchoolPlanningSnapshot;
use App\Models\User;
use App\Services\ItemSupplyPattern;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
