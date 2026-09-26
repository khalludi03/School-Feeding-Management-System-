<?php

namespace Tests\Feature;

use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\NonWorkingDay;
use App\Models\School;
use App\Models\SchoolPlanningSnapshot;
use App\Models\User;
use App\Services\ItemSupplyPattern;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandExplanationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_demand_explanation_for_configured_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $school = School::factory()->create();
        $school->participationPeriods()->create(['starts_on' => '2026-09-01']);
        $school->enrolments()->create([
            'effective_on' => '2026-09-01',
            'pupil_count' => 100,
            'source' => 'test',
        ]);
        SchoolPlanningSnapshot::factory()->create([
            'feeding_cycle_id' => $cycle->id,
            'school_id' => $school->id,
            'pupil_count' => 100,
            'daily_demand' => 90,
            'ration_factor' => 0.900,
        ]);
        $item = FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'supply_days' => 16,
            'supply_weekdays' => [0, 1, 2, 3, 4, 5, 6],
            'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER,
        ]);

        $response = $this->actingAs($admin)->get(route('schools.demand.explain', ['school' => $school, 'date' => '2026-09-15']));

        $response->assertOk();
        $response->assertSee('Demand explanation');
        $response->assertSee('100');
        $response->assertSee('90');
        $response->assertSee('Bread');
    }

    public function test_holiday_shows_zero_demand_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $school = School::factory()->create();
        $school->participationPeriods()->create(['starts_on' => '2026-09-01']);
        $school->enrolments()->create([
            'effective_on' => '2026-09-01',
            'pupil_count' => 100,
            'source' => 'test',
        ]);
        SchoolPlanningSnapshot::factory()->create([
            'feeding_cycle_id' => $cycle->id,
            'school_id' => $school->id,
            'pupil_count' => 100,
            'daily_demand' => 90,
            'ration_factor' => 0.900,
        ]);
        FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'supply_days' => 16,
            'supply_weekdays' => [0, 1, 2, 3, 4, 5, 6],
            'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER,
        ]);
        NonWorkingDay::factory()->create(['holiday_on' => '2026-09-15', 'kind' => 'holiday', 'name' => 'Test Holiday']);

        $response = $this->actingAs($admin)->get(route('schools.demand.explain', ['school' => $school, 'date' => '2026-09-15']));

        $response->assertOk();
        $response->assertSee('Non-working day');
    }

    public function test_missing_supply_pattern_shows_setup_incomplete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $school = School::factory()->create();
        $school->participationPeriods()->create(['starts_on' => '2026-09-01']);
        $school->enrolments()->create([
            'effective_on' => '2026-09-01',
            'pupil_count' => 100,
            'source' => 'test',
        ]);
        SchoolPlanningSnapshot::factory()->create([
            'feeding_cycle_id' => $cycle->id,
            'school_id' => $school->id,
            'pupil_count' => 100,
            'daily_demand' => 90,
            'ration_factor' => 0.900,
        ]);
        FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'boiled_egg',
            'name' => 'Egg',
            'unit' => 'piece',
            'supply_days' => 12,
            'supply_weekdays' => null,
            'supply_pattern_source' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('schools.demand.explain', ['school' => $school, 'date' => '2026-09-15']));

        $response->assertOk();
        $response->assertSee('Setup incomplete');
        $response->assertSee('Supply weekdays not configured');
    }

    public function test_field_staff_can_view_demand_for_participating_school(): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'ration_factor' => 0.900,
        ]);
        $school = School::factory()->create();
        $school->participationPeriods()->create(['starts_on' => '2026-09-01']);
        $school->enrolments()->create([
            'effective_on' => '2026-09-01',
            'pupil_count' => 100,
            'source' => 'test',
        ]);
        SchoolPlanningSnapshot::factory()->create([
            'feeding_cycle_id' => $cycle->id,
            'school_id' => $school->id,
            'pupil_count' => 100,
            'daily_demand' => 90,
            'ration_factor' => 0.900,
        ]);
        FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread',
            'name' => 'Bread',
            'unit' => 'packet',
            'supply_days' => 16,
            'supply_weekdays' => [0, 1, 2, 3, 4, 5, 6],
            'supply_pattern_source' => ItemSupplyPattern::SOURCE_WORK_ORDER,
        ]);

        $response = $this->actingAs($staff)->get(route('field.demand.explain', ['school' => $school, 'date' => '2026-09-15']));

        $response->assertOk();
        $response->assertSee('Demand explanation');
    }
}
