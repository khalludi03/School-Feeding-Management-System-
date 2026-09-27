<?php

namespace Tests\Feature;

use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\SchoolPlanningSnapshot;
use App\Models\User;
use App\Services\DailyReportService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\GpsfpSeptember2026Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GpsfpDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_gpsfp_source_is_imported_as_an_idempotent_planning_dataset(): void
    {
        $this->seed(GpsfpSeptember2026Seeder::class);

        $this->assertDatabaseCount('schools', 110);
        $this->assertDatabaseCount('school_planning_snapshots', 110);
        $this->assertDatabaseCount('feeding_cycles', 1);
        $this->assertDatabaseCount('feeding_items', 4);

        $cycle = FeedingCycle::query()->where('slug', 'gpsfp-2026-09')->firstOrFail();
        $this->assertSame('2026-09-01', $cycle->starts_on->toDateString());
        $this->assertSame('2026-09-30', $cycle->ends_on->toDateString());
        $this->assertSame('1163652', $cycle->tender_id);
        $this->assertSame('56874230.59', $cycle->total_value);
        $this->assertSame(98453, $cycle->regional_daily_quantity);
        $this->assertSame(4, $cycle->items()->count());

        $firstSchool = School::query()->where('source_key', 'gpsfp:anwara:2026-09:001')->firstOrFail();
        $this->assertSame('AN-001', $firstSchool->code);
        $this->assertSame('বৈরাগ সপ্রাবি', $firstSchool->bangla_name);
        $this->assertSame('আনোয়ারা', $firstSchool->upazila);
        $this->assertSame('চট্টগ্রাম', $firstSchool->district);
        $this->assertNull($firstSchool->teacher_phone);
        $this->assertSame('91411060101', $firstSchool->emis_code);
        $this->assertSame('স্কুলের_নাম_ও_EMIS_কোড.md', $firstSchool->emis_source);
        $this->assertTrue($firstSchool->is_active);
        $this->assertNotNull($firstSchool->emis_verified_at);
        $this->assertSame(110, School::query()->whereNotNull('emis_code')->distinct()->count('emis_code'));
        $this->assertSame(110, School::query()->whereNotNull('emis_source')->count());

        $firstSnapshot = SchoolPlanningSnapshot::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->where('school_id', $firstSchool->id)
            ->firstOrFail();
        $this->assertSame(191, $firstSnapshot->pupil_count);
        $this->assertSame(171.9, (float) $firstSnapshot->target_pupil_count);
        $this->assertSame(2752, $firstSnapshot->bread_quantity);
        $this->assertSame(2064, $firstSnapshot->egg_quantity);
        $this->assertSame(860, $firstSnapshot->banana_quantity);
        $this->assertSame('GPSFP_Anwara_school_seed_data.md', $firstSnapshot->source_file);

        $this->assertEquals(21797, SchoolPlanningSnapshot::query()->sum('pupil_count'));
        $this->assertEquals(313952, SchoolPlanningSnapshot::query()->sum('bread_quantity'));
        $this->assertEquals(235464, SchoolPlanningSnapshot::query()->sum('egg_quantity'));
        $this->assertEquals(98110, SchoolPlanningSnapshot::query()->sum('banana_quantity'));

        $bread = FeedingItem::query()->where('item_key', 'banana_bread')->firstOrFail();
        $this->assertSame('22.883', $bread->unit_price);
        $this->assertSame('36046399.98', $bread->total_value);
        $this->assertSame(1575248, $bread->total_quantity);
        $this->assertDatabaseHas('feeding_items', ['item_key' => 'related_service', 'total_quantity' => 3248949]);

        $uncertainPhoneSchool = School::query()->where('source_key', 'gpsfp:anwara:2026-09:010')->firstOrFail();
        $this->assertNull($uncertainPhoneSchool->teacher_phone);
        $this->assertArrayHasKey('teacher_phone', $uncertainPhoneSchool->planningSnapshots()->firstOrFail()->source_flags);
        $this->assertIsString($uncertainPhoneSchool->planningSnapshots()->firstOrFail()->source_payload['raw_row'][3]);

        $row85 = SchoolPlanningSnapshot::query()->where('source_serial', 85)->firstOrFail();
        $this->assertArrayHasKey('target_pupil_count', $row85->source_flags);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('schools.show', $firstSchool))
            ->assertOk()
            ->assertSee('September 2026 feeding plan')
            ->assertSee('2,752');

        $this->seed(GpsfpSeptember2026Seeder::class);
        $this->assertDatabaseCount('schools', 110);
        $this->assertDatabaseCount('school_planning_snapshots', 110);
        $this->assertDatabaseCount('feeding_items', 4);
    }

    public function test_gpsfp_import_preserves_admin_identity_edits_and_flags_source_drift(): void
    {
        $this->seed(GpsfpSeptember2026Seeder::class);
        $firstSchool = School::query()->where('source_key', 'gpsfp:anwara:2026-09:001')->firstOrFail();
        $firstSchool->update([
            'bangla_name' => 'Admin-corrected school name',
            'emis_code' => '91411999999',
            'emis_source' => 'Admin-supplied letter',
        ]);
        $snapshot = $firstSchool->planningSnapshots()->firstOrFail();
        $snapshot->update(['pupil_count' => 999]);

        $this->seed(GpsfpSeptember2026Seeder::class);

        $this->assertSame('Admin-corrected school name', $firstSchool->fresh()->bangla_name);
        $this->assertArrayHasKey('source_drift', $firstSchool->planningSnapshots()->firstOrFail()->source_flags);
        $this->assertSame(110, School::query()->count());
    }

    public function test_the_official_emis_list_reinstates_a_code_that_an_admin_changed(): void
    {
        $this->seed(GpsfpSeptember2026Seeder::class);
        $school = School::query()->where('source_key', 'gpsfp:anwara:2026-09:001')->firstOrFail();
        $school->update(['emis_code' => '91411999999', 'emis_source' => 'Admin-supplied letter']);

        $this->seed(GpsfpSeptember2026Seeder::class);

        // The official list is the only authority for the code, so it wins even over an Admin edit,
        // while a corrected name is left alone.
        $this->assertSame('91411060101', $school->fresh()->emis_code);
        $this->assertSame('স্কুলের_নাম_ও_EMIS_কোড.md', $school->fresh()->emis_source);
        $this->assertNotNull($school->fresh()->emis_verified_at);
        $this->assertDatabaseHas('audit_events', [
            'school_id' => $school->id,
            'action' => 'school_official_identity_imported',
        ]);
    }

    public function test_the_official_emis_list_supplies_every_code_and_repairs_damaged_roster_names(): void
    {
        $this->seed(GpsfpSeptember2026Seeder::class);

        $this->assertSame(0, School::query()->whereNull('emis_code')->count());
        $this->assertSame(0, School::query()->whereNull('emis_verified_at')->count());
        $this->assertSame(110, School::query()->where('emis_source', 'স্কুলের_নাম_ও_EMIS_কোড.md')->count());

        // Serial 80 is the clearest case: the roster recorded the name as illegible, the official list does not.
        $illegible = School::query()->where('source_key', 'gpsfp:anwara:2026-09:080')->firstOrFail();
        $this->assertSame('গুজরা তিশারী সপ্রাবি', $illegible->bangla_name);
        $this->assertSame('91411061007', $illegible->emis_code);

        // A truncated roster name is replaced by the official spelling of the same school.
        $truncated = School::query()->where('source_key', 'gpsfp:anwara:2026-09:027')->firstOrFail();
        $this->assertSame('তুলাতলী আইরমঞ্জল সপ্রাবি', $truncated->bangla_name);
        $this->assertSame('91411060404', $truncated->emis_code);

        // A name that only differs by spelling is still the same school, and keeps its own code.
        $respelled = School::query()->where('source_key', 'gpsfp:anwara:2026-09:015')->firstOrFail();
        $this->assertSame('গন্ডীপ সপ্রাবি', $respelled->bangla_name);
        $this->assertSame('91411060205', $respelled->emis_code);

        $this->assertSame(0, School::query()->where('bangla_name', 'like', '%[কাটা]%')->count());
        $this->assertSame(0, School::query()->where('bangla_name', 'like', '%[অস্পষ্ট]%')->count());
    }

    public function test_imported_schools_are_recorded_as_participating_from_the_first_day_of_the_cycle(): void
    {
        $this->seed(GpsfpSeptember2026Seeder::class);

        $this->assertDatabaseCount('school_participation_periods', 110);

        $school = School::query()->firstOrFail();
        $this->assertSame('2026-09-01', $school->participationPeriods()->firstOrFail()->starts_on->toDateString());
        $this->assertTrue($school->isParticipatingOn(Carbon::parse('2026-09-02')));

        // A pupil count alone must not be enough to generate demand, but the import has to supply both.
        $report = app(DailyReportService::class)->forDate(Carbon::parse('2026-09-02'));
        $this->assertSame(110, $report['totals']['schools']);
        $this->assertSame(
            SchoolPlanningSnapshot::query()->sum('daily_demand'),
            $report['totals']['demand']['banana_bread'],
        );
    }

    public function test_the_participation_period_is_never_rewritten_by_a_reseeding(): void
    {
        $this->seed(GpsfpSeptember2026Seeder::class);
        $school = School::query()->firstOrFail();
        $school->participationPeriods()->update(['ends_on' => '2026-09-10']);

        $this->seed(GpsfpSeptember2026Seeder::class);

        $this->assertDatabaseCount('school_participation_periods', 110);
        $this->assertSame('2026-09-10', $school->participationPeriods()->firstOrFail()->ends_on->toDateString());
    }

    public function test_default_database_seeder_does_not_import_gpsfp_data(): void
    {
        config([
            'sfp.initial_admin.username' => 'initial-admin',
            'sfp.initial_admin.password' => 'initial-password',
            'sfp.demo.enabled' => false,
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('schools', 0);
        $this->assertDatabaseHas('users', ['username' => 'initial-admin', 'role' => 'admin']);
    }
}
