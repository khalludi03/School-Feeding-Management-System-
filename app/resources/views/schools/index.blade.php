@extends('layouts.app')
@section('title', 'School directory')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Dashboard</a><h1 class="mt-3 text-3xl font-bold tracking-tight">School directory</h1><p class="mt-2 text-slate-600">Find schools by internal code, Bangla name, or current EMIS.</p></div>
    <a href="{{ route('schools.create') }}" class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Add School</a>
</div>
<form action="{{ route('schools.index') }}" method="get" class="mt-8 flex flex-wrap gap-3">
    <input name="search" aria-label="Search schools" maxlength="120" value="{{ $search }}" placeholder="Search code, name, or EMIS" class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
    <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold"><input name="include_inactive" type="checkbox" value="1" @checked($includeInactive)> Include inactive</label>
    <button class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold hover:bg-slate-100">Search</button>
    @if($search !== '' || $includeInactive)<a href="{{ route('schools.index') }}" class="self-center px-2 py-3 text-sm font-semibold text-slate-600 hover:underline">Clear</a>@endif
</form>
<div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="px-5 py-4">School</th><th class="px-5 py-4">EMIS</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Latest applicable enrolment</th><th class="px-5 py-4">Next change</th><th class="px-5 py-4">Participation</th><th class="px-5 py-4">Action</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($schools as $school)
            @php
                $enrolment = $school->enrolments->first();
                $today = today();
                $participating = $school->participationPeriods->contains(fn ($period) => $period->starts_on->lte($today) && ($period->ends_on === null || $period->ends_on->gte($today)));
                $notStarted = $school->participationPeriods->isNotEmpty() && $school->participationPeriods->every(fn ($period) => $period->starts_on->gt($today));
            @endphp
            <tr class="{{ $school->is_active ? '' : 'bg-slate-50 text-slate-600' }}">
                <td class="px-5 py-4"><div class="font-semibold" lang="bn">{{ $school->bangla_name }}</div><div class="mt-1 text-slate-500">{{ $school->code }}</div></td>
                <td class="px-5 py-4">@if($school->emis_code)<div>{{ $school->emis_code }}</div><span class="mt-1 inline-block rounded-full px-2 py-1 text-xs font-semibold {{ $school->emis_is_provisional ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-900' }}">{{ $school->emis_is_provisional ? 'Provisional' : 'Verified' }}</span>@else<span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-900">Not provided</span>@endif</td>
                <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $school->is_active ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-200 text-slate-800' }}">{{ $school->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="px-5 py-4">@if($enrolment){{ number_format($enrolment->pupil_count) }} <span class="block text-xs text-slate-500">from {{ $enrolment->effective_on->format('j M Y') }}</span>@else<span class="text-slate-500">Unknown</span>@endif</td>
                <td class="px-5 py-4">@if($nextChange = $school->scheduledEnrolments->first())<span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-900">Scheduled</span> <span class="block text-xs text-slate-500">{{ $nextChange->effective_on->format('j M Y') }} · {{ number_format($nextChange->pupil_count) }}</span>@else<span class="text-slate-500">—</span>@endif</td>
                <td class="px-5 py-4">{{ $participating ? 'Participating' : ($notStarted ? 'Not started' : 'Not participating') }}</td>
                <td class="px-5 py-4"><a href="{{ route('schools.show', $school) }}" class="font-semibold text-blue-700 hover:underline">View</a></td>
            </tr>
        @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">{{ $search !== '' || $includeInactive ? 'No schools match your search and filters.' : 'No schools found.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $schools->links() }}</div>
@endsection
