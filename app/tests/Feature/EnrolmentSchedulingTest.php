<?php

namespace Tests\Feature;

use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\SchoolEnrolment;
use App\Models\SchoolPlanningSnapshot;
use App\Models\User;
use App\Services\EnrolmentChangeImpactService;
use App\Services\EnrolmentProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class EnrolmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_schedules_a_future_count_through_review_and_confirm(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->withCount(191, $school, today()->subDays(10)->toDateString());
        $cycle = $this->openCycle(rationFactor: 0.9, startsOn: today(), endsOn: today()->addDays(20));

        $effectiveOn = today()->addDays(5)->toDateString();

        $reviewUrl = $this->reviewUrlFor($admin, $school, [
            'effective_on' => $effectiveOn,
            'pupil_count' => 200,
            'reason' => 'District verification visit.',
        ]);

        $this->assertDatabaseMissing('school_enrolments', [
            'school_id' => $school->id,
            'pupil_count' => 200,
        ]);

        $this->actingAs($admin)->get($reviewUrl)->assertOk()
            ->assertSee('Review the enrolment change')
            ->assertSee('Scheduled')
            ->assertSee('District verification visit.')
            ->assertSee('+8 more per day');

        $token = basename(parse_url($reviewUrl, PHP_URL_PATH));

        $this->actingAs($admin)->post(route('schools.enrolments.store', $school), [
            'token' => $token,
            'effective_on' => $effectiveOn,
            'pupil_count' => 200,
            'reason' => 'District verification visit.',
        ])->assertRedirect(route('schools.show', $school));

        $enrolment = $school->enrolments()->whereDate('effective_on', $effectiveOn)->sole();
        $this->assertSame(200, $enrolment->pupil_count);
        $this->assertSame('District verification visit.', $enrolment->reason);
        $this->assertSame($admin->id, $enrolment->recorded_by);
        $this->assertNull($enrolment->cancelled_at);
        $this->assertSame($effectiveOn, $enrolment->active_key);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'action' => 'enrolment_change_scheduled',
        ]);

        $this->actingAs($admin)->get(route('schools.show', $school))->assertOk()
            ->assertSee('Next change '.now()->addDays(5)->format('j M Y'))
            ->assertSee('Cancel this change');
    }

    public function test_confirming_the_same_review_twice_records_only_one_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->withCount(100, $school, today()->subDays(10)->toDateString());
        $this->openCycle(rationFactor: 0.9, startsOn: today(), endsOn: today()->addDays(20));

        $effectiveOn = today()->addDays(3)->toDateString();
        $payload = [
            'effective_on' => $effectiveOn,
            'pupil_count' => 150,
            'reason' => 'Duplicate submission guard.',
        ];

        $reviewUrl = $this->reviewUrlFor($admin, $school, $payload);
        $token = basename(parse_url($reviewUrl, PHP_URL_PATH));

        $this->actingAs($admin)->post(route('schools.enrolments.store', $school), ['token' => $token] + $payload)
            ->assertRedirect(route('schools.show', $school));

        $this->actingAs($admin)->post(route('schools.enrolments.store', $school), ['token' => $token] + $payload)
            ->assertRedirect(route('schools.enrolments.create', $school))
            ->assertSessionHasErrors('effective_on');

        $this->assertSame(1, $school->enrolments()->where('pupil_count', 150)->count());
    }

    public function test_an_unknown_review_token_returns_to_the_form_instead_of_failing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();

        $this->actingAs($admin)
            ->get(route('schools.enrolments.review.show', ['school' => $school, 'token' => str_repeat('a', 48)]))
            ->assertRedirect(route('schools.enrolments.create', $school))
            ->assertSessionHasErrors('effective_on');
    }

    public function test_confirming_values_that_were_not_the_ones_reviewed_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->withCount(100, $school, today()->subDays(10)->toDateString());
        $this->openCycle(rationFactor: 0.9, startsOn: today(), endsOn: today()->addDays(20));

        $reviewed = [
            'effective_on' => today()->addDays(3)->toDateString(),
            'pupil_count' => 150,
            'reason' => 'District verification visit.',
        ];

        $reviewUrl = $this->reviewUrlFor($admin, $school, $reviewed);
        $token = basename(parse_url($reviewUrl, PHP_URL_PATH));

        $this->actingAs($admin)->post(route('schools.enrolments.store', $school), [
            'token' => $token,
            ...$reviewed,
            'pupil_count' => 4000,
        ])->assertRedirect(route('schools.enrolments.create', $school))
            ->assertSessionHasErrors('effective_on');

        $this->assertSame(0, $school->enrolments()->where('pupil_count', 4000)->count());
        $this->assertDatabaseMissing('audit_events', ['action' => 'enrolment_change_scheduled']);
    }

    public function test_backdating_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();

        $this->actingAs($admin)->post(route('schools.enrolments.review', $school), [
            'effective_on' => today()->subDay()->toDateString(),
            'pupil_count' => 120,
            'reason' => 'Attempt to backdate.',
        ])->assertSessionHasErrors('effective_on');

        $this->assertDatabaseMissing('school_enrolments', ['pupil_count' => 120]);
    }

    public function test_a_duplicate_active_date_is_rejected_on_its_own_field(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $target = today()->addDays(4)->toDateString();
        $this->withCount(100, $school, today()->subDays(10)->toDateString());
        $school->enrolments()->create([
            'effective_on' => $target,
            'pupil_count' => 130,
            'reason' => 'Already scheduled.',
        ]);

        $this->actingAs($admin)->post(route('schools.enrolments.review', $school), [
            'effective_on' => $target,
            'pupil_count' => 140,
            'reason' => 'Second change on the same date.',
        ])->assertSessionHasErrors('effective_on');

        $this->assertSame(1, $school->enrolments()->whereDate('effective_on', $target)->count());
    }

    public function test_a_missing_ration_factor_blocks_the_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->withCount(191, $school, today()->subDays(10)->toDateString());
        $this->openCycle(rationFactor: null, startsOn: today(), endsOn: today()->addDays(20));

        $this->actingAs($admin)->post(route('schools.enrolments.review', $school), [
            'effective_on' => today()->addDays(2)->toDateString(),
            'pupil_count' => 200,
            'reason' => 'Factor has not been assigned.',
        ])->assertSessionHasErrors('effective_on');

        $this->assertDatabaseMissing('school_enrolments', ['pupil_count' => 200]);
    }

    public function test_a_closed_cycle_is_not_reported_as_affected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->withCount(191, $school, today()->subDays(40)->toDateString());
        $this->openCycle(rationFactor: 0.9, startsOn: today()->subDays(20), endsOn: today()->subDay());

        $reviewUrl = $this->reviewUrlFor($admin, $school, [
            'effective_on' => today()->addDays(5)->toDateString(),
            'pupil_count' => 200,
            'reason' => 'After the cycle closed.',
        ]);

        $this->actingAs($admin)->get($reviewUrl)->assertOk()
            ->assertSee('No open feeding cycle is affected');
    }

    public function test_a_scheduled_change_can_be_cancelled_and_replaced_on_the_same_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->withCount(191, $school, today()->subDays(10)->toDateString());
        $this->openCycle(rationFactor: 0.9, startsOn: today(), endsOn: today()->addDays(20));

        $target = today()->addDays(6)->toDateString();
        $enrolment = $school->enrolments()->create([
            'effective_on' => $target,
            'pupil_count' => 250,
            'reason' => 'Provisional figure.',
        ]);

        $this->actingAs($admin)->get(route('schools.enrolments.cancel.confirm', [$school, $enrolment]))->assertOk()
            ->assertSee('Cancel the scheduled change');

        $this->actingAs($admin)->post(route('schools.enrolments.cancel', [$school, $enrolment]), [
            'reason' => 'Superseded by a corrected figure.',
        ])->assertRedirect(route('schools.show', $school));

        $enrolment->refresh();
        $this->assertNotNull($enrolment->cancelled_at);
        $this->assertSame($admin->id, $enrolment->cancelled_by);
        $this->assertSame('Superseded by a corrected figure.', $enrolment->cancellation_reason);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'action' => 'enrolment_change_cancelled',
        ]);

        $replacement = $school->enrolments()->create([
            'effective_on' => $target,
            'pupil_count' => 260,
            'reason' => 'Corrected figure.',
        ]);

        $this->assertNull($replacement->cancelled_at);
        $this->assertSame(191, app(EnrolmentProjectionService::class)->currentCount($school));
    }

    public function test_an_effective_count_cannot_be_rewritten_or_cancelled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $enrolment = $this->withCount(191, $school, today()->subDays(10)->toDateString());

        $this->expectException(LogicException::class);
        $enrolment->update(['pupil_count' => 999]);
    }

    public function test_a_scheduled_count_cannot_be_rewritten_in_place(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $enrolment = $school->enrolments()->create([
            'effective_on' => today()->addDays(5)->toDateString(),
            'pupil_count' => 250,
            'reason' => 'Provisional figure.',
        ]);

        $this->expectException(LogicException::class);
        $enrolment->update(['pupil_count' => 999]);
    }

    public function test_an_effective_count_cannot_be_cancelled_through_the_workflow(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $enrolment = $this->withCount(191, $school, today()->subDays(10)->toDateString());

        $this->actingAs($admin)->get(route('schools.enrolments.cancel.confirm', [$school, $enrolment]))
            ->assertStatus(409);

        $this->actingAs($admin)->post(route('schools.enrolments.cancel', [$school, $enrolment]), [
            'reason' => 'Trying to cancel an applied count.',
        ])->assertSessionHasErrors('reason');

        $this->assertNull($enrolment->refresh()->cancelled_at);
    }

    public function test_inactive_schools_and_non_admins_cannot_schedule_a_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'field_staff']);
        $inactive = School::factory()->create(['is_active' => false]);
        $active = School::factory()->create();
        $this->withCount(100, $active, today()->subDays(10)->toDateString());

        $this->actingAs($admin)->get(route('schools.enrolments.create', $inactive))->assertStatus(409);

        $this->actingAs($admin)->post(route('schools.enrolments.review', $inactive), [
            'effective_on' => today()->addDay()->toDateString(),
            'pupil_count' => 120,
            'reason' => 'Blocked by lifecycle state.',
        ])->assertStatus(409);

        $this->actingAs($staff)->get(route('schools.enrolments.create', $active))->assertForbidden();

        $this->actingAs($staff)->post(route('schools.enrolments.review', $active), [
            'effective_on' => today()->addDay()->toDateString(),
            'pupil_count' => 120,
            'reason' => 'Field staff must not schedule.',
        ])->assertForbidden();

        $this->assertDatabaseMissing('school_enrolments', ['pupil_count' => 120]);
    }

    public function test_the_latest_dated_count_wins_regardless_of_insertion_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->withCount(100, $school, today()->subDays(30)->toDateString());

        $later = $school->enrolments()->create([
            'effective_on' => today()->addDays(9)->toDateString(),
            'pupil_count' => 400,
            'reason' => 'Dated later but recorded first.',
        ]);
        $earlier = $school->enrolments()->create([
            'effective_on' => today()->addDays(2)->toDateString(),
            'pupil_count' => 300,
            'reason' => 'Dated earlier but recorded second.',
        ]);

        $projections = app(EnrolmentProjectionService::class);

        $this->assertSame(100, $projections->currentCount($school));
        $this->assertSame(300, $projections->applicableCountOn($school, today()->addDays(5)));
        $this->assertSame(400, $projections->applicableCountOn($school, today()->addDays(10)));
        $this->assertSame(
            [$earlier->id, $later->id],
            array_map(fn ($e) => $e->id, $projections->scheduledEnrolments($school)),
        );
    }

    public function test_demand_before_the_first_recorded_count_is_unknown_rather_than_zero(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->openCycle(rationFactor: 0.9, startsOn: today()->subDays(5), endsOn: today()->addDays(20));

        $impact = app(EnrolmentChangeImpactService::class)
            ->forChange($school, today()->addDay()->toDateString(), 200);

        $this->assertNull($impact['current_count']);
        $this->assertNull($impact['first_effective_on']);

        $cycleImpact = $impact['cycles'][0];
        $this->assertNull($cycleImpact['before_count']);
        $this->assertNull($cycleImpact['before_daily_demand']);
        $this->assertNull($cycleImpact['daily_demand_delta']);
        $this->assertSame(180, $cycleImpact['after_daily_demand']);

        foreach ($cycleImpact['items'] as $item) {
            $this->assertNull($item['before_quantity'], "{$item['item_key']} must not be reported as zero before any count exists.");
        }
    }

    public function test_demand_is_projected_from_the_latest_dated_count_on_the_evaluation_day(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->withCount(191, $school, today()->subDays(10)->toDateString());
        $school->enrolments()->create([
            'effective_on' => today()->subDays(2)->toDateString(),
            'pupil_count' => 210,
            'reason' => 'Most recent recorded count.',
        ]);
        $this->openCycle(rationFactor: 0.9, startsOn: today()->subDays(5), endsOn: today()->addDays(20));

        $impact = app(EnrolmentChangeImpactService::class)
            ->forChange($school, today()->addDay()->toDateString(), 200);

        $this->assertSame(210, $impact['current_count']);
        $this->assertSame(210, $impact['cycles'][0]['before_count']);
        $this->assertSame(189, $impact['cycles'][0]['before_daily_demand']);
        $this->assertSame(180, $impact['cycles'][0]['after_daily_demand']);
        $this->assertSame(-9, $impact['cycles'][0]['daily_demand_delta']);
    }

    public function test_item_quantities_derive_from_daily_demand_and_supply_days(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->withCount(191, $school, today()->subDays(10)->toDateString());
        $cycle = $this->openCycle(rationFactor: 0.9, startsOn: today(), endsOn: today()->addDays(20));

        FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'banana_bread', 'name' => 'বনরুটি', 'unit' => 'packet',
            'supply_days' => 16, 'sort_order' => 1,
        ]);
        FeedingItem::factory()->for($cycle)->create([
            'item_key' => 'related_service', 'name' => 'রিলেটেড সার্ভিস', 'unit' => 'service',
            'supply_days' => null, 'sort_order' => 2,
        ]);

        $impact = app(EnrolmentChangeImpactService::class)
            ->forChange($school, today()->addDay()->toDateString(), 200);

        $items = collect($impact['cycles'][0]['items'])->keyBy('item_key');

        $this->assertSame(172 * 16, $items['banana_bread']['before_quantity']);
        $this->assertSame(180 * 16, $items['banana_bread']['after_quantity']);
        $this->assertSame(8 * 16, $items['banana_bread']['delta']);
        $this->assertNull($items['related_service']['before_quantity']);
        $this->assertNull($items['related_service']['delta']);
    }

    public function test_a_frozen_snapshot_factor_is_used_instead_of_the_live_cycle_policy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $school = School::factory()->create();
        $this->withCount(200, $school, today()->subDays(10)->toDateString());
        $cycle = $this->openCycle(rationFactor: 0.5, startsOn: today(), endsOn: today()->addDays(20));

        SchoolPlanningSnapshot::factory()->for($cycle)->for($school)->create([
            'ration_factor' => 0.9,
        ]);

        $impact = app(EnrolmentChangeImpactService::class)
            ->forChange($school, today()->addDay()->toDateString(), 200);

        $cycleImpact = $impact['cycles'][0];
        $this->assertSame(0.9, $cycleImpact['ration_factor']);
        $this->assertTrue($cycleImpact['factor_frozen']);
        $this->assertSame(180, $cycleImpact['after_daily_demand']);
    }

    private function withCount(int $count, School $school, string $effectiveOn): SchoolEnrolment
    {
        return $school->enrolments()->create([
            'effective_on' => $effectiveOn,
            'pupil_count' => $count,
            'reason' => 'Baseline for the test.',
        ]);
    }

    private function openCycle(?float $rationFactor, string $startsOn, string $endsOn): FeedingCycle
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
        ]);
        $cycle->forceFill(['ration_factor' => $rationFactor])->save();

        return $cycle->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function reviewUrlFor(User $admin, School $school, array $payload): string
    {
        $response = $this->actingAs($admin)->post(route('schools.enrolments.review', $school), $payload);

        $response->assertSessionHasNoErrors();

        $location = $response->headers->get('Location');
        $this->assertNotNull($location);

        return $location;
    }
}
