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
        <a href="{{ route('schools.index') }}" class="text-sm font-semibold text-primary hover:underline">← School directory</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-base-content" lang="bn">{{ $school->bangla_name }}</h1>
        <p class="mt-2 text-secondary-content">{{ $school->code }} · {{ $participationStatus }}</p>
    </div>
    <div class="flex flex-wrap gap-3">
        @if($school->is_active)
            <a href="{{ route('schools.enrolments.create', $school) }}" class="rounded-xl bg-success px-5 py-3 font-semibold text-primary-content hover:bg-success/90">Schedule enrolment change</a>
            <a href="{{ route('schools.deactivate.confirm', $school) }}" class="rounded-xl border border-rose-300 px-4 py-3 font-semibold text-error hover:bg-error/10">Deactivate</a>
        @else
            <a href="{{ route('schools.reactivate.confirm', $school) }}" class="rounded-xl border border-primary/30 px-4 py-3 font-semibold text-primary hover:bg-primary/10">Reactivate</a>
        @endif
        <a href="{{ route('schools.edit', $school) }}" class="rounded-xl bg-primary px-5 py-3 font-semibold text-primary-content hover:bg-primary">Edit identity</a>
    </div>
</div>
@unless($school->is_active)
    <div class="mt-6 rounded-2xl border border-warning bg-warning/10 p-4 text-sm text-warning"><span class="font-semibold">Inactive school.</span> Existing history remains available, but this school cannot receive new enrolment or participation.</div>
