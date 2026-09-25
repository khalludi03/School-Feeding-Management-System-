@extends('layouts.app')
@section('title', match ($action) { 'deactivate' => 'Confirm school deactivation', 'reactivate' => 'Confirm school reactivation' })
@section('content')
@php
    $title = match ($action) { 'deactivate' => 'Deactivate school', 'reactivate' => 'Reactivate school' };
    $route = match ($action) { 'deactivate' => 'schools.deactivate', 'reactivate' => 'schools.reactivate' };
@endphp
<a href="{{ route('schools.show', $school) }}" class="text-sm font-semibold text-blue-700 hover:underline">← School details</a>
<div class="mt-6 max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    <h1 class="text-3xl font-bold tracking-tight">{{ $title }}</h1>
    <p class="mt-2 text-slate-600">
        @if($action === 'deactivate')
            This removes the school from the active directory and blocks new participation. Existing history remains available to Admins.
        @else
            This returns the school to the active directory. New participation becomes allowed only after this confirmation.
        @endif
    </p>
    <dl class="mt-6 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">School</dt><dd class="mt-1 font-semibold" lang="bn">{{ $school->bangla_name }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Internal code</dt><dd class="mt-1 font-semibold">{{ $school->code }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current status</dt><dd class="mt-1 font-semibold">{{ $school->is_active ? 'Active' : 'Inactive' }}</dd></div>
    </dl>
    @if($errors->any())<div role="alert" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">Please correct the fields below. No school status change was made.</div>@endif
    <form method="post" action="{{ route($route, $school) }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="reason" class="mb-2 block text-sm font-semibold">Reason</label>
            <textarea id="reason" name="reason" required minlength="3" maxlength="500" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">{{ old('reason') }}</textarea>
            @error('reason')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="current_password" class="mb-2 block text-sm font-semibold">Your Admin password</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
            @error('current_password')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-wrap items-center gap-4 pt-2">
            <button class="rounded-xl px-5 py-3 font-semibold text-white {{ $action === 'deactivate' ? 'bg-rose-700 hover:bg-rose-800' : 'bg-blue-700 hover:bg-blue-800' }}">Confirm {{ strtolower($title) }}</button>
            <a href="{{ route('schools.show', $school) }}" class="font-semibold text-slate-600 hover:underline">Cancel</a>
        </div>
    </form>
</div>
@endsection
