<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_initial_and_protected_demo_accounts_without_overwriting_passwords(): void
    {
        config()->set('sfp.initial_admin', ['name' => 'Upazila Admin', 'username' => 'firstadmin', 'password' => 'FirstAdminPass123']);
        config()->set('sfp.demo', [
            'enabled' => true,
            'admin_username' => 'reviewadmin', 'admin_password' => 'ReviewAdminPass123',
            'staff_username' => 'reviewstaff', 'staff_password' => 'ReviewStaffPass123',
            'staff_whatsapp' => '8801700000000',
        ]);

        $this->seed(DatabaseSeeder::class);
        $initial = User::where('username', 'firstadmin')->firstOrFail();
        $this->assertTrue($initial->must_change_password);
        $this->assertTrue(Hash::check('FirstAdminPass123', $initial->password));
        $this->assertTrue(User::where('username', 'reviewadmin')->firstOrFail()->is_demo);
        $this->assertTrue(User::where('username', 'reviewstaff')->firstOrFail()->is_demo);

        $initial->update(['password' => 'ChangedAdminPass123']);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(3, User::count());
        $this->assertTrue(Hash::check('ChangedAdminPass123', $initial->fresh()->password));
    }

    public function test_admin_recovery_command_issues_a_new_temporary_password(): void
    {
        $admin = User::factory()->create(['username' => 'firstadmin', 'role' => 'admin', 'is_active' => false]);
        Artisan::call('sfp:reset-admin', ['username' => 'firstadmin']);

        $admin->refresh();
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->must_change_password);
        $this->assertGreaterThan(1, $admin->auth_version);
        $this->assertNotNull($admin->temporary_password_expires_at);
    }
}
