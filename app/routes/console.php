<?php

use App\Models\User;
use App\Services\CredentialService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('sfp:reset-admin {username}', function (string $username, CredentialService $credentials): void {
    $user = User::where('username', strtolower($username))->where('role', 'admin')->firstOrFail();
    if ($user->is_demo) {
        $this->error('Protected demo credentials cannot be reset.');

        return;
    }

    $user->update(['is_active' => true]);
    $password = $credentials->issueTemporaryPassword($user, 'admin_recovery_password_issued');
    $this->warn('Temporary password (shown once, expires in 48 hours): '.$password);
})->purpose('Issue a temporary password to a non-demo Admin');
