@extends('layouts.app')
@section('title', 'Review enrolment change')
@section('content')
@php
    $effectiveOn = \Illuminate\Support\Carbon::parse($review['effective_on']);
@endphp
<a href="{{ route('schools.show', $school) }}" class="text-sm font-semibold text-blue-700 hover:underline">← School details</a>
<div class="mt-6 max-w-5xl">
    <h1 class="text-3xl font-bold tracking-tight">Review the enrolment change</h1>
    <p class="mt-2 text-slate-600">Nothing is saved until you confirm. Check the demand impact across every open feeding cycle first.</p>
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold" lang="bn">{{ $school->bangla_name }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $school->code }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $review['status'] === 'Scheduled' ? 'bg-blue-100 text-blue-900' : 'bg-emerald-100 text-emerald-900' }}">{{ $review['status'] }}</span>
    </div>
    <dl class="mt-6 grid gap-4 sm:grid-cols-4">
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Effective</dt><dd class="mt-1 font-semibold">{{ $effectiveOn->format('j M Y') }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Count now</dt><dd class="mt-1 font-semibold">{{ $review['current_count'] === null ? 'Unknown' : number_format($review['current_count']) }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">New count</dt><dd class="mt-1 text-2xl font-bold">{{ number_format($review['pupil_count']) }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Change</dt>
            <dd class="mt-1 font-semibold">
                @if($review['current_count'] === null)
                    <span class="text-amber-700">First known count</span>
                @else
                    {{ $review['pupil_count'] - $review['current_count'] >= 0 ? '+' : '' }}{{ number_format($review['pupil_count'] - $review['current_count']) }}
                @endif
            </dd>
        </div>
    </dl>
    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
        <span class="font-semibold">Reason:</span> {{ $review['reason'] }}
    </div>
</div>

@forelse($review['cycles'] as $cycle)
    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">{{ $cycle['title'] }}</h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ \Illuminate\Support\Carbon::parse($cycle['starts_on'])->format('j M Y') }} – {{ \Illuminate\Support\Carbon::parse($cycle['ends_on'])->format('j M Y') }}
                    · projected on {{ \Illuminate\Support\Carbon::parse($cycle['evaluated_on'])->format('j M Y') }}
                </p>
            </div>
            <div class="text-right text-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ration factor</div>
                <div class="font-semibold">{{ number_format($cycle['ration_factor'], 3) }}</div>
                <div class="text-xs {{ $cycle['factor_frozen'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $cycle['factor_frozen'] ? 'Frozen for this cycle' : 'Live cycle policy' }}</div>
            </div>
        </div>
        <dl class="mt-5 grid gap-4 sm:grid-cols-3">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Count on that day</dt><dd class="mt-1 font-medium">{{ $cycle['before_count'] === null ? 'Unknown' : number_format($cycle['before_count']) }} → <strong>{{ number_format($cycle['after_count']) }}</strong></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Daily demand</dt><dd class="mt-1 font-medium">{{ $cycle['before_daily_demand'] === null ? 'Unknown' : number_format($cycle['before_daily_demand']) }} → <strong>{{ number_format($cycle['after_daily_demand']) }}</strong></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Daily variance</dt>
                <dd class="mt-1 font-medium">
                    @if($cycle['daily_demand_delta'] === null)
                        <span class="text-amber-700">Unknown</span>
                    @elseif($cycle['daily_demand_delta'] > 0)
                        <span class="font-semibold text-amber-700">+{{ number_format($cycle['daily_demand_delta']) }} more per day</span>
                    @elseif($cycle['daily_demand_delta'] < 0)
                        <span class="font-semibold text-emerald-700">{{ number_format($cycle['daily_demand_delta']) }} fewer per day</span>
                    @else
                        <span class="text-slate-600">No daily change</span>
                    @endif
                </dd>
            </div>
        </dl>
        <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">Supply days</th><th class="px-4 py-3">Cycle quantity now</th><th class="px-4 py-3">Cycle quantity after</th><th class="px-4 py-3">Variance</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($cycle['items'] as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium" lang="bn">{{ $item['name'] }}</td>
                            <td class="px-4 py-3">{{ $item['supply_days'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $item['before_quantity'] === null ? 'Unknown' : number_format($item['before_quantity']) }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $item['after_quantity'] === null ? '—' : number_format($item['after_quantity']) }}</td>
                            <td class="px-4 py-3">
                                @if($item['delta'] === null)
                                    <span class="text-amber-700">Unknown</span>
                                @elseif($item['delta'] > 0)
                                    <span class="font-semibold text-amber-700">+{{ number_format($item['delta']) }}</span>
                                @elseif($item['delta'] < 0)
                                    <span class="font-semibold text-emerald-700">{{ number_format($item['delta']) }}</span>
                                @else
                                    <span class="text-slate-600">No change</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@empty
    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">No open feeding cycle is affected</h2>
        <p class="mt-2 text-sm text-slate-600">Every feeding cycle already closed before {{ $effectiveOn->format('j M Y') }}, so this change alters no recorded cycle demand.</p>
    </section>
@endforelse

<p class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">{{ $review['calendar_note'] }}</p>

@if($review['requires_downstream_acknowledgement'])
    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
        <p class="font-semibold">Existing allocations or delivery receipts will be out of date.</p>
        <p class="mt-1">{{ $review['downstream']['allocations'] }} allocation(s) and {{ $review['downstream']['receipts'] }} receipt(s) already exist for this school. Recording this change does not alter them. They stay flagged as stale until they are reviewed separately.</p>
    </div>
@endif

<div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    @if($errors->any())
        <div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">Please correct the problem below. No change was recorded.</div>
    @endif
    <form method="post" action="{{ route('schools.enrolments.store', $school) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="effective_on" value="{{ $review['effective_on'] }}">
        <input type="hidden" name="pupil_count" value="{{ $review['pupil_count'] }}">
        <input type="hidden" name="reason" value="{{ $review['reason'] }}">
        @error('effective_on')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
        @error('pupil_count')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
        @error('reason')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
        <div class="flex flex-wrap items-center gap-4">
            <button class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">
                {{ $review['status'] === 'Scheduled' ? 'Confirm scheduled change' : 'Confirm change' }}
            </button>
            <a href="{{ route('schools.enrolments.create', $school) }}" class="font-semibold text-slate-600 hover:underline">Change the details</a>
        </div>
        <p class="text-xs text-slate-500">This review link expires in 15 minutes. Confirming can only be done once.</p>
    </form>
</div>
@endsection
