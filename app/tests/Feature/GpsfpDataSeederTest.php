<?php

namespace Tests\Feature;

use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\SchoolPlanningSnapshot;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\GpsfpSeptember2026Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertMatchesRegularExpression('/^880[1-9]\d{9}$/', $firstSchool->teacher_phone);
        $this->assertMatchesRegularExpression('/^\d{11}$/', $firstSchool->emis_code);
        $this->assertTrue($firstSchool->is_active);
        $this->assertTrue($firstSchool->emis_is_provisional);
        $this->assertSame(110, School::query()->whereNotNull('emis_code')->distinct()->count('emis_code'));
        $this->assertSame(110, School::query()->where('emis_is_provisional', true)->count());

        $firstSnapshot = SchoolPlanningSnapshot::query()
            ->where('feeding_cycle_id', $cycle->id)
            ->where('school_id', $firstSchool->id)
            ->firstOrFail();
        $this->assertSame(191, $firstSnapshot->pupil_count);
        $this->assertSame(171.9, (float) $firstSnapshot->target_pupil_count);
        $this->assertSame(2752, $firstSnapshot->bread_quantity);
        $this->assertSame(2064, $firstSnapshot->egg_quantity);
        $this->assertSame(860, $firstSnapshot->banana_quantity);
        $this->assertSame('GPSFP_School_List_Anwara_Upazila.md', $firstSnapshot->source_file);

        $this->assertEquals(22054, SchoolPlanningSnapshot::query()->sum('pupil_count'));
        $this->assertEquals(317664, SchoolPlanningSnapshot::query()->sum('bread_quantity'));
        $this->assertEquals(238248, SchoolPlanningSnapshot::query()->sum('egg_quantity'));
        $this->assertEquals(99270, SchoolPlanningSnapshot::query()->sum('banana_quantity'));

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
        $firstSchool->update(['bangla_name' => 'Admin-corrected school name']);
        $snapshot = $firstSchool->planningSnapshots()->firstOrFail();
        $snapshot->update(['pupil_count' => 999]);

        $this->seed(GpsfpSeptember2026Seeder::class);

        $this->assertSame('Admin-corrected school name', $firstSchool->fresh()->bangla_name);
        $this->assertArrayHasKey('source_drift', $firstSchool->planningSnapshots()->firstOrFail()->source_flags);
        $this->assertSame(110, School::query()->count());
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
