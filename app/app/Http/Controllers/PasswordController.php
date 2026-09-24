<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function forceEdit(): View
    {
        return view('auth.password', ['forced' => true]);
    }

    public function profileEdit(): View
    {
        abort_if(request()->user()->is_demo, 403);

        return view('auth.password', ['forced' => false]);
    }

    public function forceUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($user->temporary_password_expires_at?->isPast()) {
            throw ValidationException::withMessages(['password' => 'Temporary password expired. Contact your Admin for a new one.']);
        }

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Choose a password different from your temporary password.']);
        }

        $this->savePassword($request, $data['password'], 'temporary_password_changed');

        return redirect()->route('home')->with('status', 'Password changed.');
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->is_demo, 403);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
        }

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Choose a different password.']);
        }

        $this->savePassword($request, $data['password'], 'password_changed');

        return redirect()->route('password.profile.edit')->with('status', 'Password changed.');
    }

    private function savePassword(Request $request, string $password, string $action): void
    {
        $user = $request->user();
        DB::transaction(function () use ($request, $user, $password, $action): void {
            $user->forceFill([
                'password' => $password,
                'must_change_password' => false,
                'temporary_password_expires_at' => null,
                'auth_version' => $user->auth_version + 1,
                'remember_token' => Str::random(60),
            ])->save();
            DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
            AuditEvent::record($action, $user);
        });

        $request->session()->put('auth_version', $user->auth_version);
    }
}
