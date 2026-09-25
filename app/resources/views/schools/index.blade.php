@extends('layouts.app')
@section('title', 'School directory')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Dashboard</a><h1 class="mt-3 text-3xl font-bold tracking-tight">School directory</h1><p class="mt-2 text-slate-600">Find schools by generated code, Bangla name, or verified EMIS.</p></div>
    <a href="{{ route('schools.create') }}" class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Add School</a>
</div>
<form action="{{ route('schools.index') }}" method="get" class="mt-8 flex gap-3">
    <input name="search" aria-label="Search schools" value="{{ $search }}" placeholder="Search code, name, or EMIS" class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
    <button class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold hover:bg-slate-100">Search</button>
</form>
<div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="px-5 py-4">School</th><th class="px-5 py-4">Verified EMIS</th><th class="px-5 py-4">Latest enrolment</th><th class="px-5 py-4">Participation</th><th class="px-5 py-4">Action</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($schools as $school)
            @php
                $enrolment = $school->enrolments->first();
                $today = today();
                $participating = $school->participationPeriods->contains(fn ($period) => $period->starts_on->lte($today) && ($period->ends_on === null || $period->ends_on->gte($today)));
                $notStarted = $school->participationPeriods->every(fn ($period) => $period->starts_on->gt($today));
            @endphp
            <tr>
                <td class="px-5 py-4"><div class="font-semibold" lang="bn">{{ $school->bangla_name }}</div><div class="mt-1 text-slate-500">{{ $school->code }}</div></td>
                <td class="px-5 py-4">@if($school->emis_code){{ $school->emis_code }}@else<span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-900">Not provided</span>@endif</td>
                <td class="px-5 py-4">@if($enrolment){{ $enrolment->pupil_count }} <span class="block text-xs text-slate-500">from {{ $enrolment->effective_on->format('j M Y') }}</span>@else<span class="text-slate-500">Not provided</span>@endif</td>
                <td class="px-5 py-4">{{ $participating ? 'Participating' : ($notStarted ? 'Not started' : 'Not participating') }}</td>
                <td class="px-5 py-4"><a href="{{ route('schools.show', $school) }}" class="font-semibold text-blue-700 hover:underline">View</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No schools found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $schools->links() }}</div>
@endsection
