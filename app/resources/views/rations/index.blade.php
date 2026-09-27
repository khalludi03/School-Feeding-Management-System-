@extends('layouts.app')
@section('title', 'Item rations')
@section('content')
<a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
<div class="mt-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-foreground">Item rations</h1>
        <p class="mt-2 text-muted-foreground">Per-item ration factors for {{ $cycle->title }}. Each dated entry applies from its effective date onward.</p>
    </div>
    <a href="{{ route('rations.create', $cycle) }}" class="rounded-xl bg-primary px-5 py-3 font-semibold text-primary-foreground hover:bg-primary">Set new ration</a>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    @foreach($items as $item)
        @php
            $history = $itemRations->historyFor($item);
        @endphp
        <div class="card-glass rounded-2xl p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-foreground" lang="bn">{{ $item->name }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">Unit: {{ $item->unit }} · Supply days: {{ $item->supply_days ?? '—' }}</p>
            @if($history->isEmpty())
                <p class="mt-4 text-sm text-muted-foreground">No dated ration set yet.</p>
            @else
                <dl class="mt-4 space-y-3">
                    @foreach($history as $ration)
                        <div class="flex items-start justify-between gap-3 border-b border-base-200 pb-2 last:border-0">
                            <div>
                                <dt class="text-xs font-semibold text-muted-foreground">Effective {{ $ration->effective_on->format('j M Y') }}</dt>
                                <dd class="mt-1 font-semibold">{{ number_format((float) $ration->ration_factor, 3) }}</dd>
                            </div>
                            <span class="text-xs text-muted-foreground">by {{ $ration->creator?->name ?? 'System' }}</span>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    @endforeach
</div>
@endsection
