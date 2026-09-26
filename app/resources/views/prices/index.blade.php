@extends('layouts.app')
@section('title', 'Item prices')
@section('content')
<a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
<div class="mt-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-base-content">Item prices</h1>
        <p class="mt-2 text-secondary-content">Per-item unit prices for {{ $cycle->title }}. Each dated entry applies from its effective date onward.</p>
    </div>
    <a href="{{ route('prices.create', $cycle) }}" class="rounded-xl bg-primary px-5 py-3 font-semibold text-white hover:bg-primary">Set new price</a>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    @foreach($items as $item)
        @php
            $history = $itemPrices->historyFor($item);
        @endphp
        <div class="card-glass rounded-2xl p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-base-content" lang="bn">{{ $item->name }}</h2>
            <p class="mt-1 text-sm text-secondary-content">Unit: {{ $item->unit }} · Current default: {{ number_format((float) ($item->unit_price ?? 0), 3) }}</p>
            @if($history->isEmpty())
                <p class="mt-4 text-sm text-secondary-content">No dated price set yet.</p>
            @else
                <dl class="mt-4 space-y-3">
                    @foreach($history as $price)
                        <div class="flex items-start justify-between gap-3 border-b border-base-200 pb-2 last:border-0">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Effective {{ $price->effective_on->format('j M Y') }}</dt>
                                <dd class="mt-1 font-semibold">{{ number_format((float) $price->unit_price, 3) }}</dd>
                            </div>
                            <span class="text-xs text-secondary-content">by {{ $price->creator?->name ?? 'System' }}</span>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    @endforeach
</div>
@endsection
