@extends('layouts.app')
@section('title', 'Calendar conflict')
@section('content')
<a href="{{ route('admin.calendar') }}" class="text-sm font-semibold text-primary hover:underline">← Calendar</a>
<div class="mt-6">
    <h1 class="text-3xl font-bold tracking-tight text-base-content">Calendar change blocked</h1>
    @if(($kind ?? 'holiday') === 'item_removal')
        <p class="mt-2 text-secondary-content">Removing <strong>{{ $item->name ?? 'this item' }}</strong> from {{ $date->format('j F Y') }} would invalidate existing food records.</p>
    @else
        <p class="mt-2 text-secondary-content">The date {{ $date->format('j F Y') }} has existing food records that would be invalidated.</p>
    @endif
</div>

<div class="mt-6 rounded-2xl border border-error bg-error/10 p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-base-content">Affected records</h2>
    <p class="mt-1 text-sm text-secondary-content">Ask the responsible Field Staff to correct or reallocate these records before retrying the calendar change.</p>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-error/10 text-left text-sm">
            <thead class="bg-error/10 text-secondary-content">
                <tr>
                    <th class="px-4 py-3">School</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Receipt ID</th>
                    <th class="px-4 py-3">Entered by</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-error/10">
                @foreach($conflicts as $conflict)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $conflict['school_code'] }} · {{ $conflict['school_name'] }}</td>
                        <td class="px-4 py-3">{{ $date->format('j M Y') }}</td>
                        <td class="px-4 py-3">{{ $conflict['receipt_id'] }}</td>
                        <td class="px-4 py-3">{{ $conflict['entered_by_name'] }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('field.entries') }}" class="text-sm font-semibold text-primary hover:underline">View entries</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-base-content">Resolve and retry</h2>
    <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-secondary-content">
        <li>Contact the Field Staff responsible for the affected records.</li>
        <li>Have them correct or reallocate the delivery entries to a valid working date.</li>
        <li>Return here and retry the calendar change.</li>
    </ol>
    <a href="{{ route('admin.calendar') }}" class="mt-4 inline-flex rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary">Back to calendar</a>
</div>
@endsection
