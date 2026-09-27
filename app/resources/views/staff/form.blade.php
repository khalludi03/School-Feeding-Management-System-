@extends('layouts.app')
@section('title', $staff ? 'Edit Field Staff' : 'Create Field Staff')
@section('content')
<a href="{{ route('staff.index') }}" class="text-sm font-semibold text-primary hover:underline">← Staff accounts</a>
<div class="mt-6 max-w-2xl card-glass rounded-2xl p-6 shadow-sm sm:p-8">
    <h1 class="text-3xl font-bold tracking-tight text-foreground">{{ $staff ? 'Edit Field Staff' : 'Create Field Staff' }}</h1>
    <p class="mt-2 text-muted-foreground">{{ $staff ? 'Update identity and WhatsApp details.' : 'A temporary password will be generated after account creation.' }}</p>
    <dl class="mt-6 grid gap-4 rounded-xl border border-border bg-muted p-4 sm:grid-cols-2">
        <div><dt class="text-xs font-semibold text-muted-foreground">Role</dt><dd class="mt-1 font-semibold">Field Staff</dd></div>
        <div><dt class="text-xs font-semibold text-muted-foreground">Active status</dt><dd class="mt-1 font-semibold">{{ $staff ? ($staff->is_active ? 'Active' : 'Inactive') : 'Active' }}</dd></div>
    </dl>
    @if($errors->any())<div role="alert" class="mt-6 rounded-xl border border-error bg-destructive/10 p-4 text-sm text-destructive">Please correct the fields below.</div>@endif
    <form method="post" action="{{ $staff ? route('staff.update', $staff) : route('staff.store') }}" class="mt-8 space-y-5">
        @csrf
        @if($staff) @method('PUT') @endif
        <div><label for="name" class="mb-2 block text-sm font-semibold text-foreground">Full name</label><input id="name" name="name" required maxlength="255" value="{{ old('name', $staff?->name) }}" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">@error('name')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror</div>
        <div><label for="username" class="mb-2 block text-sm font-semibold text-foreground">Username</label><input id="username" name="username" required minlength="3" maxlength="32" value="{{ old('username', $staff?->username) }}" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20"><p class="mt-1 text-xs text-muted-foreground">3–32 lowercase letters, numbers, dots, underscores, or hyphens.</p>@error('username')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror</div>
        <div><label for="whatsapp_number" class="mb-2 block text-sm font-semibold text-foreground">WhatsApp number</label><input id="whatsapp_number" name="whatsapp_number" type="tel" required placeholder="01XXXXXXXXX" value="{{ old('whatsapp_number', $staff?->whatsapp_number) }}" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">@error('whatsapp_number')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror</div>
        @if($staff?->must_change_password && $staff?->is_active)
            <p class="rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">Changing the username or WhatsApp number will invalidate the current temporary password and show a new handoff.</p>
        @endif
        <div class="flex flex-wrap items-center gap-4 pt-2"><x-ui.button size="lg">{{ $staff ? 'Save changes' : 'Create and show handoff' }}</x-ui.button><a href="{{ route('staff.index') }}" class="font-semibold text-muted-foreground hover:underline">Cancel</a></div>
    </form>
</div>
@endsection
