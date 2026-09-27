@extends('layouts.app')
@section('title', 'School directory')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a><h1 class="mt-3 text-3xl font-bold tracking-tight text-base-content">School directory</h1><p class="mt-2 text-secondary-content">Find schools by internal code, Bangla name, or current EMIS.</p></div>
    <a href="{{ route('schools.create') }}" class="rounded-xl bg-primary px-5 py-3 font-semibold text-white hover:bg-primary">Add School</a>
</div>
<form action="{{ route('schools.index') }}" method="get" class="mt-8 flex flex-wrap gap-3">
    <input name="search" aria-label="Search schools" maxlength="120" value="{{ $search }}" placeholder="Search code, name, or EMIS" class="min-w-0 flex-1 rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
    <label class="flex items-center gap-2 rounded-xl border border-base-300 bg-white/80 px-4 py-3 text-sm font-semibold text-base-content"><input name="include_inactive" type="checkbox" value="1" @checked($includeInactive)> Include inactive</label>
    <button class="rounded-xl border border-base-300 bg-white/80 px-5 py-3 font-semibold hover:bg-base-200">Search</button>
    @if($search !== '' || $includeInactive)<a href="{{ route('schools.index') }}" class="self-center px-2 py-3 text-sm font-semibold text-secondary-content hover:underline">Clear</a>@endif
</form>
<div class="mt-6 overflow-x-auto card-glass rounded-2xl">
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-base-200 text-secondary-content"><tr><th class="px-5 py-4">School</th><th class="px-5 py-4">EMIS</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Latest applicable enrolment</th><th class="px-5 py-4">Next change</th><th class="px-5 py-4">Participation</th><th class="px-5 py-4">Action</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($schools as $school)
            @php
                $enrolment = $school->enrolments->first();
                $today = today();
                $participating = $school->participationPeriods->contains(fn ($period) => $period->starts_on->lte($today) && ($period->ends_on === null || $period->ends_on->gte($today)));
                $notStarted = $school->participationPeriods->isNotEmpty() && $school->participationPeriods->every(fn ($period) => $period->starts_on->gt($today));
            @endphp
            <tr class="{{ $school->is_active ? '' : 'bg-base-200 text-secondary-content' }}">
                <td class="px-5 py-4"><div class="font-semibold" lang="bn">{{ $school->bangla_name }}</div><div class="mt-1 text-secondary-content">{{ $school->code }}</div></td>
                <td class="px-5 py-4">@if($school->emis_code)<div>{{ $school->emis_code }}</div><span class="mt-1 inline-block rounded-full bg-success/10 px-2 py-1 text-xs font-semibold text-success">Verified</span>@else<span class="rounded-full bg-warning/10 px-2 py-1 text-xs font-semibold text-warning">Not provided</span>@endif</td>
                <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $school->is_active ? 'bg-success/10 text-success' : 'bg-base-200 text-base-content' }}">{{ $school->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="px-5 py-4">@if($enrolment){{ number_format($enrolment->pupil_count) }} <span class="block text-xs text-slate-500">from {{ $enrolment->effective_on->format('j M Y') }}</span>@else<span class="text-slate-500">Unknown</span>@endif</td>
                <td class="px-5 py-4">@if($nextChange = $school->scheduledEnrolments->first())<span class="rounded-full bg-primary/10 px-2 py-1 text-xs font-semibold text-primary">Scheduled</span> <span class="block text-xs text-slate-500">{{ $nextChange->effective_on->format('j M Y') }} · {{ number_format($nextChange->pupil_count) }}</span>@else<span class="text-slate-500">—</span>@endif</td>
                <td class="px-5 py-4">{{ $participating ? 'Participating' : ($notStarted ? 'Not started' : 'Not participating') }}</td>
                <td class="px-5 py-4"><a href="{{ route('schools.show', $school) }}" class="font-semibold text-primary hover:underline">View</a></td>
            </tr>
        @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-secondary-content">{{ $search !== '' || $includeInactive ? 'No schools match your search and filters.' : 'No schools found.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $schools->links() }}</div>
@endsection
