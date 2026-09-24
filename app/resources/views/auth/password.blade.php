@extends('layouts.app', ['aurora' => true])
@section('title', $forced ? 'Set a new password' : 'Change password')
@section('content')
<div class="mx-auto mt-8 max-w-md rounded-3xl border border-white/80 bg-white/90 p-6 shadow-2xl shadow-blue-900/10 backdrop-blur-xl sm:mt-14 sm:p-9">
    <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-blue-700">Account security</p>
    <h1 class="text-3xl font-bold tracking-tight">{{ $forced ? 'Set a new password' : 'Change password' }}</h1>
    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $forced ? 'Your temporary password must be replaced before you can continue.' : 'Enter your current password and choose a new one.' }}</p>
    @if($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif
    <form class="mt-7 space-y-5" method="post" action="{{ $forced ? route('password.force.update') : route('password.profile.update') }}">
        @csrf
        @unless($forced) @method('PUT') @endunless
        @unless($forced)
            <div><label for="current_password" class="mb-2 block text-sm font-semibold">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"></div>
        @endunless
        <div><label for="password" class="mb-2 block text-sm font-semibold">New password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"><p class="mt-1 text-xs text-slate-500">Use at least 12 characters.</p></div>
        <div><label for="password_confirmation" class="mb-2 block text-sm font-semibold">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"></div>
        <button class="w-full rounded-xl bg-blue-700 px-4 py-3 font-semibold text-white hover:bg-blue-800">Save password</button>
    </form>
</div>
@endsection
