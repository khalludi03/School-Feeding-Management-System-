@extends('layouts.app')
@section('title', 'Item prices')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-sm font-semibold text-muted-foreground">Configuration</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-foreground">Item prices</h1>
        <p class="mt-2 text-muted-foreground">Per-item unit prices for {{ $cycle->title }}. Each dated entry applies from its effective date onward.</p>
    </div>
    <x-ui.button href="{{ route('prices.create', $cycle) }}" size="lg">Set new price</x-ui.button>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    @foreach($items as $item)
        @php
            $history = $itemPrices->historyFor($item);
        @endphp
        <div class="card-glass rounded-2xl p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-foreground" lang="bn">{{ $item->name }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">Unit: {{ $item->unit }} · Current default: {{ number_format((float) ($item->unit_price ?? 0), 3) }}</p>
            @if($history->isEmpty())
                <p class="mt-4 text-sm text-muted-foreground">No dated price set yet.</p>
            @else
                <dl class="mt-4 space-y-3">
                    @foreach($history as $price)
                        <div class="flex items-start justify-between gap-3 border-b border-base-200 pb-2 last:border-0">
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground">Effective {{ $price->effective_on->format('j M Y') }}</dt>
                                <dd class="mt-1 font-semibold">{{ number_format((float) $price->unit_price, 3) }}</dd>
                            </div>
                            <span class="text-xs text-muted-foreground">by {{ $price->creator?->name ?? 'System' }}</span>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    @endforeach
</div>
@endsection
