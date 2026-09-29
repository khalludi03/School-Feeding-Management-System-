@extends('layouts.app')
@section('title', 'School directory')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-sm font-semibold text-muted-foreground">Administration</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-foreground">School directory</h1>
        <p class="mt-2 text-muted-foreground">Find schools by internal code, Bangla name, or current EMIS.</p>
    </div>
    <x-ui.button :href="route('schools.create')" size="lg">Add School</x-ui.button>
</div>

<div id="school-filters-root"></div>
<script>window.__SCHOOLS_FILTERS__ = @json($filtersPayload);</script>

<div class="mt-6 overflow-x-auto card-glass rounded-2xl">
    <table class="min-w-full divide-y divide-border text-left text-sm">
        <thead class="bg-muted text-muted-foreground">
            <tr>
                <th class="px-5 py-4">
                    <a href="{{ $sortUrls['bangla_name'] }}" class="inline-flex items-center gap-1 hover:text-foreground">
                        School Name
                        @if($sort === 'bangla_name')<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                    </a>
                </th>
                <th class="px-5 py-4">
                    <a href="{{ $sortUrls['code'] }}" class="inline-flex items-center gap-1 hover:text-foreground">
                        Code
                        @if($sort === 'code')<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                    </a>
                </th>
                <th class="px-5 py-4">
                    <a href="{{ $sortUrls['teacher_name'] }}" class="inline-flex items-center gap-1 hover:text-foreground">
                        Head Teacher
                        @if($sort === 'teacher_name')<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                    </a>
                </th>
                <th class="px-5 py-4">
                    <a href="{{ $sortUrls['union'] }}" class="inline-flex items-center gap-1 hover:text-foreground">
                        Location
                        @if($sort === 'union')<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                    </a>
                </th>
                <th class="px-5 py-4">
                    <a href="{{ $sortUrls['emis_code'] }}" class="inline-flex items-center gap-1 hover:text-foreground">
                        EMIS
                        @if($sort === 'emis_code')<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                    </a>
                </th>
                <th class="px-5 py-4">
                    <a href="{{ $sortUrls['is_active'] }}" class="inline-flex items-center gap-1 hover:text-foreground">
                        Status
                        @if($sort === 'is_active')<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                    </a>
                </th>
                <th class="px-5 py-4">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
        @forelse($schools as $school)
            <tr class="{{ $school->is_active ? '' : 'bg-muted/40' }}">
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
                    @else
                        <x-ui.badge variant="warning">Not provided</x-ui.badge>
                    @endif
                </td>
                <td class="px-5 py-4">
                    @if($school->is_active)
                        <x-ui.badge variant="success">Active</x-ui.badge>
                    @else
                        <x-ui.badge variant="neutral">Inactive</x-ui.badge>
                    @endif
                </td>
                <td class="px-5 py-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('schools.show', $school) }}" class="font-semibold text-primary hover:underline">View</a>
                        @if($school->is_active)
                            <button
                                type="button"
                                class="deactivate-trigger font-semibold text-destructive hover:underline"
                                data-deactivate-url="{{ route('schools.deactivate.confirm', $school) }}"
                                data-school-name="{{ $school->bangla_name }}"
                            >Deactivate</button>
                        @else
                            <a href="{{ route('schools.reactivate.confirm', $school) }}" class="font-semibold text-primary hover:underline">Reactivate</a>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            @if($allSchoolCount === 0)
                <tr>
                    <td colspan="7" class="px-5 py-16 text-center">
                        <p class="text-base font-semibold text-foreground">No schools exist yet</p>
                        <p class="mt-1 text-sm text-muted-foreground">Add the first school to start building the directory.</p>
                        <div class="mt-4 flex justify-center">
                            <x-ui.button :href="route('schools.create')" size="sm">Add School</x-ui.button>
                        </div>
                    </td>
                </tr>
            @else
                <tr>
                    <td colspan="7" class="px-5 py-16 text-center text-muted-foreground">No schools match your search and filters.</td>
                </tr>
            @endif
        @endforelse
        </tbody>
    </table>
</div>

@if($schools->hasPages())
    <div class="mt-5">{{ $schools->links() }}</div>
@endif

<div id="deactivate-dialog-root"></div>
@endsection
