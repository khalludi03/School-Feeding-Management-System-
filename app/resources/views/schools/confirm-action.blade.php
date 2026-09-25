@extends('layouts.app')
@section('title', match ($action) { 'deactivate' => 'Confirm school deactivation', 'reactivate' => 'Confirm school reactivation' })
@section('content')
@php
    $title = match ($action) { 'deactivate' => 'Deactivate school', 'reactivate' => 'Reactivate school' };
    $route = match ($action) { 'deactivate' => 'schools.deactivate', 'reactivate' => 'schools.reactivate' };
@endphp
<a href="{{ route('schools.show', $school) }}" class="text-sm font-semibold text-blue-700 hover:underline">← School details</a>
<div class="mt-6 max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    <h1 class="text-3xl font-bold tracking-tight">{{ $title }}</h1>
    <p class="mt-2 text-slate-600">
        @if($action === 'deactivate')
            Choose the date participation stops. The school keeps generating demand and stays in reports up to the day
            before that date, and its earlier records stay readable. Records dated on or after that date have to be
            resolved with the responsible Field Staff first.
        @else
            This returns the school to the active directory and opens a new participation period. The previous period
            keeps its end date, so any gap in between still generates no demand.
        @endif
    </p>
    <dl class="mt-6 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">School</dt><dd class="mt-1 font-semibold" lang="bn">{{ $school->bangla_name }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Internal code</dt><dd class="mt-1 font-semibold">{{ $school->code }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current status</dt><dd class="mt-1 font-semibold">{{ $school->is_active ? 'Active' : 'Inactive' }}</dd></div>
    </dl>
    @if($errors->any())<div role="alert" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">Please correct the fields below. No school status change was made.</div>@endif

    @if($action === 'reactivate')
        <form method="post" action="{{ route($route, $school) }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label for="effective_on" class="mb-2 block text-sm font-semibold">Reactivation effective date</label>
                <input id="effective_on" name="effective_on" type="date" min="{{ today()->toDateString() }}" required
                       value="{{ old('effective_on', today()->toDateString()) }}"
                       class="w-full rounded-xl border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                <p class="mt-1 text-xs text-slate-500">Today or later. Participation is reopened from this date onwards.</p>
                @error('effective_on')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            @include('schools.partials.status-change-fields', ['submitLabel' => 'Confirm reactivation'])
        </form>
    @else
        <form method="get" action="{{ route('schools.deactivate.confirm', $school) }}" class="mt-8 space-y-5">
            <div>
                <label for="effective_on" class="mb-2 block text-sm font-semibold">Deactivation effective date</label>
                <input id="effective_on" name="effective_on" type="date" min="{{ today()->toDateString() }}" required
                       value="{{ old('effective_on', $impact['effective_on'] ?? today()->toDateString()) }}"
                       class="w-full rounded-xl border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                <p class="mt-1 text-xs text-slate-500">Today or later. The school stops generating demand from this date onwards.</p>
                @error('effective_on')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <button class="rounded-xl border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50">Check for conflicts</button>
        </form>

        @if($impact !== null)
            @if($impact['already_closed'])
                <div role="alert" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    This school has no open participation period, so there is nothing to stop.
                </div>
            @elseif($impact['has_conflicts'])
                <div role="alert" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <p class="font-semibold">This date would strand {{ $impact['conflict_count'] }} existing record(s).</p>
                    <p class="mt-1">Ask the responsible Field Staff to correct or remove them before saving.</p>
                    <ul class="mt-3 list-disc space-y-1 pl-5">
                        @foreach($impact['conflicts'] as $conflict)
                            <li>
                                <span class="font-medium">{{ $conflict['reference'] }}</span> on {{ $conflict['date'] }}
                                <span class="text-rose-700">({{ $conflict['responsible_staff_name'] ?? 'unassigned staff member' }})</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    No records fall on or after this date. The school will keep generating demand through
                    <span class="font-semibold">{{ $impact['last_participating_on'] }}</span>
                    and stay in reports up to that day.
                </div>
                <form method="post" action="{{ route('schools.deactivate', $school) }}" class="mt-8 space-y-5">
                    @csrf
                    <input type="hidden" name="effective_on" value="{{ $impact['effective_on'] }}">
                    @include('schools.partials.status-change-fields', ['submitLabel' => 'Confirm deactivation'])
                </form>
            @endif
        @endif
    @endif
</div>
@endsection
