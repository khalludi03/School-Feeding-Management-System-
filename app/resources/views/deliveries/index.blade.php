@extends('layouts.app')
@section('title', 'My Entries')
@section('content')
<a href="{{ route('staff.home') }}" class="text-sm font-semibold text-primary hover:underline">← Field Staff home</a>

<div class="mt-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-foreground">My Entries</h1>
        <p class="mt-1 text-muted-foreground">Receipts you authored or are currently responsible for correcting.</p>
    </div>
    <x-ui.button href="{{ route('field.delivery.create') }}" size="sm">Enter Delivery</x-ui.button>
</div>

@if(session('status'))
    <p class="mt-6 rounded-xl border border-success/20 bg-success/10 p-4 text-sm text-success">{{ session('status') }}</p>
@endif

<form method="GET" action="{{ route('field.entries') }}" class="mt-6 flex flex-wrap items-end gap-3">
    <div>
        <label for="school_id" class="block text-xs font-semibold text-muted-foreground">School</label>
        <select id="school_id" name="school_id" class="mt-1 rounded-lg border border-border bg-card/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
            <option value="">All schools</option>
            @foreach($schools as $school)
                <option value="{{ $school->id }}" @selected(($filters['school_id'] ?? '') == (string) $school->id)>{{ $school->code }} — {{ $school->bangla_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="from" class="block text-xs font-semibold text-muted-foreground">From</label>
        <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="mt-1 rounded-lg border border-border bg-card/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
    </div>
    <div>
        <label for="to" class="block text-xs font-semibold text-muted-foreground">To</label>
        <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="mt-1 rounded-lg border border-border bg-card/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
    </div>
    <button class="rounded-lg bg-neutral px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-neutral/90">Filter</button>
    <a href="{{ route('field.entries') }}" class="rounded-lg border border-border px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted">Reset</a>
</form>

@if($receipts->isEmpty())
    <div class="mt-6 rounded-xl border border-border bg-muted p-6 text-center">
        <p class="text-muted-foreground">No entries match the selected filters.</p>
        <x-ui.button href="{{ route('field.delivery.create') }}" size="sm" class="mt-3">Record a new delivery</x-ui.button>
    </div>
@endif

<div class="mt-6 space-y-3">
    @foreach($receipts as $receipt)
        <div class="card-glass rounded-2xl p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-muted-foreground">{{ $receipt->school->code }}<span class="mx-2 text-muted-foreground/70">·</span>{{ $receipt->delivery_date->format('j M Y') }}</p>
                    <p class="text-lg font-semibold text-foreground" lang="bn">{{ $receipt->school->bangla_name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($receipt->responsible_by === auth()->id() && $receipt->entered_by !== auth()->id())
                        <x-ui.badge variant="warning">Assigned to you</x-ui.badge>
                    @elseif($receipt->entered_by === auth()->id() && $receipt->responsible_by !== auth()->id())
                        <span class="rounded-full bg-muted px-2 py-1 text-xs font-semibold text-foreground">Reassigned</span>
                    @endif
                    @if($receipt->responsible_by === auth()->id())
                        <a href="{{ route('field.delivery.edit', $receipt) }}" class="rounded-lg border border-border px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted">Correct</a>
                    @endif
                </div>
            </div>
            <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted-foreground">
                @foreach($receipt->items->sortBy(fn($line) => $line->item->sort_order) as $line)
                    <div class="flex gap-1"><dt class="text-muted-foreground">{{ $line->item->name }}</dt><dd class="font-semibold">{{ number_format($line->delivered_quantity) }}</dd></div>
                @endforeach
            </dl>
            @if($receipt->chalan_photo_path)
                <p class="mt-2 text-xs text-muted-foreground">Chalan photo attached</p>
            @endif
        </div>
    @endforeach
</div>

@if($receipts->hasPages())
    <div class="mt-6">{{ $receipts->links() }}</div>
@endif
@endsection
