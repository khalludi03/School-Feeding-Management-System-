<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAccessRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_pages_show_target_and_only_the_valid_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['name' => '<script>alert(1)</script>', 'username' => 'worker']);
        $this->actingAs($admin)->get("/admin/staff/{$staff->id}/reset-password/confirm")
            ->assertOk()->assertSee('Reset password')->assertSee('worker')
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->get("/admin/staff/{$staff->id}/deactivate/confirm")
            ->assertOk()->assertSee('Your Admin password');
        $this->get("/admin/staff/{$staff->id}/reactivate/confirm")->assertStatus(409);

        $staff->update(['is_active' => false]);
        $this->get("/admin/staff/{$staff->id}/reactivate/confirm")
            ->assertOk()->assertSee('How did you verify');
        $this->get("/admin/staff/{$staff->id}/reset-password/confirm")->assertStatus(409);
    }

    public function test_reset_requires_identity_check_note_and_admin_password_without_changing_the_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['password' => 'OldStaffPass123']);

        $this->actingAs($admin)->post("/admin/staff/{$staff->id}/reset-password", [])
            ->assertSessionHasErrors(['verification_method', 'verification_note', 'identity_verified', 'current_password']);
        $this->post("/admin/staff/{$staff->id}/reset-password", [
            'verification_method' => 'unknown',
            'verification_note' => 'x',
            'current_password' => 'wrong-password',
        ])->assertSessionHasErrors(['verification_method', 'verification_note', 'identity_verified', 'current_password']);

        $this->assertTrue(Hash::check('OldStaffPass123', $staff->fresh()->password));
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertNull(session()->getOldInput('current_password'));
    }

    public function test_reset_revokes_old_password_and_records_identity_check_and_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['username' => 'worker', 'password' => 'OldStaffPass123']);
        DB::table('sessions')->insert([
            'id' => 'staff-session', 'user_id' => $staff->id, 'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($admin)->post("/admin/staff/{$staff->id}/reset-password", [
            'verification_method' => 'registered_number_call',
            'verification_note' => 'Called the number already on the account.',
            'identity_verified' => '1',
            'current_password' => 'password',
        ])->assertOk()->assertSee('Send via WhatsApp');

        $newPassword = $response->viewData('handoff')['password'];
        $staff->refresh();
        $this->assertFalse(Hash::check('OldStaffPass123', $staff->password));
        $this->assertTrue(Hash::check($newPassword, $staff->password));
        $this->assertTrue($staff->must_change_password);
        $this->assertDatabaseMissing('sessions', ['id' => 'staff-session']);
        $event = AuditEvent::where('action', 'staff_password_reset')->firstOrFail();
        $this->assertSame($admin->id, $event->actor_id);
        $this->assertSame($staff->id, $event->target_user_id);
        $this->assertSame('registered_number_call', $event->details['verification_method']);
        $this->assertSame('Called the number already on the account.', $event->details['verification_note']);
        $this->assertNotNull($event->created_at);
        $this->assertStringNotContainsString($newPassword, json_encode($event->details));

        $this->post('/logout');
        $this->post('/login', ['username' => 'worker', 'password' => 'OldStaffPass123'])
            ->assertSessionHasErrors('username');
        $this->post('/login', ['username' => 'worker', 'password' => $newPassword])
            ->assertRedirect('/change-temporary-password');
    }

    public function test_deactivation_requires_admin_password_and_keeps_the_account_and_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['username' => 'departing', 'password' => 'OldStaffPass123']);
        $originalVersion = $staff->fresh()->auth_version;
        DB::table('sessions')->insert([
            'id' => 'departing-session', 'user_id' => $staff->id, 'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)->post("/admin/staff/{$staff->id}/deactivate", [
            'current_password' => 'wrong',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue($staff->fresh()->is_active);

        $this->post("/admin/staff/{$staff->id}/deactivate", [
            'current_password' => 'password',
        ])->assertRedirect('/admin/staff');
        $staff->refresh();
        $this->assertFalse($staff->is_active);
        $this->assertSame($originalVersion + 1, $staff->auth_version);
        $this->assertDatabaseMissing('sessions', ['id' => 'departing-session']);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'username' => 'departing']);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $admin->id, 'target_user_id' => $staff->id, 'action' => 'staff_deactivated',
        ]);
        $this->post('/logout');
        $this->post('/login', ['username' => 'departing', 'password' => 'OldStaffPass123'])
            ->assertSessionHasErrors('username');
        $this->actingAs($staff)->withSession(['auth_version' => $originalVersion])
            ->get('/field/home')->assertRedirect('/login');
    }

    public function test_reactivation_requires_identity_check_and_issues_fresh_temporary_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create([
            'username' => 'returning', 'password' => 'OldStaffPass123', 'is_active' => false,
        ]);

        $this->actingAs($admin)->post("/admin/staff/{$staff->id}/reactivate", [
            'verification_method' => 'in_person',
            'verification_note' => 'Confirmed identity at the office.',
            'identity_verified' => '1',
            'current_password' => 'wrong',
        ])->assertSessionHasErrors('current_password');
        $this->assertFalse($staff->fresh()->is_active);

        $response = $this->post("/admin/staff/{$staff->id}/reactivate", [
            'verification_method' => 'in_person',
            'verification_note' => 'Confirmed identity at the office.',
            'identity_verified' => '1',
            'current_password' => 'password',
        ])->assertOk()->assertSee('Send via WhatsApp');
        $newPassword = $response->viewData('handoff')['password'];
        $staff->refresh();
        $this->assertTrue($staff->is_active);
        $this->assertTrue($staff->must_change_password);
        $this->assertFalse(Hash::check('OldStaffPass123', $staff->password));
        $this->assertTrue(Hash::check($newPassword, $staff->password));
        $event = AuditEvent::where('action', 'staff_reactivated')->firstOrFail();
        $this->assertSame($admin->id, $event->actor_id);
        $this->assertSame('in_person', $event->details['verification_method']);
        $this->assertSame('Confirmed identity at the office.', $event->details['verification_note']);
    }

    public function test_stale_actions_admin_targets_and_demo_accounts_are_blocked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['is_active' => false]);
        $demo = User::factory()->create(['is_demo' => true]);
        $confirmation = [
            'verification_method' => 'in_person',
            'verification_note' => 'Confirmed identity at the office.',
            'identity_verified' => '1',
            'current_password' => 'password',
        ];

        $this->actingAs($admin)->post("/admin/staff/{$staff->id}/reset-password", $confirmation)
            ->assertStatus(409);
        $this->post("/admin/staff/{$staff->id}/deactivate", $confirmation)->assertStatus(409);
        $this->post("/admin/staff/{$demo->id}/reset-password", $confirmation)->assertForbidden();
        $this->get("/admin/staff/{$demo->id}/deactivate/confirm")->assertForbidden();
        $this->post("/admin/staff/{$admin->id}/deactivate", $confirmation)->assertNotFound();
        $this->assertTrue($admin->fresh()->is_active);

        $this->post("/admin/staff/{$staff->id}/reactivate", $confirmation)->assertOk();
        $this->post("/admin/staff/{$staff->id}/reactivate", $confirmation)->assertStatus(409);
    }

    public function test_field_staff_cannot_open_or_submit_account_actions(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff)->get("/admin/staff/{$staff->id}/reset-password/confirm")
            ->assertForbidden();
        $this->post("/admin/staff/{$staff->id}/deactivate", ['current_password' => 'password'])
            ->assertForbidden();
    }
}
