@extends('layouts.app')
@section('title', 'Review ration change')
@section('content')
@php
    $effectiveOn = \Illuminate\Support\Carbon::parse($review['effective_on']);
@endphp
<a href="{{ route('rations.create', $cycle) }}" class="text-sm font-semibold text-primary hover:underline">← Set item ration</a>
<div class="mt-6 max-w-5xl">
    <h1 class="text-3xl font-bold tracking-tight text-base-content">Review the ration change</h1>
    <p class="mt-2 text-secondary-content">Nothing is saved until you confirm. Check the demand impact across every participating school first.</p>
</div>

<div class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-base-content" lang="bn">{{ $review['item_name'] ?? $review['feeding_item_id'] }}</h2>
            <p class="mt-1 text-sm text-secondary-content">Ration factor: <strong>{{ number_format((float) $review['ration_factor'], 3) }}</strong> effective {{ $effectiveOn->format('j M Y') }}</p>
        </div>
    </div>
    <dl class="mt-6 grid gap-4 sm:grid-cols-3">
        <div><dt class="text-xs font-semibold text-slate-500">Total before</dt><dd class="mt-1 font-semibold">{{ $review['total_before'] === null ? 'Unknown' : number_format($review['total_before']) }}</dd></div>
        <div><dt class="text-xs font-semibold text-slate-500">Total after</dt><dd class="mt-1 font-semibold">{{ number_format($review['total_after']) }}</dd></div>
        <div><dt class="text-xs font-semibold text-slate-500">Change</dt>
            <dd class="mt-1 font-semibold">
                @if($review['total_delta'] === null)
                    <span class="text-warning">First dated ration</span>
                @elseif($review['total_delta'] > 0)
                    <span class="font-semibold text-warning">+{{ number_format($review['total_delta']) }} more per day</span>
                @elseif($review['total_delta'] < 0)
                    <span class="font-semibold text-success">{{ number_format($review['total_delta']) }} fewer per day</span>
                @else
                    <span class="text-secondary-content">No daily change</span>
                @endif
            </dd>
        </div>
    </dl>
</div>

@if(! empty($review['schools']))
    <section class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-base-content">School-level impact</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-base-200 text-secondary-content">
                    <tr>
                        <th class="px-4 py-3">School</th>
                        <th class="px-4 py-3">Before</th>
                        <th class="px-4 py-3">After</th>
                        <th class="px-4 py-3">Change</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($review['schools'] as $school)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $school['code'] }} <span class="block text-xs text-secondary-content" lang="bn">{{ $school['bangla_name'] }}</span></td>
                            <td class="px-4 py-3">{{ $school['before_daily_demand'] === null ? 'Unknown' : number_format($school['before_daily_demand']) }}</td>
                            <td class="px-4 py-3">{{ number_format($school['after_daily_demand']) }}</td>
                            <td class="px-4 py-3">
                                @if($school['delta'] === null)
                                    <span class="text-warning">First dated ration</span>
                                @elseif($school['delta'] > 0)
                                    <span class="font-semibold text-warning">+{{ number_format($school['delta']) }}</span>
                                @elseif($school['delta'] < 0)
                                    <span class="font-semibold text-success">{{ number_format($school['delta']) }}</span>
                                @else
                                    <span class="text-secondary-content">No change</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

<div class="mt-8 card-glass rounded-2xl p-6 shadow-sm sm:p-8">
    <form method="post" action="{{ route('rations.store', $cycle) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="feeding_item_id" value="{{ $review['feeding_item_id'] }}">
        <input type="hidden" name="ration_factor" value="{{ $review['ration_factor'] }}">
        <input type="hidden" name="effective_on" value="{{ $review['effective_on'] }}">
        <input type="hidden" name="reason" value="{{ old('reason', $review['reason'] ?? '') }}">
        <div class="flex flex-wrap items-center gap-4">
            <button class="rounded-xl bg-primary px-5 py-3 font-semibold text-primary-content hover:bg-primary">Confirm ration</button>
            <a href="{{ route('rations.create', $cycle) }}" class="font-semibold text-slate-500 hover:underline">Change the details</a>
        </div>
            <p class="text-xs text-slate-500">This review link expires in 15 minutes. Confirming can only be done once.</p>
    </form>
</div>
@endsection
