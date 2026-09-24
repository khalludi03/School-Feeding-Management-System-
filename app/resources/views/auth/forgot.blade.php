@extends('layouts.app', ['aurora' => true])
@section('title', 'Account recovery')
@section('content')
<div class="mx-auto mt-8 max-w-md rounded-3xl border border-white/80 bg-white/90 p-6 shadow-2xl shadow-blue-900/10 backdrop-blur-xl sm:mt-14 sm:p-9">
    <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-blue-700">Account recovery</p>
    <h1 class="text-3xl font-bold tracking-tight">Need a new password?</h1>
    <p class="mt-4 leading-7 text-slate-600">Contact your upazila Admin. They can issue a new temporary password and send it to your registered WhatsApp number.</p>
    <a href="{{ route('login') }}" class="mt-7 inline-block rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Back to sign in</a>
</div>
@endsection
