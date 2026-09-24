@extends('layouts.app')
@section('title', $staff ? 'Edit Field Staff' : 'Create Field Staff')
@section('content')
<a href="{{ route('staff.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Staff accounts</a>
<div class="mt-6 max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    <h1 class="text-3xl font-bold tracking-tight">{{ $staff ? 'Edit Field Staff' : 'Create Field Staff' }}</h1>
    <p class="mt-2 text-slate-600">{{ $staff ? 'Update identity and WhatsApp details.' : 'A temporary password will be generated after account creation.' }}</p>
    <dl class="mt-6 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role</dt><dd class="mt-1 font-semibold">Field Staff</dd></div>
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active status</dt><dd class="mt-1 font-semibold">{{ $staff ? ($staff->is_active ? 'Active' : 'Inactive') : 'Active' }}</dd></div>
    </dl>
    @if($errors->any())<div role="alert" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">Please correct the fields below.</div>@endif
    <form method="post" action="{{ $staff ? route('staff.update', $staff) : route('staff.store') }}" class="mt-8 space-y-5">
        @csrf
        @if($staff) @method('PUT') @endif
        <div><label for="name" class="mb-2 block text-sm font-semibold">Full name</label><input id="name" name="name" required maxlength="255" value="{{ old('name', $staff?->name) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">@error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
        <div><label for="username" class="mb-2 block text-sm font-semibold">Username</label><input id="username" name="username" required minlength="3" maxlength="32" value="{{ old('username', $staff?->username) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100"><p class="mt-1 text-xs text-slate-500">3–32 lowercase letters, numbers, dots, underscores, or hyphens.</p>@error('username')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
        <div><label for="whatsapp_number" class="mb-2 block text-sm font-semibold">WhatsApp number</label><input id="whatsapp_number" name="whatsapp_number" type="tel" required placeholder="01XXXXXXXXX" value="{{ old('whatsapp_number', $staff?->whatsapp_number) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">@error('whatsapp_number')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
        @if($staff?->must_change_password && $staff?->is_active)
            <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Changing the username or WhatsApp number will invalidate the current temporary password and show a new handoff.</p>
        @endif
        <div class="flex flex-wrap items-center gap-4 pt-2"><button class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">{{ $staff ? 'Save changes' : 'Create and show handoff' }}</button><a href="{{ route('staff.index') }}" class="font-semibold text-slate-600 hover:underline">Cancel</a></div>
    </form>
</div>
@endsection
