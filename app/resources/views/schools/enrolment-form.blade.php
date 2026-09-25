@extends('layouts.app')
@section('title', 'Schedule enrolment change')
@section('content')
@php
    $today = today();
@endphp
<a href="{{ route('schools.show', $school) }}" class="text-sm font-semibold text-blue-700 hover:underline">← School details</a>
<div class="mt-6 max-w-3xl">
    <h1 class="text-3xl font-bold tracking-tight">Schedule an enrolment change</h1>
    <p class="mt-2 text-slate-600">
        Records an absolute pupil count for <span class="font-semibold" lang="bn">{{ $school->bangla_name }}</span> that takes effect on a date you choose. Nothing is backdated and nothing changes until that date.
    </p>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
        @if($errors->any())
            <div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">Please correct the fields below. No change was recorded.</div>
        @endif
        <form method="post" action="{{ route('schools.enrolments.review', $school) }}" class="space-y-5">
            @csrf
            <div>
                <label for="effective_on" class="mb-2 block text-sm font-semibold">Effective date</label>
                <input id="effective_on" name="effective_on" type="date" required min="{{ $today->toDateString() }}" value="{{ old('effective_on') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                <p class="mt-1 text-xs text-slate-500">Today or later. The new count applies from this date onward until a later change replaces it.</p>
                @error('effective_on')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="pupil_count" class="mb-2 block text-sm font-semibold">Pupil count</label>
                <input id="pupil_count" name="pupil_count" type="number" required min="0" max="1000000" step="1" value="{{ old('pupil_count') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                <p class="mt-1 text-xs text-slate-500">The whole number of pupils on this date, between 0 and 1,000,000. Not a percentage and not a difference.</p>
                @error('pupil_count')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="reason" class="mb-2 block text-sm font-semibold">Reason</label>
                <textarea id="reason" name="reason" required minlength="3" maxlength="500" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">{{ old('reason') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Recorded in the audit history alongside the before and after counts.</p>
                @error('reason')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-wrap items-center gap-4 pt-2">
                <button class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Review demand impact</button>
                <a href="{{ route('schools.show', $school) }}" class="font-semibold text-slate-600 hover:underline">Cancel</a>
            </div>
        </form>
    </section>

    <section class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Current position</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Count in force today</dt><dd class="mt-1 text-2xl font-semibold">{{ $currentCount === null ? 'Unknown' : number_format($currentCount) }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">First recorded count</dt><dd class="mt-1 font-medium">{{ $firstEffectiveOn?->format('j M Y') ?? 'None' }}</dd></div>
            </dl>
            @if($currentCount === null)
                <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">No count has been recorded yet, so demand before today is unknown rather than zero.</p>
            @endif
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Already scheduled</h2>
            @forelse($scheduled as $change)
                <div class="mt-3 flex justify-between gap-3 border-b border-slate-100 pb-2 text-sm">
                    <span>{{ $change->effective_on->format('j M Y') }}</span>
                    <strong>{{ number_format($change->pupil_count) }}</strong>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-500">No future change is scheduled.</p>
            @endforelse
            <p class="mt-4 text-xs text-slate-500">A new change can be dated before or after these. The latest dated count on any given day is the one that applies.</p>
        </div>
    </section>
</div>
@endsection
