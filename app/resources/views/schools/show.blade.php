@extends('layouts.app')
@section('title', $school->bangla_name)
@section('content')
@php
    $today = today();
    $participating = $school->participationPeriods->contains(fn ($period) => $period->starts_on->lte($today) && ($period->ends_on === null || $period->ends_on->gte($today)));
    $notStarted = $school->participationPeriods->every(fn ($period) => $period->starts_on->gt($today));
    $firstEnrolment = $school->enrolments->last();
    $firstParticipation = $school->participationPeriods->last();
@endphp
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><a href="{{ route('schools.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">← School directory</a><h1 class="mt-3 text-3xl font-bold tracking-tight" lang="bn">{{ $school->bangla_name }}</h1><p class="mt-2 text-slate-600">{{ $school->code }} · {{ $participating ? 'Participating' : ($notStarted ? 'Not yet participating' : 'Not participating') }}</p></div>
    <a href="{{ route('schools.edit', $school) }}" class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Edit identity</a>
</div>
<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">School identity</h2>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Internal code</dt><dd class="mt-1 font-medium">{{ $school->code }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bangla name</dt><dd class="mt-1 font-medium" lang="bn">{{ $school->bangla_name }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Union</dt><dd class="mt-1 font-medium">{{ $school->union ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cluster</dt><dd class="mt-1 font-medium">{{ $school->cluster ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher contact name</dt><dd class="mt-1 font-medium">{{ $school->teacher_name ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher contact phone</dt><dd class="mt-1 font-medium">{{ $school->teacher_phone ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pupil breakdown</dt><dd class="mt-1 font-medium">Not provided</dd></div>
        </dl>
    </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">Official EMIS</h2>
        @if($school->emis_code)
            <p class="mt-5 text-2xl font-semibold">{{ $school->emis_code }}</p>
            <p class="mt-2 text-sm text-slate-600">Verified against: {{ $school->emis_source }}</p>
            <p class="mt-1 text-sm text-slate-600">Verified on {{ $school->emis_verified_at->format('j M Y, g:i A') }} (Bangladesh time)</p>
        @else
            <p class="mt-5 inline-block rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900">Not provided</p>
            <p class="mt-3 text-sm text-slate-600">No official EMIS has been verified. The application has not invented one.</p>
        @endif
    </section>
</div>
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">Dated enrolment</h2>
        @if($firstParticipation && $firstEnrolment && $firstEnrolment->effective_on->gt($firstParticipation->starts_on))
            <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Enrolment before {{ $firstEnrolment->effective_on->format('j M Y') }} is unknown; the later count is not backdated.</p>
        @endif
        <ol class="mt-4 divide-y divide-slate-100">
            @forelse($school->enrolments as $enrolment)
                <li class="flex justify-between gap-4 py-3"><span>{{ $enrolment->effective_on->format('j M Y') }}</span><strong>{{ $enrolment->pupil_count }} pupils</strong></li>
            @empty
                <li class="py-3 text-slate-500">Not provided</li>
            @endforelse
        </ol>
        <p class="mt-3 text-xs text-slate-500">Later changes require the dated US2.3 workflow.</p>
    </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">Participation history</h2>
        <ol class="mt-4 divide-y divide-slate-100">
            @forelse($school->participationPeriods as $period)
                <li class="flex justify-between gap-4 py-3"><span>Started {{ $period->starts_on->format('j M Y') }}</span><strong>{{ $period->ends_on ? 'Ended '.$period->ends_on->format('j M Y') : 'No end date' }}</strong></li>
            @empty
                <li class="py-3 text-slate-500">Not provided</li>
            @endforelse
        </ol>
        <p class="mt-3 text-xs text-slate-500">Later changes require the dated US2.4 workflow.</p>
    </section>
</div>
@endsection
