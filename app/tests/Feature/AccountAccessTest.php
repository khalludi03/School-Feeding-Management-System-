<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_sign_in_to_their_role_home_and_are_restricted_by_role(): void
    {
        $admin = User::factory()->create(['username' => 'admin', 'role' => 'admin', 'password' => 'VeryLongAdminPass123']);
        $staff = User::factory()->create(['username' => 'staff', 'password' => 'VeryLongStaffPass123']);

        $this->post('/login', ['username' => 'ADMIN', 'password' => 'VeryLongAdminPass123'])->assertRedirect('/');
        $this->get('/')->assertRedirect('/admin/dashboard');
        $this->get('/field/home')->assertForbidden();
        $this->post('/logout')
            ->assertRedirect('/login')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/login')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertViewHas('loggedOut', true)
            ->assertSee('data-clear-credentials="true"', false)
            ->assertSee('autocomplete="off"', false)
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('value=""', false)
            ->assertDontSee('VeryLongAdminPass123', false);
        $this->get('/login')
            ->assertOk()
            ->assertViewHas('loggedOut', true)
            ->assertSee('data-clear-credentials="true"', false);
        $this->get('/admin/dashboard')->assertRedirect('/login');

        $this->post('/login', ['username' => 'staff', 'password' => 'VeryLongStaffPass123'])->assertRedirect('/');
        $this->assertFalse(session('logged_out', false));
        $this->get('/')->assertRedirect('/field/home');
        $this->get('/admin/staff')->assertForbidden();
        $this->get('/field/home')->assertOk()->assertSee('Enter Delivery')->assertSee('My Entries')->assertSee('Daily Delivery Report');
    }

    public function test_temporary_password_blocks_all_other_work_until_changed(): void
    {
        $staff = User::factory()->create([
            'username' => 'tempstaff', 'password' => 'TemporaryPass1234',
            'must_change_password' => true, 'temporary_password_expires_at' => now()->addDay(),
        ]);

        $this->post('/login', ['username' => 'tempstaff', 'password' => 'TemporaryPass1234'])
            ->assertRedirect('/change-temporary-password');
        $this->get('/field/home')->assertRedirect('/change-temporary-password');
        $this->get('/change-temporary-password')->assertOk();
        $this->post('/change-temporary-password', [
            'password' => 'ReplacementPass1234', 'password_confirmation' => 'ReplacementPass1234',
        ])->assertRedirect('/');

        $this->assertFalse($staff->fresh()->must_change_password);
        $this->assertTrue(Hash::check('ReplacementPass1234', $staff->fresh()->password));
        $this->get('/field/home')->assertOk();
        $this->post('/logout');
        $this->post('/login', ['username' => 'tempstaff', 'password' => 'TemporaryPass1234'])
            ->assertSessionHasErrors('username');
    }

    public function test_inactive_and_unknown_accounts_return_the_same_error(): void
    {
        User::factory()->create(['username' => 'inactive', 'is_active' => false, 'password' => 'VeryLongPassword123']);
        $this->post('/login', ['username' => 'inactive', 'password' => 'VeryLongPassword123'])
            ->assertSessionHasErrors(['username' => 'Unable to sign in with these details.']);
        $this->post('/login', ['username' => 'unknown', 'password' => 'VeryLongPassword123'])
            ->assertSessionHasErrors(['username' => 'Unable to sign in with these details.']);
        $this->assertGuest();
    }

    public function test_admin_can_create_staff_with_a_one_time_password_and_demo_is_protected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $demo = User::factory()->create(['username' => 'demo', 'is_demo' => true]);

        $response = $this->actingAs($admin)->post('/admin/staff', [
            'name' => 'রহিমা', 'username' => 'new.staff', 'whatsapp_number' => '01712345678',
        ]);
        $response->assertOk()->assertSee('Temporary password ready');
        $handoff = $response->viewData('handoff');
        $staff = User::where('username', 'new.staff')->firstOrFail();
        $this->assertSame('রহিমা', $staff->name);
        $this->assertSame('8801712345678', $staff->whatsapp_number);
        $this->assertTrue(Hash::check($handoff['password'], $staff->password));
        $this->assertTrue($staff->must_change_password);
        $this->actingAs($admin)->post("/admin/staff/{$demo->id}/deactivate")->assertForbidden();
    }

    public function test_reset_and_deactivation_revoke_an_existing_session(): void
    {
        $staff = User::factory()->create(['username' => 'staff']);
        $this->actingAs($staff)->withSession(['auth_version' => $staff->auth_version]);
        $staff->forceFill(['auth_version' => $staff->auth_version + 1])->save();
        Auth::guard('web')->forgetUser();
        $this->get('/field/home')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_expired_temporary_password_cannot_sign_in_and_failed_attempts_are_throttled(): void
    {
        User::factory()->create([
            'username' => 'expired', 'password' => 'TemporaryPass1234',
            'must_change_password' => true, 'temporary_password_expires_at' => now()->subMinute(),
        ]);
        $this->post('/login', ['username' => 'expired', 'password' => 'TemporaryPass1234'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['username' => 'guessed', 'password' => 'wrong']);
        }
        $this->post('/login', ['username' => 'guessed', 'password' => 'wrong'])
            ->assertSessionHasErrors(['username' => 'Too many attempts. Try again in a few minutes.']);
    }
}
