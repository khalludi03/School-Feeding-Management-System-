<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_field_staff_is_denied_direct_admin_pages_but_keeps_personal_and_summary_access(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);

        foreach (['/admin/staff', '/admin/schools', '/admin/settings', '/admin/report-generator'] as $path) {
            $this->get($path)->assertForbidden();
        }

        $this->get('/password')->assertOk()->assertSee('Change password');
        $this->get('/field/daily-report')->assertOk()->assertSee('Daily Delivery Report');
    }

    public function test_admin_can_open_reserved_pages_without_seeing_unfinished_dashboard_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get('/admin/staff')->assertOk();
        $this->get('/admin/schools')->assertOk()->assertSee('School management')->assertSee('not been built yet');
        $this->get('/admin/settings')->assertOk()->assertSee('Programme settings')->assertSee('not been built yet');
        $this->get('/admin/report-generator')->assertOk()->assertSee('Official Report Generator')->assertSee('not been built yet');
        $this->get('/field/daily-report')->assertForbidden();

        $dashboard = $this->get('/admin/dashboard')->assertOk();
        foreach (['admin.schools', 'admin.settings', 'admin.report-generator'] as $route) {
            $dashboard->assertDontSee('href="'.route($route).'"', false);
        }
    }

    public function test_reserved_pages_still_require_an_active_account_with_a_permanent_password(): void
    {
        $this->get('/admin/schools')->assertRedirect('/login');

        $temporaryAdmin = User::factory()->create([
            'role' => 'admin',
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addDay(),
        ]);
        $this->actingAs($temporaryAdmin)->get('/admin/schools')
            ->assertRedirect('/change-temporary-password');

        $inactiveAdmin = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $this->actingAs($inactiveAdmin)->get('/admin/schools')->assertRedirect('/login');
    }
}
