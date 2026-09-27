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
<div class="mx-auto mt-8 max-w-md card-glass rounded-3xl p-6 shadow-xl shadow-primary/10 sm:mt-14 sm:p-9">
    <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-primary">Welcome back</p>
    <h1 class="text-3xl font-bold tracking-tight text-foreground">Sign in</h1>
    <p class="mt-2 text-sm leading-6 text-muted-foreground">Use your SFP username and password to continue.</p>
    @if($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-error bg-destructive/10 p-4 text-sm text-destructive">{{ $errors->first() }}</div>
    @endif
    <form class="mt-7 space-y-5" method="post" action="{{ route('login.submit') }}" autocomplete="{{ $loginAutocomplete }}" data-clear-credentials="{{ $loggedOut ? 'true' : 'false' }}">
        @csrf
        <div>
            <label for="username" class="mb-2 block text-sm font-semibold text-foreground">Username</label>
            <input id="username" name="username" type="text" autocomplete="{{ $usernameAutocomplete }}" required {{ $usernameAutofocus }} value="{{ $loggedOut ? '' : old('username') }}" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
        </div>
        <div>
            <label for="password" class="mb-2 block text-sm font-semibold text-foreground">Password</label>
            <input id="password" name="password" type="password" autocomplete="{{ $passwordAutocomplete }}" required class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
        </div>
        @if($loggedOut)
            <script>
                document.getElementById('username').value = '';
                document.getElementById('password').value = '';
            </script>
        @endif
         <label class="flex items-center gap-2 text-sm text-muted-foreground"><input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-border text-primary"> Remember me for 14 days</label>
        <x-ui.button type="submit" class="w-full shadow-lg shadow-primary/20">Sign in</x-ui.button>
    </form>
    <a href="{{ route('password.forgot') }}" class="mt-6 inline-block text-sm font-medium text-primary hover:underline">Forgot your password?</a>
</div>
@if($loggedOut)
    @vite('resources/js/login.ts')
@endif
@endsection
