@extends('layouts.app')
@section('title', 'Calendar conflict')
@section('content')
<a href="{{ route('admin.calendar') }}" class="text-sm font-semibold text-indigo-600 hover:underline">← Calendar</a>
<div class="mt-6">
    <h1 class="text-3xl font-bold tracking-tight text-charcoal">Calendar change blocked</h1>
    @if(($kind ?? 'holiday') === 'item_removal')
        <p class="mt-2 text-slate-gray">Removing <strong>{{ $item->name ?? 'this item' }}</strong> from {{ $date->format('j F Y') }} would invalidate existing food records.</p>
    @else
        <p class="mt-2 text-slate-gray">The date {{ $date->format('j F Y') }} has existing food records that would be invalidated.</p>
    @endif
</div>

<div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-charcoal">Affected records</h2>
    <p class="mt-1 text-sm text-slate-gray">Ask the responsible Field Staff to correct or reallocate these records before retrying the calendar change.</p>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-rose-100 text-left text-sm">
            <thead class="bg-rose-100/50 text-slate-gray">
                <tr>
                    <th class="px-4 py-3">School</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Receipt ID</th>
                    <th class="px-4 py-3">Entered by</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-rose-100">
                @foreach($conflicts as $conflict)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $conflict['school_code'] }} · {{ $conflict['school_name'] }}</td>
                        <td class="px-4 py-3">{{ $date->format('j M Y') }}</td>
                        <td class="px-4 py-3">{{ $conflict['receipt_id'] }}</td>
                        <td class="px-4 py-3">{{ $conflict['entered_by_name'] }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('field.entries') }}" class="text-sm font-semibold text-indigo-600 hover:underline">View entries</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-charcoal">Resolve and retry</h2>
    <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-slate-gray">
        <li>Contact the Field Staff responsible for the affected records.</li>
        <li>Have them correct or reallocate the delivery entries to a valid working date.</li>
        <li>Return here and retry the calendar change.</li>
    </ol>
    <a href="{{ route('admin.calendar') }}" class="mt-4 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Back to calendar</a>
</div>
@endsection
