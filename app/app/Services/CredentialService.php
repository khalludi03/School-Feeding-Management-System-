<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CredentialService
{
    public function issueTemporaryPassword(User $user, string $action): string
    {
        $password = Str::random(20);

        DB::transaction(function () use ($user, $password, $action): void {
            $user->forceFill([
                'password' => $password,
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addHours(48),
                'auth_version' => $user->auth_version + 1,
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('sessions')->where('user_id', $user->id)->delete();
            AuditEvent::record($action, $user);
        });

        return $password;
    }
}
