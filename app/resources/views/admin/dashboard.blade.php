@extends('layouts.app')
@section('title', 'Admin dashboard')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Admin workspace</p><h1 class="mt-2 text-3xl font-bold tracking-tight">Dashboard</h1><p class="mt-2 text-slate-600">Manage field staff access for this upazila.</p></div>
    <a href="{{ route('staff.create') }}" class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Create Field Staff</a>
</div>
<div class="mt-8 grid gap-5 md:grid-cols-2">
    <a href="{{ route('staff.index') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-blue-300 hover:shadow-md"><h2 class="text-xl font-semibold">Staff accounts</h2><p class="mt-2 text-slate-600">Search, update, deactivate, reactivate, and reset staff access.</p><span class="mt-5 inline-block font-semibold text-blue-700">Open accounts →</span></a>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="text-xl font-semibold">Delivery and reporting</h2><p class="mt-2 text-slate-600">School setup, deliveries, and reports are outside this account-feature build.</p></div>
</div>
@endsection
