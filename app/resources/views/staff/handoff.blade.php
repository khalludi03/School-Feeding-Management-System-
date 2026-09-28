@extends('layouts.app')
@section('title', 'Temporary password handoff')
@section('content')
<div class="max-w-2xl rounded-2xl border border-warning bg-card p-6 shadow-sm sm:p-8">
    <p class="text-sm font-semibold uppercase tracking-widest text-warning">One-time handoff</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Temporary password ready</h1>
    <p class="mt-3 text-muted-foreground">Send these details to {{ $handoff['name'] }} at {{ $handoff['whatsapp_number'] }}. They must change the password at first sign-in.</p>
    <p class="mt-2 text-sm text-muted-foreground">WhatsApp will open a draft. Check it there and choose Send manually.</p>
    <div class="mt-6 rounded-xl border border-border bg-muted p-5">
        <p class="text-sm text-muted-foreground">Username</p><p class="mt-1 font-mono font-semibold">{{ $handoff['username'] }}</p>
        <p class="mt-5 text-sm text-muted-foreground">Temporary password</p><p class="mt-1 break-all font-mono text-lg font-bold" id="temporary-password">{{ $handoff['password'] }}</p>
        <p class="mt-5 text-sm text-muted-foreground">Expires</p><p class="mt-1 font-semibold">{{ $handoff['expires_at'] }} (Bangladesh time)</p>
    </div>
    <div class="mt-6">
        <h2 class="text-lg font-semibold">Review before opening WhatsApp</h2>
        <p class="mt-2 text-sm text-muted-foreground">Recipient: <span class="font-semibold text-foreground">+{{ $handoff['whatsapp_number'] }}</span></p>
        <pre class="mt-3 whitespace-pre-wrap break-words rounded-xl border border-border bg-muted p-5 font-sans text-sm leading-6 text-foreground">{{ $handoff['message'] }}</pre>
    </div>
    <p class="mt-5 text-sm text-warning">This password is shown only on this page. The WhatsApp link contains it in the URL, which may appear in browser history.</p>
    <div class="mt-7 flex flex-wrap gap-3">
        <x-ui.button href="{{ $handoff['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" referrerpolicy="no-referrer" variant="success" size="lg">Send via WhatsApp</x-ui.button>
        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('temporary-password').textContent)" class="rounded-xl border border-border px-5 py-3 font-semibold hover:bg-muted">Copy password</button>
        <a href="{{ route('staff.index') }}" class="rounded-xl border border-border px-5 py-3 font-semibold hover:bg-muted">Done</a>
    </div>
</div>
@endsection
