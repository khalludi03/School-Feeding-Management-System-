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
        <x-ui.back-link :href="route('schools.index')" label="School directory" />
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-foreground" lang="bn">{{ $school->bangla_name }}</h1>
        <p class="mt-2 text-muted-foreground">{{ $school->code }} · {{ $participationStatus }}</p>
    </div>
    <div class="flex flex-wrap gap-3">
        @if($school->is_active)
            <x-ui.button href="{{ route('schools.enrolments.create', $school) }}" variant="success" size="lg">Schedule enrolment change</x-ui.button>
            <x-ui.button href="{{ route('schools.deactivate.confirm', $school) }}" variant="outline" size="lg" class="border-destructive text-destructive hover:bg-destructive/10">Deactivate</x-ui.button>
        @else
            <x-ui.button href="{{ route('schools.reactivate.confirm', $school) }}" variant="outline" size="lg">Reactivate</x-ui.button>
        @endif
        <x-ui.button href="{{ route('schools.edit', $school) }}" size="lg">Edit identity</x-ui.button>
    </div>
</div>
@unless($school->is_active)
    <div class="mt-6 rounded-2xl border border-warning bg-warning/10 p-4 text-sm text-warning"><span class="font-semibold">Inactive school.</span> Existing history remains available, but this school cannot receive new enrolment or participation.</div>
