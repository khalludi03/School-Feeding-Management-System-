<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || (
            $request->session()->has('auth_version')
            && $request->session()->get('auth_version') !== $user->auth_version
        )) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => 'Unable to sign in with these details.']);
        }

        if (! $request->session()->has('auth_version')) {
            $request->session()->put('auth_version', $user->auth_version);
        }

        if ($user->must_change_password && ! $request->routeIs('password.force.*') && ! $request->routeIs('logout')) {
            return redirect()->route('password.force.edit');
        }

        if (! $user->must_change_password && $request->routeIs('password.force.*')) {
            return redirect()->route('home');
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
