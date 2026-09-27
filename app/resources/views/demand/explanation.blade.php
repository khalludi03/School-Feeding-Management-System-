@extends('layouts.app')
@section('title', 'Demand explanation')
@section('content')
@php
    $isAdmin = auth()->user()->role === 'admin';
    $backRoute = $isAdmin ? route('schools.show', $school) : route('staff.home');
    $backLabel = $isAdmin ? '← School details' : '← Field Staff home';
@endphp
<a href="{{ $backRoute }}" class="text-sm font-semibold text-primary hover:underline">{{ $backLabel }}</a>
<div class="mt-6">
    <h1 class="text-3xl font-bold tracking-tight text-base-content">Demand explanation</h1>
    <p class="mt-2 text-secondary-content">{{ $school->code }} · {{ $school->bangla_name }} · {{ $date->format('l, j F Y') }}</p>
</div>

@if(isset($error))
    <div class="mt-6 rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">{{ $error }}</div>
@endif

@if(isset($error))
    <div class="mt-6 rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">{{ $error }}</div>
@endif

@if($explanation !== null)
    @if($explanation['setup_incomplete'])
    <div class="mt-6 rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">
        <p class="font-semibold">Setup incomplete</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($explanation['setup_incomplete_reasons'] as $reason)
                <li>{{ $reason }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
        <div class="card-glass rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">Working day</p>
            <p class="mt-1 text-lg font-semibold">{{ $explanation['is_working_day'] ? 'Yes' : 'No' }}</p>
            @if(! $explanation['is_working_day'])
                <p class="mt-1 text-xs text-slate-500">{{ $explanation['working_day_reason'] }}</p>
            @endif
        </div>
        <div class="card-glass rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">Participating</p>
            <p class="mt-1 text-lg font-semibold">{{ $explanation['is_participating'] ? 'Yes' : 'No' }}</p>
            @if(! $explanation['is_participating'])
                <p class="mt-1 text-xs text-slate-500">{{ $explanation['participation_reason'] }}</p>
            @endif
        </div>
        <div class="card-glass rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">Enrolment count</p>
            <p class="mt-1 text-lg font-semibold">{{ $explanation['pupil_count'] === null ? 'Unknown' : number_format($explanation['pupil_count']) }}</p>
            @if($explanation['pupil_count_effective_on'])
                <p class="mt-1 text-xs text-slate-500">Effective {{ $explanation['pupil_count_effective_on'] }}</p>
            @endif
        </div>
        <div class="card-glass rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">Daily demand</p>
            <p class="mt-1 text-lg font-semibold">{{ $explanation['daily_demand'] === null ? 'Unknown' : number_format($explanation['daily_demand']) }}</p>
            @if($explanation['ration_factor'] !== null)
                <p class="mt-1 text-xs text-slate-500">Ration {{ number_format((float) $explanation['ration_factor'], 3) }} ({{ $explanation['ration_source'] === 'item_dated' ? 'dated' : 'cycle' }})</p>
            @endif
        </div>
    </div>

    <div class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-base-content">Item schedule and demand</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-base-200 text-secondary-content">
                    <tr>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3">Supply days</th>
                        <th class="px-4 py-3">Supplied on date?</th>
                        <th class="px-4 py-3">Demand</th>
                        <th class="px-4 py-3">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($explanation['items'] as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $item['name'] }}</td>
                            <td class="px-4 py-3">{{ $item['unit'] }}</td>
                            <td class="px-4 py-3">{{ $item['supply_days'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($item['supplied_on_date'] === true)
                                    <span class="text-success">Yes</span>
                                @elseif($item['supplied_on_date'] === false)
                                    <span class="text-secondary-content">No</span>
                                @else
                                    <span class="text-warning">Unknown</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($item['demand'] === null)
                                    <span class="text-warning">Unknown</span>
                                @elseif($item['demand'] === 0)
                                    <span class="text-secondary-content">0</span>
                                @else
                                    <span class="font-semibold">{{ number_format($item['demand']) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($item['zero_reason'])
                                    <span class="text-xs text-secondary-content">{{ match($item['zero_reason']) { 'holiday' => 'Non-working day', 'not_participating' => 'Not participating', 'item_not_scheduled' => 'Not scheduled for this weekday', default => $item['zero_reason'] } }}</span>
                                @elseif($item['unknown_reason'])
                                    <span class="text-xs text-warning">{{ match($item['unknown_reason']) { 'no_supply_pattern' => 'Supply weekdays not configured', 'missing_ration' => 'No ration factor available', 'no_pupil_count' => 'No enrolment count recorded', default => $item['unknown_reason'] } }}</span>
                                @else
                                    <span class="text-xs text-secondary-content">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-base-content">Change date</h2>
    <form method="get" action="{{ route($isAdmin ? 'schools.demand.explain' : 'field.demand.explain', $school) }}" class="mt-4 flex items-end gap-3">
        <div>
            <label for="date" class="block text-xs font-semibold text-slate-500">Date</label>
            <input id="date" name="date" type="date" value="{{ $date->toDateString() }}" class="mt-1 rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
        </div>
        <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-content hover:bg-primary">Show</button>
    </form>
</div>
@endsection
