@extends('layouts.app')
@section('title', 'Temporary password handoff')
@section('content')
<div class="max-w-2xl rounded-2xl border border-amber-200 bg-white p-6 shadow-sm sm:p-8">
    <p class="text-sm font-semibold uppercase tracking-widest text-amber-700">One-time handoff</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">Temporary password ready</h1>
    <p class="mt-3 text-slate-600">Send these details to {{ $handoff['name'] }} at {{ $handoff['whatsapp_number'] }}. They must change the password at first sign-in.</p>
    <p class="mt-2 text-sm text-slate-600">WhatsApp will open a draft. Check it there and choose Send manually.</p>
    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
        <p class="text-sm text-slate-500">Username</p><p class="mt-1 font-mono font-semibold">{{ $handoff['username'] }}</p>
        <p class="mt-5 text-sm text-slate-500">Temporary password</p><p class="mt-1 break-all font-mono text-lg font-bold" id="temporary-password">{{ $handoff['password'] }}</p>
        <p class="mt-5 text-sm text-slate-500">Expires</p><p class="mt-1 font-semibold">{{ $handoff['expires_at'] }} (Bangladesh time)</p>
    </div>
    <div class="mt-6">
        <h2 class="text-lg font-semibold">Review before opening WhatsApp</h2>
        <p class="mt-2 text-sm text-slate-600">Recipient: <span class="font-semibold text-slate-900">+{{ $handoff['whatsapp_number'] }}</span></p>
        <pre class="mt-3 whitespace-pre-wrap break-words rounded-xl border border-slate-200 bg-slate-50 p-5 font-sans text-sm leading-6 text-slate-800">{{ $handoff['message'] }}</pre>
    </div>
    <p class="mt-5 text-sm text-amber-800">This password is shown only on this page. The WhatsApp link contains it in the URL, which may appear in browser history.</p>
    <div class="mt-7 flex flex-wrap gap-3">
        <a href="{{ $handoff['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" referrerpolicy="no-referrer" class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white hover:bg-emerald-800">Send via WhatsApp</a>
        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('temporary-password').textContent)" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-100">Copy password</button>
        <a href="{{ route('staff.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-100">Done</a>
    </div>
</div>
@endsection
