@extends('layouts.app')
@section('title', 'Review price change')
@section('content')
<a href="{{ route('prices.create', $cycle) }}" class="text-sm font-semibold text-primary hover:underline">← Set item price</a>
<div class="mt-6 max-w-5xl">
    <h1 class="text-3xl font-bold tracking-tight text-base-content">Review the price change</h1>
    <p class="mt-2 text-secondary-content">Nothing is saved until you confirm. Check the applicability to existing receipt dates first.</p>
</div>

<div class="mt-6 card-glass rounded-2xl p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-base-content" lang="bn">{{ $review['item_name'] }}</h2>
            <p class="mt-1 text-sm text-slate-500">Unit: {{ $review['unit'] }}</p>
        </div>
        <div class="text-right text-sm">
            <div class="text-xs font-semibold text-slate-500">New price</div>
            <div class="font-semibold">{{ number_format((float) $review['unit_price'], 3) }}</div>
            <div class="text-xs text-secondary-content">Effective {{ $review['effective_on'] }}</div>
        </div>
    </div>
</div>

<div class="mt-8 card-glass rounded-2xl p-6 shadow-sm sm:p-8">
    <form method="post" action="{{ route('prices.store', $cycle) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="feeding_item_id" value="{{ $review['feeding_item_id'] }}">
        <input type="hidden" name="unit_price" value="{{ $review['unit_price'] }}">
        <input type="hidden" name="effective_on" value="{{ $review['effective_on'] }}">
        <input type="hidden" name="reason" value="{{ old('reason', $review['reason'] ?? '') }}">
        <div class="flex flex-wrap items-center gap-4">
            <button class="rounded-xl bg-primary px-5 py-3 font-semibold text-white hover:bg-primary">Confirm price</button>
            <a href="{{ route('prices.create', $cycle) }}" class="font-semibold text-slate-500 hover:underline">Change the details</a>
        </div>
         <p class="text-xs text-slate-500">This review link expires in 15 minutes. Confirming can only be done once.</p>
    </form>
</div>
@endsection
