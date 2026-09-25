@extends('layouts.app')
@section('title', 'Working Day Calendar')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Demand settings</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Working Day Calendar</h1>
        <p class="mt-2 text-slate-600">{{ $month->format('F Y') }}<span class="mx-2 text-slate-300">·</span>{{ $workingDays }} working days of {{ $totalDays }}. No demand is generated on a marked day, and delivery entries are refused.</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.calendar', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">← Previous</a>
        <a href="{{ route('admin.calendar', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Next →</a>
    </div>
</div>

@if(session('status'))
    <p class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</p>
@endif

@if($errors->any())
    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $label)<div class="py-1">{{ $label }}</div>@endforeach
        </div>
        <div class="mt-1 grid grid-cols-7 gap-1">
            @foreach($days as $day)
                @php $blank = $day['date']->dayOfWeekIso; @endphp
                @if($blank === 1)<div></div>@endif
                <div class="min-h-20 rounded-lg border p-2 text-sm {{ $day['marked'] ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white' }}">
                    <p class="font-semibold">{{ $day['date']->day }}</p>
                    @if($day['marked'])
                        <p class="mt-1 text-xs font-semibold text-amber-800">{{ $day['marked']->name }}</p>
                        <p class="text-xs text-amber-700">{{ $day['marked']->isWeeklyOff() ? 'Weekly off' : 'Holiday' }}</p>
                        <form method="POST" action="{{ route('admin.calendar.destroy', $day['marked']) }}" class="mt-1">
                            @csrf @method('DELETE')
                            <button class="text-xs font-semibold text-red-700 hover:underline">Unmark</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold">Mark a non-working day</h2>
            <form method="POST" action="{{ route('admin.calendar.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label for="holiday_on" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                    <input id="holiday_on" name="holiday_on" type="date" value="{{ old('holiday_on') }}" class="mt-1 w-full rounded-lg border-slate-300">
                </div>
                <div>
                    <label for="kind" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Kind</label>
                    <select id="kind" name="kind" class="mt-1 w-full rounded-lg border-slate-300">
                        <option value="holiday">Public holiday</option>
                        <option value="weekly_off">Weekly off</option>
                    </select>
                </div>
                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
                    <input id="name" name="name" type="text" maxlength="160" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border-slate-300">
                </div>
                <button class="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Mark day</button>
            </form>
        </div>
    </div>
</div>
@endsection
