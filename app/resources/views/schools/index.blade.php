@extends('layouts.app')
@section('title', 'School directory')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a><h1 class="mt-3 text-3xl font-bold tracking-tight text-foreground">School directory</h1><p class="mt-2 text-muted-foreground">Find schools by internal code, Bangla name, or current EMIS.</p></div>
    <a href="{{ route('schools.create') }}" class="rounded-xl bg-primary px-5 py-3 font-semibold text-primary-foreground hover:bg-primary">Add School</a>
</div>
<form action="{{ route('schools.index') }}" method="get" class="mt-8 flex flex-wrap gap-3">
    <input name="search" aria-label="Search schools" maxlength="120" value="{{ $search }}" placeholder="Search code, name, or EMIS" class="min-w-0 flex-1 rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
    <select name="union" aria-label="Filter by Union" class="rounded-xl border border-border bg-card/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
        <option value="">All Unions</option>
        @foreach($unions as $u)
            <option value="{{ $u }}" @selected($union === $u)>{{ $u }}</option>
        @endforeach
    </select>
    <label class="flex items-center gap-2 rounded-xl border border-border bg-card/80 px-4 py-3 text-sm font-semibold text-foreground"><input name="include_inactive" type="checkbox" value="1" @checked($includeInactive)> Include inactive</label>
    <button class="rounded-xl border border-border bg-card/80 px-5 py-3 font-semibold hover:bg-muted">Search</button>
    @if($search !== '' || $includeInactive || $union !== '')<a href="{{ route('schools.index') }}" class="self-center px-2 py-3 text-sm font-semibold text-muted-foreground hover:underline">Clear</a>@endif
</form>
<div class="mt-6 overflow-x-auto card-glass rounded-2xl">
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-muted text-muted-foreground"><tr><th class="px-5 py-4">School Name</th><th class="px-5 py-4">School Code</th><th class="px-5 py-4">Head Teacher</th><th class="px-5 py-4">Location</th><th class="px-5 py-4">EMIS</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Action</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($schools as $school)
            <tr class="{{ $school->is_active ? '' : 'bg-muted text-muted-foreground' }}">
                <td class="px-5 py-4 font-semibold" lang="bn">{{ $school->bangla_name }}</td>
                <td class="px-5 py-4 text-muted-foreground">{{ $school->code }}</td>
                <td class="px-5 py-4">
                    @if($school->teacher_name)
                        <div class="font-semibold">{{ $school->teacher_name }}</div>
                        <div class="mt-1 text-muted-foreground">{{ $school->teacher_phone }}</div>
                    @else
                        <span class="text-muted-foreground">—</span>
                    @endif
                </td>
                <td class="px-5 py-4">
                    @if($school->union || $school->upazila || $school->district)
                        <div>{{ collect([$school->union, $school->upazila, $school->district])->filter()->join(', ') }}</div>
                        @if($school->cluster)<div class="mt-1 text-muted-foreground">Cluster: {{ $school->cluster }}</div>@endif
                    @else
                        <span class="text-muted-foreground">—</span>
                    @endif
                </td>
                <td class="px-5 py-4">
                    @if($school->emis_code)
                        <div>{{ $school->emis_code }}</div>
                        @if($school->emis_source)<div class="mt-1 text-xs text-muted-foreground">Source: {{ $school->emis_source }}</div>@endif
                    @else
                        <span class="rounded-full bg-warning/10 px-2 py-1 text-xs font-semibold text-warning">Not provided</span>
                    @endif
                </td>
                <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $school->is_active ? 'bg-success/10 text-success' : 'bg-muted text-foreground' }}">{{ $school->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="px-5 py-4"><a href="{{ route('schools.show', $school) }}" class="font-semibold text-primary hover:underline">View</a></td>
            </tr>
        @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-muted-foreground">{{ $search !== '' || $includeInactive || $union !== '' ? 'No schools match your search and filters.' : 'No schools found.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $schools->links() }}</div>
@endsection
