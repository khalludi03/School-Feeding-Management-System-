<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $username = Str::lower(trim($data['username']));
        $key = sha1($username).'|'.$request->ip();
        $genericError = ['username' => 'Unable to sign in with these details.'];

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['username' => 'Too many attempts. Try again in a few minutes.'])->onlyInput('username');
        }

        $user = User::where('username', $username)->first();

        if (! $user || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 900);

            return back()->withErrors($genericError)->onlyInput('username');
        }

        if ($user->must_change_password && $user->temporary_password_expires_at?->isPast()) {
            RateLimiter::hit($key, 900);

            return back()->withErrors(['username' => 'Temporary password expired. Contact your Admin for a new one.'])->onlyInput('username');
        }

        RateLimiter::clear($key);
        Auth::login($user, (bool) ($data['remember'] ?? false) && ! $user->must_change_password);
        $request->session()->regenerate();
        $request->session()->put('auth_version', $user->auth_version);
        AuditEvent::record('signed_in', $user);

        return redirect()->route($user->must_change_password ? 'password.force.edit' : 'home');
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditEvent::record('signed_out', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
