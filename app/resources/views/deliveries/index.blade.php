@extends('layouts.app')
@section('title', 'My Entries')
@section('content')
<a href="{{ route('staff.home') }}" class="text-sm font-semibold text-indigo-600 hover:underline">← Field Staff home</a>

<div class="mt-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-charcoal">My Entries</h1>
        <p class="mt-1 text-slate-gray">Receipts you authored or are currently responsible for correcting.</p>
    </div>
    <a href="{{ route('field.delivery.create') }}" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Enter Delivery</a>
</div>

@if(session('status'))
    <p class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</p>
@endif

<form method="GET" action="{{ route('field.entries') }}" class="mt-6 flex flex-wrap items-end gap-3">
    <div>
        <label for="school_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">School</label>
        <select id="school_id" name="school_id" class="mt-1 rounded-lg border border-slate-300 bg-white/80 px-3 py-2 outline-none transition focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100">
            <option value="">All schools</option>
            @foreach($schools as $school)
                <option value="{{ $school->id }}" @selected(($filters['school_id'] ?? '') == (string) $school->id)>{{ $school->code }} — {{ $school->bangla_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">From</label>
        <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="mt-1 rounded-lg border border-slate-300 bg-white/80 px-3 py-2 outline-none transition focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100">
    </div>
    <div>
        <label for="to" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
        <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="mt-1 rounded-lg border border-slate-300 bg-white/80 px-3 py-2 outline-none transition focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100">
    </div>
    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Filter</button>
    <a href="{{ route('field.entries') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-charcoal hover:bg-slate-100">Reset</a>
</form>

@if($receipts->isEmpty())
    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-6 text-center">
        <p class="text-slate-gray">No entries match the selected filters.</p>
        <a href="{{ route('field.delivery.create') }}" class="mt-3 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Record a new delivery</a>
    </div>
@endif

<div class="mt-6 space-y-3">
    @foreach($receipts as $receipt)
        <div class="card-glass rounded-2xl p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $receipt->school->code }}<span class="mx-2 text-slate-300">·</span>{{ $receipt->delivery_date->format('j M Y') }}</p>
                    <p class="text-lg font-semibold text-charcoal" lang="bn">{{ $receipt->school->bangla_name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($receipt->responsible_by === auth()->id() && $receipt->entered_by !== auth()->id())
                        <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Assigned to you</span>
                    @elseif($receipt->entered_by === auth()->id() && $receipt->responsible_by !== auth()->id())
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">Reassigned</span>
                    @endif
                    @if($receipt->responsible_by === auth()->id())
                        <a href="{{ route('field.delivery.edit', $receipt) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-charcoal hover:bg-slate-100">Correct</a>
                    @endif
                </div>
            </div>
            <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-slate-gray">
                @foreach($receipt->items->sortBy(fn($line) => $line->item->sort_order) as $line)
                    <div class="flex gap-1"><dt class="text-slate-500">{{ $line->item->name }}</dt><dd class="font-semibold">{{ number_format($line->delivered_quantity) }}</dd></div>
                @endforeach
            </dl>
            @if($receipt->chalan_photo_path)
                <p class="mt-2 text-xs text-slate-500">Chalan photo attached</p>
            @endif
        </div>
    @endforeach
</div>

@if($receipts->hasPages())
    <div class="mt-6">{{ $receipts->links() }}</div>
@endif
@endsection