@endunless
<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="card-glass rounded-2xl p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-base-content">School identity</h2>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Internal code</dt><dd class="mt-1 font-medium">{{ $school->code }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Lifecycle status</dt><dd class="mt-1 font-medium">{{ $school->is_active ? 'Active' : 'Inactive' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Bangla name</dt><dd class="mt-1 font-medium" lang="bn">{{ $school->bangla_name }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Upazila</dt><dd class="mt-1 font-medium" lang="bn">{{ $school->upazila ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">District</dt><dd class="mt-1 font-medium" lang="bn">{{ $school->district ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Union</dt><dd class="mt-1 font-medium">{{ $school->union ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Cluster</dt><dd class="mt-1 font-medium">{{ $school->cluster ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Teacher contact name</dt><dd class="mt-1 font-medium">{{ $school->teacher_name ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Teacher contact phone</dt><dd class="mt-1 font-medium">{{ $school->teacher_phone ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Pupil breakdown</dt><dd class="mt-1 font-medium">Not provided</dd></div>
        </dl>
    </section>
    <section class="card-glass rounded-2xl p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-base-content">EMIS identity</h2>
        @if($school->emis_code)
            <p class="mt-5 text-2xl font-semibold">{{ $school->emis_code }}</p>
            <p class="mt-2"><span class="rounded-full bg-success/10 px-3 py-1 text-sm font-semibold text-success">Verified</span></p>
            <p class="mt-3 text-sm text-secondary-content">Verified against: {{ $school->emis_source }}</p>
            @if($school->emis_verified_at)<p class="mt-1 text-sm text-secondary-content">Verified on {{ $school->emis_verified_at->format('j M Y, g:i A') }} (Bangladesh time)</p>@endif
        @else
            <p class="mt-5 inline-block rounded-full bg-warning/10 px-3 py-1 text-sm font-semibold text-warning">Not provided</p>
            <p class="mt-3 text-sm text-secondary-content">No EMIS identity is currently recorded. Add the official code before this school takes deliveries.</p>
        @endif
    </section>
</div>
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="card-glass rounded-2xl p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h2 class="text-xl font-semibold text-base-content">Dated enrolment</h2>
            @if($nextScheduled = $school->nextScheduledEnrolment())
                <span class="rounded-full bg-primary/10 px-3 py-1 text-sm font-semibold text-primary">
                    Next change {{ $nextScheduled->effective_on->format('j M Y') }}
                </span>
            @endif
        </div>
        @if($firstParticipation && $firstEnrolment && $firstEnrolment->effective_on->gt($firstParticipation->starts_on))
            <p class="mt-3 rounded-xl border border-warning bg-warning/10 p-3 text-sm text-warning">Enrolment before {{ $firstEnrolment->effective_on->format('j M Y') }} is unknown; the later count is not backdated.</p>
        @endif
        <p class="mt-3 text-sm text-secondary-content">
            Count in force today:
            <strong>{{ $applicableEnrolment === null ? 'Unknown' : number_format($applicableEnrolment->pupil_count) }}</strong>
        </p>
        <ol class="mt-4 divide-y divide-slate-100">
            @forelse($school->enrolments->sortByDesc('effective_on')->sortByDesc('id') as $enrolment)
                @php
                    $status = $enrolment->statusLabel($today);
                    $statusClass = match ($status) {
                        'Scheduled' => 'bg-primary/10 text-primary',
                        'Cancelled' => 'bg-base-200 text-base-content',
                        default => 'bg-success/10 text-success',
                    };
                @endphp
                <li class="py-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="{{ $enrolment->isCancelled() ? 'text-secondary-content line-through' : '' }}">{{ $enrolment->effective_on->format('j M Y') }}</span>
                        <span class="flex items-center gap-3">
                            <strong class="{{ $enrolment->isCancelled() ? 'text-secondary-content line-through' : '' }}">{{ number_format($enrolment->pupil_count) }} pupils</strong>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>
                        </span>
                    </div>
                    @if($enrolment->isCancelled())
                        <p class="mt-1 text-xs text-secondary-content">Cancelled: {{ $enrolment->cancellation_reason }}</p>
                    @elseif($enrolment->reason)
                        <p class="mt-1 text-xs text-secondary-content">Reason: {{ $enrolment->reason }}</p>
                    @endif
                    @if($enrolment->isScheduled($today))
                        <a href="{{ route('schools.enrolments.cancel.confirm', [$school, $enrolment]) }}" class="mt-2 inline-block text-sm font-semibold text-error hover:underline">Cancel this change</a>
                    @endif
                </li>
            @empty
                <li class="py-3 text-secondary-content">Not provided</li>
            @endforelse
        </ol>
        <p class="mt-3 text-xs text-secondary-content">A count applies from its effective date until a later dated count replaces it. Cancelled changes stay in the history for audit.</p>
    </section>
    <section class="card-glass rounded-2xl p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-base-content">Participation history</h2>
        @if($school->hasOverlappingParticipationPeriods())
            <p class="mt-3 rounded-xl border border-warning bg-warning/10 p-3 text-sm text-warning">Overlapping participation periods are recorded and require review.</p>
        @endif
        <ol class="mt-4 divide-y divide-slate-100">
            @forelse($school->participationPeriods as $period)
                <li class="flex justify-between gap-4 py-3"><span>Started {{ $period->starts_on->format('j M Y') }}</span><strong>{{ $period->ends_on ? 'Ended '.$period->ends_on->format('j M Y') : 'No end date' }}</strong></li>
            @empty
                <li class="py-3 text-secondary-content">No participation history recorded.</li>
            @endforelse
        </ol>
        <p class="mt-3 text-xs text-secondary-content">Demand and report rows run for each date a period covers, so a school that has left the programme stays in the reports it took part in.</p>
    </section>
</div>
@if($planning)
    <section class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><h2 class="text-xl font-semibold text-base-content">September 2026 feeding plan</h2><p class="mt-2 text-sm text-secondary-content">Source serial {{ $planning->source_serial }} · Planning data, not delivery or participation history.</p></div>
            <span class="rounded-full bg-primary/10 px-3 py-1 text-sm font-semibold text-primary">Reference data</span>
        </div>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Pupils</dt><dd class="mt-1 font-medium">{{ number_format($planning->pupil_count) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">90% planning figure</dt><dd class="mt-1 font-medium">{{ number_format((float) $planning->target_pupil_count, 1) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Daily demand</dt><dd class="mt-1 font-medium">{{ number_format($planning->daily_demand) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Bread packets</dt><dd class="mt-1 font-medium">{{ number_format($planning->bread_quantity) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Egg pieces</dt><dd class="mt-1 font-medium">{{ number_format($planning->egg_quantity) }}</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Banana pieces</dt><dd class="mt-1 font-medium">{{ number_format($planning->banana_quantity) }}</dd></div>
        </dl>
        @if($planning->source_flags)
            <div class="mt-5 rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">
                <p class="font-semibold">Source review required</p>
                <ul class="mt-2 list-disc space-y-1 ps-5">
                    @foreach($planning->source_flags as $flag)
                        <li>{{ $flag }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <p class="mt-4 text-xs text-secondary-content">Source file: {{ $planning->source_file }}. Raw source payload is retained for provenance and is not displayed here.</p>
    </section>
@endif
<section class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-base-content">Delivery receipts</h2>
    @if($school->deliveryReceipts->isEmpty())
        <p class="mt-4 text-sm text-secondary-content">No delivery receipts recorded for this school.</p>
    @else
        <ol class="mt-4 divide-y divide-slate-100">
            @foreach($school->deliveryReceipts as $receipt)
                <li class="py-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-medium">{{ $receipt->delivery_date->format('j M Y') }} · Chalan {{ $receipt->chalan_number ?: 'n/a' }}</p>
                            <p class="text-xs text-secondary-content">Author: {{ $receipt->enteredBy->name }} · Current owner: {{ $receipt->responsibleBy->name }}</p>
                        </div>
                        <a href="{{ route('admin.receipts.assign', $receipt) }}" class="rounded-lg border border-base-300 px-3 py-1 text-xs font-semibold text-base-content hover:bg-base-200">Reassign</a>
                    </div>
                    <dl class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-secondary-content">
                        @foreach($receipt->items->sortBy(fn($line) => $line->item->sort_order) as $line)
                            <div class="flex gap-1"><dt class="text-secondary-content">{{ $line->item->name }}</dt><dd class="font-semibold">{{ number_format($line->delivered_quantity) }}</dd></div>
                        @endforeach
                    </dl>
                </li>
            @endforeach
        </ol>
    @endif
</section>

<section class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-base-content">Audit history</h2>
    @if($school->auditEvents->isEmpty())
        <p class="mt-4 text-sm text-secondary-content">No audit history is available.</p>
    @else
        <ol class="mt-4 divide-y divide-slate-100">
            @foreach($school->auditEvents as $event)
                <li class="py-3">
                    <div class="flex flex-wrap justify-between gap-2 text-sm"><strong>{{ str_replace('_', ' ', $event->action) }}</strong><span class="text-secondary-content">{{ $event->created_at->format('j M Y, g:i A') }} · {{ $event->actor?->name ?? 'System' }}</span></div>
                    @if(($event->details['reason'] ?? null) !== null)<p class="mt-1 text-sm text-secondary-content">Reason: {{ $event->details['reason'] }}</p>@endif
                    @if(($event->details['changes'] ?? null) !== null)
                        <ul class="mt-1 space-y-1 text-xs text-secondary-content">
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
