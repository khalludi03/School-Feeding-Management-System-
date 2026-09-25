@extends('layouts.app', ['aurora' => true])
@section('title', 'Sign in')
@section('content')
@php
    $loggedOut = $loggedOut ?? false;
    $loginAutocomplete = $loggedOut ? 'off' : 'on';
    $usernameAutocomplete = $loggedOut ? 'off' : 'username';
    $passwordAutocomplete = $loggedOut ? 'new-password' : 'current-password';
    $usernameAutofocus = $loggedOut ? '' : 'autofocus';
@endphp
<div class="mx-auto mt-8 max-w-md rounded-3xl border border-white/80 bg-white/90 p-6 shadow-2xl shadow-blue-900/10 backdrop-blur-xl sm:mt-14 sm:p-9">
    <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-blue-700">Welcome back</p>
    <h1 class="text-3xl font-bold tracking-tight">Sign in</h1>
    <p class="mt-2 text-sm leading-6 text-slate-600">Use your SFP username and password to continue.</p>
    @if($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif
    <form class="mt-7 space-y-5" method="post" action="{{ route('login.submit') }}" autocomplete="{{ $loginAutocomplete }}" data-clear-credentials="{{ $loggedOut ? 'true' : 'false' }}">
        @csrf
        <div>
            <label for="username" class="mb-2 block text-sm font-semibold">Username</label>
            <input id="username" name="username" type="text" autocomplete="{{ $usernameAutocomplete }}" required {{ $usernameAutofocus }} value="{{ $loggedOut ? '' : old('username') }}" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
        </div>
        <div>
            <label for="password" class="mb-2 block text-sm font-semibold">Password</label>
            <input id="password" name="password" type="password" autocomplete="{{ $passwordAutocomplete }}" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
        </div>
        @if($loggedOut)
            <script>
                document.getElementById('username').value = '';
                document.getElementById('password').value = '';
            </script>
        @endif
        <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-700"> Remember me for 14 days</label>
        <button class="w-full rounded-xl bg-blue-700 px-4 py-3 font-semibold text-white shadow-lg shadow-blue-700/20 transition hover:bg-blue-800">Sign in</button>
    </form>
    <a href="{{ route('password.forgot') }}" class="mt-6 inline-block text-sm font-medium text-blue-700 hover:underline">Forgot your password?</a>
</div>
@if($loggedOut)
    @vite('resources/js/login.ts')
@endif
@endsection
