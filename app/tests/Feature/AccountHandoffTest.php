<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountHandoffTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_shows_fixed_role_status_and_an_exact_whatsapp_preview(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/staff/create')
            ->assertOk()->assertSee('Field Staff')->assertSee('Active status')->assertSee('Active');

        $response = $this->post('/admin/staff', [
            'name' => 'রহিমা', 'username' => 'new.staff', 'whatsapp_number' => '01712345678',
            'role' => 'admin', 'is_active' => '0',
        ]);
        $response->assertOk()->assertSee('Send via WhatsApp')->assertSee('Review before opening WhatsApp');

        $staff = User::where('username', 'new.staff')->firstOrFail();
        $handoff = $response->viewData('handoff');
        $this->assertSame('field_staff', $staff->role);
        $this->assertTrue($staff->is_active);
        $this->assertTrue($staff->must_change_password);
        $this->assertTrue(Hash::check($handoff['password'], $staff->password));
        $this->assertSame('8801712345678', $staff->whatsapp_number);
        $this->assertStringContainsString('Site: '.rtrim(config('app.url'), '/').'/login', $handoff['message']);
        $this->assertStringContainsString('Username: new.staff', $handoff['message']);
        $this->assertStringContainsString('Temporary password: '.$handoff['password'], $handoff['message']);
        $this->assertStringContainsString('Expires:', $handoff['message']);
        $this->assertSame('https://wa.me/8801712345678?text='.rawurlencode($handoff['message']), $handoff['whatsapp_url']);
        $response->assertSee($handoff['message']);
        $response->assertHeader('Cache-Control', 'no-store, private');
        $this->assertFalse(session()->has('handoff'));
        $this->assertSame(1, User::where('username', 'new.staff')->count());
        $this->get('/register')->assertNotFound();
    }

    public function test_missing_fields_and_duplicate_username_create_no_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['username' => 'taken']);

        $this->actingAs($admin)->post('/admin/staff', ['name' => '', 'username' => 'other'])
            ->assertSessionHasErrors(['name', 'whatsapp_number']);
        $this->post('/admin/staff', [
            'name' => 'Second', 'username' => 'TAKEN', 'whatsapp_number' => '01712345678',
        ])->assertSessionHasErrors('username');

        $this->assertSame(0, User::where('name', 'Second')->count());
        $this->assertSame(1, User::where('username', 'taken')->count());
    }

    public function test_creation_rolls_back_if_temporary_password_cannot_be_issued(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->mock(CredentialService::class)
            ->shouldReceive('issueTemporaryPassword')->once()->andThrow(new \RuntimeException('handoff failed'));

        try {
            $this->actingAs($admin)->post('/admin/staff', [
                'name' => 'Incomplete', 'username' => 'incomplete', 'whatsapp_number' => '01712345678',
            ]);
        } catch (\RuntimeException $exception) {
            $this->assertSame('handoff failed', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['username' => 'incomplete']);
    }

    public function test_later_handoff_requires_reset_and_never_retrieves_the_original_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $created = $this->actingAs($admin)->post('/admin/staff', [
            'name' => 'Field Worker', 'username' => 'worker', 'whatsapp_number' => '01712345678',
        ]);
        $oldPassword = $created->viewData('handoff')['password'];
        $staff = User::where('username', 'worker')->firstOrFail();

        $this->get("/admin/staff/{$staff->id}/edit")->assertOk()->assertDontSee($oldPassword);
        $this->get('/admin/staff')->assertOk()->assertDontSee($oldPassword);
        $reset = $this->post("/admin/staff/{$staff->id}/reset-password", [
            'verification_method' => 'in_person',
            'verification_note' => 'Checked in person at the office.',
            'identity_verified' => '1',
            'current_password' => 'password',
        ])->assertOk();
        $newPassword = $reset->viewData('handoff')['password'];
        $this->assertNotSame($oldPassword, $newPassword);
        $this->assertFalse(Hash::check($oldPassword, $staff->fresh()->password));
        $this->assertTrue(Hash::check($newPassword, $staff->fresh()->password));
        $reset->assertSee('Send via WhatsApp')->assertDontSee('Message delivered');
    }

    public function test_correcting_pending_username_or_number_rotates_password_and_prepares_new_handoff(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create([
            'username' => 'oldname', 'whatsapp_number' => '8801712345678',
            'password' => 'OldTemporaryPass123', 'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHours(48),
        ]);

        $phoneEdit = $this->actingAs($admin)->put("/admin/staff/{$staff->id}", [
            'name' => $staff->name, 'username' => 'oldname', 'whatsapp_number' => '01812345678',
        ])->assertOk();
        $phonePassword = $phoneEdit->viewData('handoff')['password'];
        $this->assertFalse(Hash::check('OldTemporaryPass123', $staff->fresh()->password));
        $this->assertStringStartsWith('https://wa.me/8801812345678?', $phoneEdit->viewData('handoff')['whatsapp_url']);

        $nameEdit = $this->put("/admin/staff/{$staff->id}", [
            'name' => $staff->name, 'username' => 'newname', 'whatsapp_number' => '01812345678',
        ])->assertOk();
        $this->assertFalse(Hash::check($phonePassword, $staff->fresh()->password));
        $this->assertStringContainsString('Username: newname', $nameEdit->viewData('handoff')['message']);
    }

    public function test_after_permanent_password_change_account_views_do_not_show_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create([
            'username' => 'permanent', 'whatsapp_number' => '8801712345678',
            'password' => 'OldTemporaryPass123', 'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHours(48),
        ]);

        $this->post('/login', ['username' => 'permanent', 'password' => 'OldTemporaryPass123'])
            ->assertRedirect('/change-temporary-password');
        $this->post('/change-temporary-password', [
            'password' => 'PermanentPass1234', 'password_confirmation' => 'PermanentPass1234',
        ])->assertRedirect('/');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertFalse($staff->fresh()->must_change_password);

        $this->actingAs($admin)->get("/admin/staff/{$staff->id}/edit")
            ->assertOk()->assertDontSee('PermanentPass1234');
        $this->get('/admin/staff')->assertOk()->assertDontSee('PermanentPass1234');
        $this->put("/admin/staff/{$staff->id}", [
            'name' => $staff->name, 'username' => 'permanent', 'whatsapp_number' => '01812345678',
        ])->assertRedirect('/admin/staff');
        $this->assertTrue(Hash::check('PermanentPass1234', $staff->fresh()->password));
    }
}
