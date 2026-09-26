@extends('layouts.app')
@section('title', 'Configure month items')
@section('content')
<a href="{{ route('admin.calendar') }}" class="text-sm font-semibold text-primary hover:underline">← Calendar</a>
<div class="mt-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-base-content">Configure items</h1>
        <p class="mt-2 text-secondary-content">{{ $month->format('F Y') }} · Select which items are scheduled on each date. Weekday patterns are used as defaults.</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.calendar', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold hover:bg-base-200">← Previous</a>
        <a href="{{ route('admin.calendar', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="rounded-lg border border-base-300 px-4 py-2 text-sm font-semibold hover:bg-base-200">Next →</a>
    </div>
</div>

@if(session('status'))
    <p class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</p>
@endif

<div class="mt-6 rounded-2xl border border-base-300 bg-white p-6 shadow-sm">
    <form method="post" action="{{ route('admin.calendar.month-config.update', ['cycle' => $cycle, 'month' => $month->format('Y-m')]) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">

        <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase tracking-wide text-secondary-content">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $label)<div class="py-1">{{ $label }}</div>@endforeach
        </div>
        <div class="grid grid-cols-7 gap-1">
            @foreach($days as $day)
                @php $blank = $day['date']->dayOfWeekIso; @endphp
                @if($blank === 1)<div></div>@endif
                <div class="min-h-24 rounded-lg border p-2 text-sm {{ $day['setup_incomplete'] ? 'border-amber-300 bg-amber-50' : 'border-base-300 bg-white' }}">
                    <p class="font-semibold">{{ $day['date']->day }}</p>
                    @foreach($items as $item)
                        @php $itemData = $day['items'][$item->id]; @endphp
                        <label class="mt-1 flex items-center gap-1">
                            <input type="checkbox" name="items[]" value="{{ $item->id }}-{{ $day['date']->format('Y-m-d') }}" {{ $itemData['scheduled'] ? 'checked' : '' }} class="rounded border-base-300 text-primary focus:ring-primary">
                            <span class="text-xs">{{ $item->name }}</span>
                        </label>
                    @endforeach
                    @if($day['setup_incomplete'])
                        <p class="mt-1 text-xs font-semibold text-warning">Setup incomplete</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary">Save configuration</button>
            <span class="text-xs text-secondary-content">Checked dates are scheduled; unchecking removes the override and falls back to weekday patterns.</span>
        </div>
    </form>
</div>
@endsection
