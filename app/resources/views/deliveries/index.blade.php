@extends('layouts.app')
@section('title', 'My Entries')
@section('content')
<a href="{{ route('staff.home') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Field Staff home</a>

<div class="mt-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight">My Entries</h1>
        <p class="mt-1 text-slate-600">Delivery figures you recorded. Correcting an entry keeps both the original and the correction in the audit trail.</p>
    </div>
    <a href="{{ route('field.delivery.create') }}" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Enter Delivery</a>
</div>

@if(session('status'))
    <p class="mt-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</p>
@endif

@if($receipts->isEmpty())
    <p class="mt-6 text-slate-600">You have not recorded any delivery yet.</p>
@endif

<div class="mt-6 space-y-3">
    @foreach($receipts as $receipt)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $receipt->school->code }}<span class="mx-2 text-slate-300">·</span>{{ $receipt->delivery_date->format('j M Y') }}</p>
                    <p class="text-lg font-semibold" lang="bn">{{ $receipt->school->bangla_name }}</p>
                </div>
                <a href="{{ route('field.delivery.edit', $receipt) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Correct</a>
            </div>
            <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-slate-700">
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
