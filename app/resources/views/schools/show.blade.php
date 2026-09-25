@extends('layouts.app')
@section('title', $school->bangla_name)
@section('content')
@php
    $today = today();
    $participating = $school->isParticipatingOn($today);
    $notStarted = $school->participationHasStartedOn($today) === false
        && $school->participationPeriods->isNotEmpty();
    $firstEnrolment = $school->enrolments->sortBy('effective_on')->first();
    $firstParticipation = $school->participationPeriods->sortBy('starts_on')->first();
    $planning = $school->planningSnapshots->sortByDesc(fn ($snapshot) => $snapshot->feedingCycle->starts_on)->first();
    $participationStatus = $participating ? 'Participating' : ($notStarted ? 'Not yet participating' : 'Not participating');
    $applicableEnrolment = $school->enrolments
        ->whereNull('cancelled_at')
        ->filter(fn ($enrolment) => $enrolment->effective_on->lte($today))
        ->sortByDesc('effective_on')
        ->first();
@endphp
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <a href="{{ route('schools.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">← School directory</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight" lang="bn">{{ $school->bangla_name }}</h1>
        <p class="mt-2 text-slate-600">{{ $school->code }} · {{ $participationStatus }}</p>
    </div>
    <div class="flex flex-wrap gap-3">
        @if($school->is_active)
            <a href="{{ route('schools.enrolments.create', $school) }}" class="rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white hover:bg-emerald-800">Schedule enrolment change</a>
            <a href="{{ route('schools.deactivate.confirm', $school) }}" class="rounded-xl border border-rose-300 px-4 py-3 font-semibold text-rose-700 hover:bg-rose-50">Deactivate</a>
        @else
            <a href="{{ route('schools.reactivate.confirm', $school) }}" class="rounded-xl border border-blue-300 px-4 py-3 font-semibold text-blue-700 hover:bg-blue-50">Reactivate</a>
        @endif
        <a href="{{ route('schools.edit', $school) }}" class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Edit identity</a>
    </div>
</div>
@unless($school->is_active)
    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><span class="font-semibold">Inactive school.</span> Existing history remains available, but this school cannot receive new enrolment or participation.</div>