@endunless
<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="card-glass rounded-2xl p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-foreground">School identity</h2>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs font-semibold text-muted-foreground">Internal code</dt><dd class="mt-1 font-medium">{{ $school->code }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Lifecycle status</dt><dd class="mt-1 font-medium">{{ $school->is_active ? 'Active' : 'Inactive' }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Bangla name</dt><dd class="mt-1 font-medium" lang="bn">{{ $school->bangla_name }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Upazila</dt><dd class="mt-1 font-medium" lang="bn">{{ $school->upazila ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">District</dt><dd class="mt-1 font-medium" lang="bn">{{ $school->district ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Union</dt><dd class="mt-1 font-medium">{{ $school->union ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Cluster</dt><dd class="mt-1 font-medium">{{ $school->cluster ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Teacher contact name</dt><dd class="mt-1 font-medium">{{ $school->teacher_name ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Teacher contact phone</dt><dd class="mt-1 font-medium">{{ $school->teacher_phone ?: 'Not provided' }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Pupil breakdown</dt><dd class="mt-1 font-medium">Not provided</dd></div>
        </dl>
    </section>
    <section class="card-glass rounded-2xl p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-foreground">EMIS identity</h2>
        @if($school->emis_code)
            <p class="mt-5 text-2xl font-semibold">{{ $school->emis_code }}</p>
            <p class="mt-2"><x-ui.badge variant="success">Verified</x-ui.badge></p>
            <p class="mt-3 text-sm text-muted-foreground">Verified against: {{ $school->emis_source }}</p>
            @if($school->emis_verified_at)<p class="mt-1 text-sm text-muted-foreground">Verified on {{ $school->emis_verified_at->format('j M Y, g:i A') }} (Bangladesh time)</p>@endif
        @else
            <x-ui.badge variant="warning" class="mt-5 inline-flex">Not provided</x-ui.badge>
            <p class="mt-3 text-sm text-muted-foreground">No EMIS identity is currently recorded. Add the official code before this school takes deliveries.</p>
        @endif
    </section>
</div>
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="card-glass rounded-2xl p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h2 class="text-xl font-semibold text-foreground">Dated enrolment</h2>
            @if($nextScheduled = $school->nextScheduledEnrolment())
                <x-ui.badge>
                    Next change {{ $nextScheduled->effective_on->format('j M Y') }}
                </x-ui.badge>
            @endif
        </div>
        @if($firstParticipation && $firstEnrolment && $firstEnrolment->effective_on->gt($firstParticipation->starts_on))
            <p class="mt-3 rounded-xl border border-warning bg-warning/10 p-3 text-sm text-warning">Enrolment before {{ $firstEnrolment->effective_on->format('j M Y') }} is unknown; the later count is not backdated.</p>
        @endif
        <p class="mt-3 text-sm text-muted-foreground">
            Count in force today:
            <strong>{{ $applicableEnrolment === null ? 'Unknown' : number_format($applicableEnrolment->pupil_count) }}</strong>
        </p>
        <ol class="mt-4 divide-y divide-border">
            @forelse($school->enrolments->sortByDesc('effective_on')->sortByDesc('id') as $enrolment)
                @php
                    $status = $enrolment->statusLabel($today);
                    $statusVariant = match ($status) {
                        'Scheduled' => 'default',
                        'Cancelled' => 'neutral',
                        default => 'success',
                    };
                @endphp
                <li class="py-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="{{ $enrolment->isCancelled() ? 'text-muted-foreground line-through' : '' }}">{{ $enrolment->effective_on->format('j M Y') }}</span>
                        <span class="flex items-center gap-3">
                            <strong class="{{ $enrolment->isCancelled() ? 'text-muted-foreground line-through' : '' }}">{{ number_format($enrolment->pupil_count) }} pupils</strong>
                            <x-ui.badge variant="{{ $statusVariant }}">{{ $status }}</x-ui.badge>
                        </span>
                    </div>
                    @if($enrolment->isCancelled())
                        <p class="mt-1 text-xs text-muted-foreground">Cancelled: {{ $enrolment->cancellation_reason }}</p>
                    @elseif($enrolment->reason)
                        <p class="mt-1 text-xs text-muted-foreground">Reason: {{ $enrolment->reason }}</p>
                    @endif
                    @if($enrolment->isScheduled($today))
                        <a href="{{ route('schools.enrolments.cancel.confirm', [$school, $enrolment]) }}" class="mt-2 inline-block text-sm font-semibold text-destructive hover:underline">Cancel this change</a>
                    @endif
                </li>
            @empty
                <li class="py-3 text-muted-foreground">Not provided</li>
            @endforelse
        </ol>
        <p class="mt-3 text-xs text-muted-foreground">A count applies from its effective date until a later dated count replaces it. Cancelled changes stay in the history for audit.</p>
    </section>
    <section class="card-glass rounded-2xl p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-foreground">Participation history</h2>
        @if($school->hasOverlappingParticipationPeriods())
            <p class="mt-3 rounded-xl border border-warning bg-warning/10 p-3 text-sm text-warning">Overlapping participation periods are recorded and require review.</p>
        @endif
        <ol class="mt-4 divide-y divide-border">
            @forelse($school->participationPeriods as $period)
                <li class="flex justify-between gap-4 py-3"><span>Started {{ $period->starts_on->format('j M Y') }}</span><strong>{{ $period->ends_on ? 'Ended '.$period->ends_on->format('j M Y') : 'No end date' }}</strong></li>
            @empty
                <li class="py-3 text-muted-foreground">No participation history recorded.</li>
            @endforelse
        </ol>
        <p class="mt-3 text-xs text-muted-foreground">Demand and report rows run for each date a period covers, so a school that has left the programme stays in the reports it took part in.</p>
    </section>
</div>
@if($planning)
    <section class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><h2 class="text-xl font-semibold text-foreground">September 2026 feeding plan</h2><p class="mt-2 text-sm text-muted-foreground">Source serial {{ $planning->source_serial }} · Planning data, not delivery or participation history.</p></div>
            <x-ui.badge>Reference data</x-ui.badge>
        </div>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-xs font-semibold text-muted-foreground">Pupils</dt><dd class="mt-1 font-medium">{{ number_format($planning->pupil_count) }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">90% planning figure</dt><dd class="mt-1 font-medium">{{ number_format((float) $planning->target_pupil_count, 1) }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Daily demand</dt><dd class="mt-1 font-medium">{{ number_format($planning->daily_demand) }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Bread packets</dt><dd class="mt-1 font-medium">{{ number_format($planning->bread_quantity) }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Egg pieces</dt><dd class="mt-1 font-medium">{{ number_format($planning->egg_quantity) }}</dd></div>
            <div><dt class="text-xs font-semibold text-muted-foreground">Banana pieces</dt><dd class="mt-1 font-medium">{{ number_format($planning->banana_quantity) }}</dd></div>
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
        <p class="mt-4 text-xs text-muted-foreground">Source file: {{ $planning->source_file }}. Raw source payload is retained for provenance and is not displayed here.</p>
    </section>
@endif
<section class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-foreground">Delivery receipts</h2>
    @if($school->deliveryReceipts->isEmpty())
        <p class="mt-4 text-sm text-muted-foreground">No delivery receipts recorded for this school.</p>
    @else
        <ol class="mt-4 divide-y divide-border">
            @foreach($school->deliveryReceipts as $receipt)
                <li class="py-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-medium">{{ $receipt->delivery_date->format('j M Y') }} · Chalan {{ $receipt->chalan_number ?: 'n/a' }}</p>
                            <p class="text-xs text-muted-foreground">Author: {{ $receipt->enteredBy->name }} · Current owner: {{ $receipt->responsibleBy->name }}</p>
                        </div>
                        <a href="{{ route('admin.receipts.assign', $receipt) }}" class="rounded-lg border border-border px-3 py-1 text-xs font-semibold text-foreground hover:bg-muted">Reassign</a>
                    </div>
                    <dl class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                        @foreach($receipt->items->sortBy(fn($line) => $line->item->sort_order) as $line)
                            <div class="flex gap-1"><dt class="text-muted-foreground">{{ $line->item->name }}</dt><dd class="font-semibold">{{ number_format($line->delivered_quantity) }}</dd></div>
                        @endforeach
                    </dl>
                </li>
            @endforeach
        </ol>
    @endif
</section>

<section class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-foreground">Audit history</h2>
    @if($school->auditEvents->isEmpty())
        <p class="mt-4 text-sm text-muted-foreground">No audit history is available.</p>
    @else
        <ol class="mt-4 divide-y divide-border">
            @foreach($school->auditEvents as $event)
                <li class="py-3">
                    <div class="flex flex-wrap justify-between gap-2 text-sm"><strong>{{ str_replace('_', ' ', $event->action) }}</strong><span class="text-muted-foreground">{{ $event->created_at->format('j M Y, g:i A') }} · {{ $event->actor?->name ?? 'System' }}</span></div>
                    @if(($event->details['reason'] ?? null) !== null)<p class="mt-1 text-sm text-muted-foreground">Reason: {{ $event->details['reason'] }}</p>@endif
                    @if(($event->details['changes'] ?? null) !== null)
                        <ul class="mt-1 space-y-1 text-xs text-muted-foreground">
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
