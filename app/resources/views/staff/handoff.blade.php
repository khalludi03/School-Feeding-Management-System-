@extends('layouts.app')
@section('title', 'Temporary password handoff')
@section('content')
<div class="max-w-2xl rounded-2xl border border-warning bg-white p-6 shadow-sm sm:p-8">
    <p class="text-sm font-semibold uppercase tracking-widest text-warning">One-time handoff</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Temporary password ready</h1>
    <p class="mt-3 text-secondary-content">Send these details to {{ $handoff['name'] }} at {{ $handoff['whatsapp_number'] }}. They must change the password at first sign-in.</p>
    <p class="mt-2 text-sm text-secondary-content">WhatsApp will open a draft. Check it there and choose Send manually.</p>
    <div class="mt-6 rounded-xl border border-base-300 bg-base-200 p-5">
        <p class="text-sm text-secondary-content">Username</p><p class="mt-1 font-mono font-semibold">{{ $handoff['username'] }}</p>
        <p class="mt-5 text-sm text-secondary-content">Temporary password</p><p class="mt-1 break-all font-mono text-lg font-bold" id="temporary-password">{{ $handoff['password'] }}</p>
        <p class="mt-5 text-sm text-secondary-content">Expires</p><p class="mt-1 font-semibold">{{ $handoff['expires_at'] }} (Bangladesh time)</p>
    </div>
    <div class="mt-6">
        <h2 class="text-lg font-semibold">Review before opening WhatsApp</h2>
        <p class="mt-2 text-sm text-secondary-content">Recipient: <span class="font-semibold text-base-content">+{{ $handoff['whatsapp_number'] }}</span></p>
        <pre class="mt-3 whitespace-pre-wrap break-words rounded-xl border border-base-300 bg-base-200 p-5 font-sans text-sm leading-6 text-base-content">{{ $handoff['message'] }}</pre>
    </div>
    <p class="mt-5 text-sm text-warning">This password is shown only on this page. The WhatsApp link contains it in the URL, which may appear in browser history.</p>
    <div class="mt-7 flex flex-wrap gap-3">
        <a href="{{ $handoff['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" referrerpolicy="no-referrer" class="rounded-xl bg-success px-5 py-3 font-semibold text-primary-content hover:bg-success/90">Send via WhatsApp</a>
        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('temporary-password').textContent)" class="rounded-xl border border-base-300 px-5 py-3 font-semibold hover:bg-base-200">Copy password</button>
        <a href="{{ route('staff.index') }}" class="rounded-xl border border-base-300 px-5 py-3 font-semibold hover:bg-base-200">Done</a>
    </div>
</div>
@endsection