@endunless
<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">School identity</h2>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Internal code</dt><dd class="mt-1 font-medium">{{ $school->code }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lifecycle status</dt><dd class="mt-1 font-medium">{{ $school->is_active ? 'Active' : 'Inactive' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bangla name</dt><dd class="mt-1 font-medium" lang="bn">{{ $school->bangla_name }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upazila</dt><dd class="mt-1 font-medium" lang="bn">{{ $school->upazila ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">District</dt><dd class="mt-1 font-medium" lang="bn">{{ $school->district ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Union</dt><dd class="mt-1 font-medium">{{ $school->union ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cluster</dt><dd class="mt-1 font-medium">{{ $school->cluster ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher contact name</dt><dd class="mt-1 font-medium">{{ $school->teacher_name ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teacher contact phone</dt><dd class="mt-1 font-medium">{{ $school->teacher_phone ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pupil breakdown</dt><dd class="mt-1 font-medium">Not provided</dd></div>
        </dl>
    </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">EMIS identity</h2>
        @if($school->emis_code)
            <p class="mt-5 text-2xl font-semibold">{{ $school->emis_code }}</p>
            @if($school->emis_is_provisional)
                <p class="mt-2"><span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900">Provisional</span></p>
                <p class="mt-3 text-sm text-slate-600">This is a persisted provisional identity, not an official EMIS verification.</p>
            @else
                <p class="mt-2"><span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-900">Verified</span></p>
                <p class="mt-3 text-sm text-slate-600">Verified against: {{ $school->emis_source }}</p>
                @if($school->emis_verified_at)<p class="mt-1 text-sm text-slate-600">Verified on {{ $school->emis_verified_at->format('j M Y, g:i A') }} (Bangladesh time)</p>@endif
            @endif
        @else
            <p class="mt-5 inline-block rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900">Not provided</p>
            <p class="mt-3 text-sm text-slate-600">No EMIS identity is currently recorded.</p>
        @endif
    </section>
</div>
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h2 class="text-xl font-semibold">Dated enrolment</h2>
            @if($nextScheduled = $school->nextScheduledEnrolment())
                <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-900">
                    Next change {{ $nextScheduled->effective_on->format('j M Y') }}
                </span>
            @endif
        </div>
        @if($firstParticipation && $firstEnrolment && $firstEnrolment->effective_on->gt($firstParticipation->starts_on))
            <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Enrolment before {{ $firstEnrolment->effective_on->format('j M Y') }} is unknown; the later count is not backdated.</p>
        @endif
        <p class="mt-3 text-sm text-slate-600">
            Count in force today:
            <strong>{{ $applicableEnrolment === null ? 'Unknown' : number_format($applicableEnrolment->pupil_count) }}</strong>
        </p>
        <ol class="mt-4 divide-y divide-slate-100">
            @forelse($school->enrolments->sortByDesc('effective_on')->sortByDesc('id') as $enrolment)
                @php
                    $status = $enrolment->statusLabel($today);
                    $statusClass = match ($status) {
                        'Scheduled' => 'bg-blue-100 text-blue-900',
                        'Cancelled' => 'bg-slate-200 text-slate-700',
                        default => 'bg-emerald-100 text-emerald-900',
                    };
                @endphp
                <li class="py-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="{{ $enrolment->isCancelled() ? 'text-slate-500 line-through' : '' }}">{{ $enrolment->effective_on->format('j M Y') }}</span>
                        <span class="flex items-center gap-3">
                            <strong class="{{ $enrolment->isCancelled() ? 'text-slate-500 line-through' : '' }}">{{ number_format($enrolment->pupil_count) }} pupils</strong>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>
                        </span>
                    </div>
                    @if($enrolment->isCancelled())
                        <p class="mt-1 text-xs text-slate-500">Cancelled: {{ $enrolment->cancellation_reason }}</p>
                    @elseif($enrolment->reason)
                        <p class="mt-1 text-xs text-slate-500">Reason: {{ $enrolment->reason }}</p>
                    @endif
                    @if($enrolment->isScheduled($today))
                        <a href="{{ route('schools.enrolments.cancel.confirm', [$school, $enrolment]) }}" class="mt-2 inline-block text-sm font-semibold text-rose-700 hover:underline">Cancel this change</a>
                    @endif
                </li>
            @empty
                <li class="py-3 text-slate-500">Not provided</li>
            @endforelse
        </ol>
        <p class="mt-3 text-xs text-slate-500">A count applies from its effective date until a later dated count replaces it. Cancelled changes stay in the history for audit.</p>
    </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold">Participation history</h2>
        @if($school->hasOverlappingParticipationPeriods())
            <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Overlapping participation periods are recorded and require review.</p>
        @endif
        <ol class="mt-4 divide-y divide-slate-100">
            @forelse($school->participationPeriods as $period)
                <li class="flex justify-between gap-4 py-3"><span>Started {{ $period->starts_on->format('j M Y') }}</span><strong>{{ $period->ends_on ? 'Ended '.$period->ends_on->format('j M Y') : 'No end date' }}</strong></li>
            @empty
                <li class="py-3 text-slate-500">No participation history recorded.</li>
            @endforelse
        </ol>
        <p class="mt-3 text-xs text-slate-500">Later changes require the dated US2.4 workflow.</p>
    </section>
</div>
@if($planning)
    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><h2 class="text-xl font-semibold">September 2026 feeding plan</h2><p class="mt-2 text-sm text-slate-600">Source serial {{ $planning->source_serial }} · Planning data, not delivery or participation history.</p></div>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-900">Reference data</span>
        </div>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pupils</dt><dd class="mt-1 font-medium">{{ number_format($planning->pupil_count) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">90% planning figure</dt><dd class="mt-1 font-medium">{{ number_format((float) $planning->target_pupil_count, 1) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Daily demand</dt><dd class="mt-1 font-medium">{{ number_format($planning->daily_demand) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bread packets</dt><dd class="mt-1 font-medium">{{ number_format($planning->bread_quantity) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Egg pieces</dt><dd class="mt-1 font-medium">{{ number_format($planning->egg_quantity) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Banana pieces</dt><dd class="mt-1 font-medium">{{ number_format($planning->banana_quantity) }}</dd></div>
        </dl>
        @if($planning->source_flags)
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Source review required</p>
                <ul class="mt-2 list-disc space-y-1 ps-5">
                    @foreach($planning->source_flags as $flag)
                        <li>{{ $flag }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <p class="mt-4 text-xs text-slate-500">Source file: {{ $planning->source_file }}. Raw source payload is retained for provenance and is not displayed here.</p>
    </section>
@endif
<section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-semibold">Audit history</h2>
    @if($school->auditEvents->isEmpty())
        <p class="mt-4 text-sm text-slate-500">No audit history is available.</p>
    @else
        <ol class="mt-4 divide-y divide-slate-100">
            @foreach($school->auditEvents as $event)
                <li class="py-3">
                    <div class="flex flex-wrap justify-between gap-2 text-sm"><strong>{{ str_replace('_', ' ', $event->action) }}</strong><span class="text-slate-500">{{ $event->created_at->format('j M Y, g:i A') }} · {{ $event->actor?->name ?? 'System' }}</span></div>
                    @if(($event->details['reason'] ?? null) !== null)<p class="mt-1 text-sm text-slate-600">Reason: {{ $event->details['reason'] }}</p>@endif
                    @if(($event->details['changes'] ?? null) !== null)
                        <ul class="mt-1 space-y-1 text-xs text-slate-600">
                            @foreach($event->details['changes'] as $field => $change)
                                <li>{{ str_replace('_', ' ', $field) }}: {{ $change['from'] ?? 'Not provided' }} → {{ $change['to'] ?? 'Not provided' }}</li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif
</section>
@endsection
