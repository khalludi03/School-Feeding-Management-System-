@extends('layouts.app')
@section('title', match ($action) { 'reset' => 'Confirm password reset', 'deactivate' => 'Confirm deactivation', 'reactivate' => 'Confirm reactivation' })
@section('content')
@php
    $isCredentialAction = $action !== 'deactivate';
    $title = match ($action) { 'reset' => 'Reset password', 'deactivate' => 'Deactivate account', 'reactivate' => 'Reactivate account' };
    $route = match ($action) { 'reset' => 'staff.reset', 'deactivate' => 'staff.deactivate', 'reactivate' => 'staff.reactivate' };
@endphp
<a href="{{ route('staff.index') }}" class="text-sm font-semibold text-primary hover:underline">← Staff accounts</a>
<div class="mt-6 max-w-2xl card-glass rounded-2xl p-6 shadow-sm sm:p-8">
    <h1 class="text-3xl font-bold tracking-tight text-foreground">{{ $title }}</h1>
    <p class="mt-2 text-muted-foreground">
        @if($action === 'deactivate') This immediately blocks sign-in and protected actions. Existing records and history remain intact.
        @else A new temporary password will replace the old credential and require a password change at next sign-in. Review the WhatsApp handoff after confirming.
        @endif
    </p>
    <dl class="mt-6 grid gap-4 rounded-xl border border-border bg-muted p-4 sm:grid-cols-2">
        <div><dt class="text-xs font-semibold text-muted-foreground">Field Staff</dt><dd class="mt-1 font-semibold">{{ $staff->name }}</dd></div>
        <div><dt class="text-xs font-semibold text-muted-foreground">Username</dt><dd class="mt-1 font-semibold">{{ $staff->username }}</dd></div>
        <div><dt class="text-xs font-semibold text-muted-foreground">WhatsApp</dt><dd class="mt-1 font-semibold">{{ $staff->whatsapp_number }}</dd></div>
        <div><dt class="text-xs font-semibold text-muted-foreground">Current status</dt><dd class="mt-1 font-semibold">{{ $staff->is_active ? 'Active' : 'Inactive' }}</dd></div>
    </dl>
    @if($errors->any())<div role="alert" class="mt-6 rounded-xl border border-error bg-destructive/10 p-4 text-sm text-destructive">Please correct the fields below. No account change was made.</div>@endif
    <form method="post" action="{{ route($route, $staff) }}" class="mt-8 space-y-5">
        @csrf
        @if($isCredentialAction)
            <div>
                <label for="verification_method" class="mb-2 block text-sm font-semibold text-foreground">How did you verify this person's identity?</label>
                <select id="verification_method" name="verification_method" required class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    <option value="">Select a method</option>
                    <option value="in_person" @selected(old('verification_method') === 'in_person')>In person</option>
                    <option value="registered_number_call" @selected(old('verification_method') === 'registered_number_call')>Call to registered number</option>
                </select>
                @error('verification_method')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="verification_note" class="mb-2 block text-sm font-semibold text-foreground">Verification note</label>
                <textarea id="verification_note" name="verification_note" required minlength="3" maxlength="500" rows="3" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">{{ old('verification_note') }}</textarea>
                <p class="mt-1 text-xs text-muted-foreground">Briefly describe the check. Do not enter identity documents, passwords, or sensitive personal details.</p>
                @error('verification_note')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="flex items-start gap-3 text-sm font-semibold text-foreground"><input type="checkbox" name="identity_verified" value="1" required @checked(old('identity_verified')) class="mt-1 rounded border-border text-primary">I confirm that I verified this person's identity using the method above.</label>
                @error('identity_verified')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
            </div>
        @endif
        <div>
            <label for="current_password" class="mb-2 block text-sm font-semibold text-foreground">Your Admin password</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
            @error('current_password')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-wrap items-center gap-4 pt-2">
            <x-ui.button type="submit" variant="{{ $action === 'deactivate' ? 'destructive' : 'default' }}" size="lg">Confirm {{ strtolower($title) }}</x-ui.button>
            <a href="{{ route('staff.index') }}" class="font-semibold text-muted-foreground hover:underline">Cancel</a>
        </div>
    </form>
</div>
@endsection
