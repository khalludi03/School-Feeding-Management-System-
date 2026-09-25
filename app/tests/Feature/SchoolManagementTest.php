<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_schools_with_serial_codes_and_dated_history_without_inventing_emis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = $this->actingAs($admin)->post('/admin/schools', $this->schoolData([
            'bangla_name' => 'আনোয়ার প্রাথমিক বিদ্যালয়',
            'enrolment_count' => '0',
            'enrolment_effective_on' => today()->subMonth()->toDateString(),
            'participation_starts_on' => today()->subMonths(2)->toDateString(),
        ]))->assertRedirect();

        $school = School::firstOrFail();
        $this->assertSame('AN-001', $school->code);
        $this->assertSame('আনোয়ার প্রাথমিক বিদ্যালয়', $school->bangla_name);
        $this->assertNull($school->emis_code);
        $this->assertNull($school->union);
        $this->assertNull($school->teacher_phone);
        $this->assertSame(0, $school->enrolments()->firstOrFail()->pupil_count);
        $this->assertSame(today()->subMonth()->toDateString(), $school->enrolments()->firstOrFail()->effective_on->toDateString());
        $this->assertSame(today()->subMonths(2)->toDateString(), $school->participationPeriods()->firstOrFail()->starts_on->toDateString());
        $this->assertSame($admin->id, $school->enrolments()->firstOrFail()->recorded_by);
        $first->assertRedirect(route('schools.show', $school));

        $this->get(route('schools.show', $school))->assertOk()
            ->assertSee('আনোয়ার প্রাথমিক বিদ্যালয়')
            ->assertSee('Not provided')
            ->assertSee('Pupil breakdown')
            ->assertSee('is unknown; the later count is not backdated');
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $admin->id, 'school_id' => $school->id, 'action' => 'school_created',
        ]);

        $this->post('/admin/schools', $this->schoolData([
            'bangla_name' => 'দ্বিতীয় বিদ্যালয়',
            'participation_starts_on' => today()->addWeek()->toDateString(),
        ]))->assertRedirect();
        $second = School::where('code', 'AN-002')->firstOrFail();
        $this->get(route('schools.show', $second))->assertOk()->assertSee('Not yet participating');
        $this->get('/admin/schools')->assertOk()->assertSee('School directory')->assertSee('AN-001')->assertSee('AN-002');
    }

    public function test_invalid_required_details_or_supplied_code_create_no_school_and_consume_no_serial(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/schools', $this->schoolData([
            'bangla_name' => 'English Only School',
            'enrolment_count' => '-1',
            'enrolment_effective_on' => today()->addDay()->toDateString(),
            'participation_starts_on' => '',
            'code' => 'AN-001',
        ]))->assertSessionHasErrors([
            'bangla_name', 'enrolment_count', 'enrolment_effective_on', 'participation_starts_on', 'code',
        ]);
        $this->assertDatabaseCount('schools', 0);
        $this->assertDatabaseCount('school_enrolments', 0);
        $this->assertDatabaseHas('school_code_sequences', ['prefix' => 'AN', 'next_number' => 1]);

        $this->post('/admin/schools', $this->schoolData())->assertRedirect();
        $this->assertSame('AN-001', School::firstOrFail()->code);
    }

    public function test_existing_serial_is_skipped_and_generated_codes_remain_unique(): void
    {
        School::factory()->create(['code' => 'AN-001']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/schools', $this->schoolData())->assertRedirect();

        $this->assertDatabaseHas('schools', ['code' => 'AN-001']);
        $this->assertDatabaseHas('schools', ['code' => 'AN-002']);
        $this->assertSame(2, School::distinct('code')->count('code'));
    }

    public function test_a_generated_code_cannot_be_changed_through_the_model(): void
    {
        $school = School::factory()->create(['code' => 'AN-001']);

        $this->expectException(\LogicException::class);
        $school->update(['code' => 'AN-099']);
    }

    public function test_verified_emis_requires_an_official_source_and_attestation_and_is_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/schools', $this->schoolData([
            'emis_code' => 'ab-001',
        ]))->assertSessionHasErrors(['emis_source', 'emis_verified']);
        $this->post('/admin/schools', $this->schoolData([
            'emis_code' => ['not-a-code'],
            'emis_source' => 'Untrusted input',
            'emis_verified' => '1',
        ]))->assertSessionHasErrors('emis_code');
        $this->assertDatabaseCount('schools', 0);

        $this->post('/admin/schools', $this->schoolData([
            'emis_code' => 'ab-001',
            'emis_source' => 'Official roster page 4',
            'emis_verified' => '1',
        ]))->assertRedirect();
        $school = School::firstOrFail();
        $this->assertSame('AB-001', $school->emis_code);
        $this->assertSame('Official roster page 4', $school->emis_source);
        $this->assertNotNull($school->emis_verified_at);
        $this->assertSame($admin->id, $school->emis_verified_by);
        $this->get(route('schools.show', $school))->assertOk()->assertSee('AB-001')->assertSee('Official roster page 4');

        $this->post('/admin/schools', $this->schoolData([
            'emis_code' => 'ab-001',
            'emis_source' => 'Another official document',
            'emis_verified' => '1',
        ]))->assertSessionHasErrors('emis_code');
        $this->assertDatabaseCount('schools', 1);
        $this->assertDatabaseHas('school_code_sequences', ['prefix' => 'AN', 'next_number' => 2]);
    }

    public function test_optional_details_remain_nullable_and_teacher_phone_is_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/schools', $this->schoolData([
            'teacher_phone' => '12345',
        ]))->assertSessionHasErrors('teacher_phone');

        $this->post('/admin/schools', $this->schoolData([
            'union' => 'দক্ষিণ ইউনিয়ন',
            'cluster' => 'Cluster A',
            'teacher_name' => 'রহিমা',
            'teacher_phone' => '+880 1712-345678',
        ]))->assertRedirect();
        $school = School::firstOrFail();
        $this->assertSame('8801712345678', $school->teacher_phone);
        $this->assertSame('দক্ষিণ ইউনিয়ন', $school->union);
        $this->assertSame('Cluster A', $school->cluster);
        $this->get(route('schools.show', $school))->assertOk()->assertSee('রহিমা')->assertSee('Not provided');
    }

    public function test_identity_corrections_are_audited_but_code_and_dated_history_cannot_be_overwritten(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/schools', $this->schoolData())->assertRedirect();
        $school = School::firstOrFail();
        $originalEnrolment = $school->enrolments()->firstOrFail()->pupil_count;
        $originalStart = $school->participationPeriods()->firstOrFail()->starts_on->toDateString();

        $this->put(route('schools.update', $school), $this->identityData([
            'bangla_name' => 'সংশোধিত বিদ্যালয়',
            'union' => 'New union',
            'emis_code' => 'EM-002',
            'emis_source' => 'Signed official letter',
            'emis_verified' => '1',
        ]))->assertRedirect(route('schools.show', $school));
        $school->refresh();
        $this->assertSame('AN-001', $school->code);
        $this->assertSame('সংশোধিত বিদ্যালয়', $school->bangla_name);
        $this->assertSame('EM-002', $school->emis_code);
        $audit = AuditEvent::where('action', 'school_identity_updated')->firstOrFail();
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertSame($school->id, $audit->school_id);
        $this->assertSame(['from' => 'বাংলা প্রাথমিক বিদ্যালয়', 'to' => 'সংশোধিত বিদ্যালয়'], $audit->details['changes']['bangla_name']);
        $this->assertSame(['from' => null, 'to' => 'EM-002'], $audit->details['changes']['emis_code']);
        $this->assertNotNull($audit->created_at);

        $this->put(route('schools.update', $school), $this->identityData([
            'code' => 'AN-099',
            'enrolment_count' => '999',
            'enrolment_effective_on' => today()->toDateString(),
            'participation_starts_on' => today()->addYear()->toDateString(),
        ]))->assertSessionHasErrors(['code', 'enrolment_count', 'enrolment_effective_on', 'participation_starts_on']);
        $this->assertSame('AN-001', $school->fresh()->code);
        $this->assertSame($originalEnrolment, $school->enrolments()->firstOrFail()->pupil_count);
        $this->assertSame($originalStart, $school->participationPeriods()->firstOrFail()->starts_on->toDateString());
        $this->assertDatabaseCount('school_enrolments', 1);
        $this->assertDatabaseCount('school_participation_periods', 1);
    }

    public function test_changing_emis_source_requires_fresh_verification_and_duplicate_emis_is_blocked_on_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/schools', $this->schoolData([
            'emis_code' => '00123', 'emis_source' => 'Official roster', 'emis_verified' => '1',
        ]))->assertRedirect();
        $first = School::firstOrFail();
        $this->post('/admin/schools', $this->schoolData(['bangla_name' => 'দ্বিতীয় বিদ্যালয়']))->assertRedirect();
        $second = School::where('code', 'AN-002')->firstOrFail();

        $this->put(route('schools.update', $first), $this->identityData([
            'emis_code' => '00123', 'emis_source' => 'New official letter',
        ]))->assertSessionHasErrors('emis_verified');
        $this->assertSame('Official roster', $first->fresh()->emis_source);

        $this->put(route('schools.update', $first), $this->identityData([
            'emis_code' => '00123', 'emis_source' => 'New official letter', 'emis_verified' => '1',
        ]))->assertRedirect(route('schools.show', $first));
        $this->assertSame('00123', $first->fresh()->emis_code);
        $this->assertSame('New official letter', $first->fresh()->emis_source);

        $this->put(route('schools.update', $second), $this->identityData([
            'emis_code' => '00123', 'emis_source' => 'Other letter', 'emis_verified' => '1',
        ]))->assertSessionHasErrors('emis_code');
        $this->assertNull($second->fresh()->emis_code);

        $this->put(route('schools.update', $first), $this->identityData())
            ->assertRedirect(route('schools.show', $first));
        $this->assertNull($first->fresh()->emis_code);
        $this->assertNull($first->fresh()->emis_verified_at);
    }

    public function test_only_admin_can_open_or_change_schools_and_search_finds_bangla_names(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create();
        $this->actingAs($admin)->post('/admin/schools', $this->schoolData())->assertRedirect();
        $school = School::firstOrFail();

        $this->get('/admin/schools?search=বাংলা')->assertOk()->assertSee('বাংলা প্রাথমিক বিদ্যালয়');
        $this->get('/admin/schools?search=missing')->assertOk()->assertSee('No schools found.');

        $this->actingAs($staff)->get('/admin/schools')->assertForbidden();
        $this->get('/admin/schools/create')->assertForbidden();
        $this->get(route('schools.show', $school))->assertForbidden();
        $this->post('/admin/schools', $this->schoolData())->assertForbidden();
        $this->put(route('schools.update', $school), $this->identityData())->assertForbidden();
        $this->assertDatabaseCount('schools', 1);
    }

    private function schoolData(array $overrides = []): array
    {
        return [
            'bangla_name' => 'বাংলা প্রাথমিক বিদ্যালয়',
            'enrolment_count' => '250',
            'enrolment_effective_on' => today()->toDateString(),
            'participation_starts_on' => today()->toDateString(),
            ...$overrides,
        ];
    }

    private function identityData(array $overrides = []): array
    {
        return [
            'bangla_name' => 'বাংলা প্রাথমিক বিদ্যালয়',
            'union' => '',
            'cluster' => '',
            'teacher_name' => '',
            'teacher_phone' => '',
            'emis_code' => '',
            'emis_source' => '',
            ...$overrides,
        ];
    }
}
