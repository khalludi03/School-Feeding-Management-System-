<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\User;
use App\Services\DailyReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipationPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'password' => 'password']);
    }

    // US2.4-AC1

    public function test_a_new_school_defaults_its_participation_start_to_today(): void
    {
        $this->actingAs($this->admin)
            ->get(route('schools.create'))
            ->assertOk()
            ->assertSee('name="participation_starts_on" type="date" required value="'.today()->toDateString().'"', false);
    }

    public function test_a_new_school_accepts_a_future_participation_start_and_generates_no_demand_before_it(): void
    {
        $start = CarbonImmutable::today()->addDays(10);
        $this->actingAs($this->admin)
            ->post(route('schools.store'), $this->schoolData(['participation_starts_on' => $start->toDateString()]))
            ->assertRedirect();

        $school = School::firstOrFail();

        $this->assertSame($start->toDateString(), $school->participationPeriods()->firstOrFail()->starts_on->toDateString());
        $this->assertFalse($school->isParticipatingOn($start->subDay()));
        $this->assertTrue($school->isParticipatingOn($start));

        $this->openCycle();
        $report = app(DailyReportService::class)->forDate($start->subDay());
        $this->assertSame([], $report['rows'], 'A school before its start date must not appear in a report.');
    }

    public function test_a_participation_start_before_today_is_rejected_with_a_field_error(): void
    {
        // Past starts stay impossible through the create form, which only ever offers today or later.
        $this->actingAs($this->admin)
            ->from(route('schools.create'))
            ->post(route('schools.store'), $this->schoolData(['participation_starts_on' => 'not-a-date']))
            ->assertSessionHasErrors('participation_starts_on');

        $this->assertDatabaseCount('schools', 0);
    }

    // US2.4-AC2

    public function test_deactivation_from_a_date_stops_demand_from_that_date_and_keeps_earlier_records_reportable(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, 100);
        $item = $this->item($cycle);
        $effectiveOn = CarbonImmutable::today()->addDays(5);

        $receipt = $this->receipt($school, $effectiveOn->subDay()->toDateString());
        $this->line($receipt, $item, 90);

        $this->actingAs($this->admin)
            ->post(route('schools.deactivate', $school), [
                'effective_on' => $effectiveOn->toDateString(),
                'reason' => 'School closed after the feeder vehicle stopped',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('schools.show', $school));

        $period = $school->participationPeriods()->firstOrFail();
        $this->assertSame(
            $effectiveOn->subDay()->toDateString(),
            $period->ends_on->toDateString(),
            'The day before the effective date is the last day the school participates.',
        );
        $school->refresh();
        $this->assertFalse($school->is_active);

        $lastDay = $effectiveOn->subDay();
        $this->assertTrue($school->isParticipatingOn($lastDay));
        $this->assertFalse($school->isParticipatingOn($effectiveOn));

        $report = app(DailyReportService::class);
        $this->assertCount(1, $report->forDate($lastDay)['rows'], 'Earlier demand must survive deactivation.');
        $this->assertSame(90, $report->forDate($lastDay)['rows'][0]['delivered']['bread']);
        $this->assertSame([], $report->forDate($effectiveOn)['rows'], 'No demand from the effective date onwards.');
    }

    public function test_deactivation_rejects_a_date_before_today(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, 100);

        $this->actingAs($this->admin)
            ->from(route('schools.deactivate.confirm', $school))
            ->post(route('schools.deactivate', $school), [
                'effective_on' => CarbonImmutable::today()->subDay()->toDateString(),
                'reason' => 'Trying to backdate the stop',
                'current_password' => 'password',
            ])
            ->assertSessionHasErrors('effective_on');

        $school->refresh();
        $this->assertTrue($school->is_active, 'A rejected deactivation must leave the school active.');
        $this->assertNull($school->participationPeriods()->firstOrFail()->ends_on);
    }

    public function test_deactivation_audits_the_effective_date_and_the_last_participating_day(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, 100);

        $this->actingAs($this->admin)->post(route('schools.deactivate', $school), [
            'effective_on' => CarbonImmutable::today()->addDays(3)->toDateString(),
            'reason' => 'Programme ended for this school',
            'current_password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'action' => 'school_deactivated',
        ]);
    }

    // US2.4-AC3

    public function test_deactivation_is_blocked_when_a_receipt_falls_on_or_after_the_effective_date(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, 100);
        $staff = User::factory()->create(['role' => 'field_staff']);
        $effectiveOn = CarbonImmutable::today()->addDays(5);
        $this->receipt($school, $effectiveOn->toDateString(), $staff);

        $this->actingAs($this->admin)
            ->post(route('schools.deactivate', $school), [
                'effective_on' => $effectiveOn->toDateString(),
                'reason' => 'Closing the school',
                'current_password' => 'password',
            ])
            ->assertSessionHasErrors('effective_on');

        $school->refresh();
        $this->assertTrue($school->is_active, 'A blocked deactivation must leave the school active.');
        $this->assertNull($school->participationPeriods()->firstOrFail()->ends_on);
    }

    public function test_the_conflict_preview_names_every_affected_record_and_its_responsible_staff(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, 100);
        $staff = User::factory()->create(['role' => 'field_staff', 'name' => 'Rahima Begum']);
        $effectiveOn = CarbonImmutable::today()->addDays(5);
        $this->receipt($school, $effectiveOn->toDateString(), $staff);
        $this->receipt($school, $effectiveOn->addDay()->toDateString(), $staff);

        $this->actingAs($this->admin)
            ->get(route('schools.deactivate.confirm', ['school' => $school, 'effective_on' => $effectiveOn->toDateString()]))
            ->assertOk()
            ->assertSee('This date would strand 2 existing record(s).')
            ->assertSee('Rahima Begum')
            ->assertSee($effectiveOn->toDateString());
    }

    public function test_a_receipt_before_the_effective_date_does_not_block_deactivation(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, 100);
        $this->receipt($school, today()->toDateString());

        $this->actingAs($this->admin)->post(route('schools.deactivate', $school), [
            'effective_on' => CarbonImmutable::today()->addDays(5)->toDateString(),
            'reason' => 'Closing after the last delivery',
            'current_password' => 'password',
        ])->assertRedirect();

        $this->assertFalse($school->fresh()->is_active);
    }

    // US2.4-AC4

    public function test_a_deactivated_school_still_appears_in_reports_for_the_period_it_participated(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, 100);
        $item = $this->item($cycle);
        $historicDate = CarbonImmutable::today()->subDays(3);

        $receipt = $this->receipt($school, $historicDate->toDateString());
        $this->line($receipt, $item, 88);

        $this->actingAs($this->admin)->post(route('schools.deactivate', $school), [
            'effective_on' => today()->toDateString(),
            'reason' => 'School closed today',
            'current_password' => 'password',
        ])->assertRedirect();

        $school->refresh();
        $this->assertFalse($school->is_active, 'The school is no longer in the active directory.');

        $report = app(DailyReportService::class)->forDate($historicDate);

        $this->assertCount(1, $report['rows'], 'A deactivated school must stay in reports for dates it participated.');
        $this->assertSame($school->id, $report['rows'][0]['school']->id);
        $this->assertSame(88, $report['rows'][0]['delivered']['bread'], 'Historic quantities stay intact.');
        $this->assertSame(90, $report['totals']['demand']['bread']);
    }

    public function test_a_historic_delivery_entry_can_still_be_corrected_after_deactivation(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, 100);
        $item = $this->item($cycle);
        $staff = User::factory()->create(['role' => 'field_staff']);
        $historicDate = CarbonImmutable::today()->subDays(2);
        $receipt = $this->receipt($school, $historicDate->toDateString(), $staff);
        $this->line($receipt, $item, 70);

        $this->actingAs($this->admin)->post(route('schools.deactivate', $school), [
            'effective_on' => today()->toDateString(),
            'reason' => 'School closed today',
            'current_password' => 'password',
        ])->assertRedirect();

        $this->actingAs($staff)
            ->put(route('field.delivery.update', $receipt), [
                'delivery_date' => $historicDate->toDateString(),
                'school_id' => $school->id,
                'quantities' => [$item->id => 75],
                'notes' => 'Corrected after the school closed',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('delivery_receipt_items', [
            'delivery_receipt_id' => $receipt->id,
            'delivered_quantity' => 75,
        ]);
    }

    public function test_a_school_cannot_receive_a_new_entry_on_a_date_after_its_deactivation(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, 100);
        $item = $this->item($cycle);
        $staff = User::factory()->create(['role' => 'field_staff']);

        $this->actingAs($this->admin)->post(route('schools.deactivate', $school), [
            'effective_on' => today()->toDateString(),
            'reason' => 'School closed today',
            'current_password' => 'password',
        ])->assertRedirect();

        $this->actingAs($staff)
            ->post(route('field.delivery.create'), [
                'delivery_date' => today()->toDateString(),
                'school_id' => $school->id,
                'quantities' => [$item->id => 80],
            ])
            ->assertSessionHasErrors('school_id');

        $this->assertDatabaseCount('delivery_receipts', 0);
    }

    // Reactivation

    public function test_reactivation_opens_a_new_period_and_the_gap_generates_no_demand(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, 100);

        $this->actingAs($this->admin)->post(route('schools.deactivate', $school), [
            'effective_on' => today()->toDateString(),
            'reason' => 'Closed for repairs',
            'current_password' => 'password',
        ])->assertRedirect();

        $reopenOn = CarbonImmutable::today()->addDays(10);
        $this->actingAs($this->admin)->post(route('schools.reactivate', $school), [
            'effective_on' => $reopenOn->toDateString(),
            'reason' => 'Repairs complete',
            'current_password' => 'password',
        ])->assertRedirect();

        $school->refresh();
        $this->assertTrue($school->is_active);
        $this->assertCount(2, $school->participationPeriods, 'The closed period is kept, not overwritten.');
        $this->assertTrue($school->isParticipatingOn($reopenOn));
        $this->assertFalse($school->isParticipatingOn($reopenOn->subDay()), 'The gap generates no demand.');

        $this->assertFalse($school->hasOverlappingParticipationPeriods());
    }

    private function openCycle(): FeedingCycle
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => CarbonImmutable::today()->subMonth()->toDateString(),
            'ends_on' => CarbonImmutable::today()->addMonth()->toDateString(),
        ]);
        $cycle->forceFill(['ration_factor' => 0.9])->save();

        return $cycle->refresh();
    }

    private function item(FeedingCycle $cycle): FeedingItem
    {
        return FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'bread', 'name' => 'Bread', 'sort_order' => 1, 'supply_days' => 16]);
    }

    private function participatingSchool(FeedingCycle $cycle, int $pupils): School
    {
        $school = School::factory()->create(['emis_code' => '12345678901']);
        $school->participationPeriods()->create(['starts_on' => $cycle->starts_on->toDateString()]);
        $school->enrolments()->create([
            'effective_on' => $cycle->starts_on->toDateString(),
            'pupil_count' => $pupils,
            'source' => 'test',
        ]);

        return $school->refresh();
    }

    private function receipt(School $school, string $date, ?User $staff = null): DeliveryReceipt
    {
        $receipt = new DeliveryReceipt;
        $receipt->school_id = $school->id;
        $receipt->delivery_date = $date;
        $receipt->entered_by = ($staff ?? User::factory()->create(['role' => 'field_staff']))->id;
        $receipt->save();

        return $receipt;
    }

    private function line(DeliveryReceipt $receipt, FeedingItem $item, int $quantity): void
    {
        $line = new DeliveryReceiptItem;
        $line->delivery_receipt_id = $receipt->id;
        $line->feeding_item_id = $item->id;
        $line->delivered_quantity = $quantity;
        $line->save();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function schoolData(array $overrides = []): array
    {
        return array_merge([
            'bangla_name' => 'আনোয়ারা পরীক্ষা বিদ্যালয়',
            'emis_code' => '12345678901',
            'emis_source' => 'Official school list',
            'emis_verified' => '1',
            'enrolment_count' => 100,
            'enrolment_effective_on' => today()->toDateString(),
            'participation_starts_on' => today()->toDateString(),
        ], $overrides);
    }
}
