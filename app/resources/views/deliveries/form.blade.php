@extends('layouts.app')
@section('title', $receipt ? 'Correct Delivery Entry' : 'Enter Delivery')
@section('content')
<a href="{{ route('staff.home') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Field Staff home</a>

<div class="mt-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight">{{ $receipt ? 'Correct Delivery Entry' : 'Enter Delivery' }}</h1>
        <p class="mt-1 text-slate-600">{{ $date->format('l, j F Y') }}<span class="mx-2 text-slate-300">·</span>{{ $cycle?->title ?? 'No feeding cycle' }}</p>
    </div>
    <form method="GET" action="{{ route('field.delivery.create') }}" class="flex items-end gap-2">
        <div>
            <label for="delivery_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
            <input id="delivery_date" name="delivery_date" type="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}" class="mt-1 rounded-lg border-slate-300">
        </div>
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Load</button>
    </form>
</div>

@if(! $isWorkingDay)
    <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">The programme does not deliver on this date, so no entry can be recorded.</p>
@endif

@if($errors->any())
    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif

@if($schools->isEmpty())
    <p class="mt-6 text-slate-600">No active school is participating on this date.</p>
@endif

<div class="mt-6 space-y-4">
    @foreach($schools as $school)
        @php
            $alreadyEntered = in_array($school->id, $existingFor, true);
            $isSubject = $receipt !== null && $receipt->school_id === $school->id;
        @endphp
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $school->code }}</p>
                    <p class="text-lg font-semibold" lang="bn">{{ $school->bangla_name }}</p>
                </div>
                @if($alreadyEntered && ! $isSubject)
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Already entered</span>
                @endif
            </div>

            <form method="POST" enctype="multipart/form-data" class="mt-4"
                  action="{{ $receipt ? route('field.delivery.update', $receipt) : route('field.delivery.store') }}">
                @csrf
                @if($receipt) @method('PUT') @else
                    <input type="hidden" name="delivery_date" value="{{ $date->toDateString() }}">
                    <input type="hidden" name="school_id" value="{{ $school->id }}">
                @endif

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($cycle?->items()->orderBy('sort_order')->get() ?? [] as $item)
                        @php $current = $receipt?->items->firstWhere('feeding_item_id', $item->id)?->delivered_quantity; @endphp
                        <div>
                            <label for="q-{{ $item->id }}" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item->name }}</label>
                            <input id="q-{{ $item->id }}" name="quantities[{{ $item->id }}]" type="number" inputmode="numeric" min="0" step="1"
                                   value="{{ $current ?? 0 }}"
                                   class="mt-1 w-full rounded-lg border-slate-300">
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="chalan_photo" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Chalan photo</label>
                        <input id="chalan_photo" name="chalan_photo" type="file" accept="image/*" class="mt-1 w-full text-sm">
                    </div>
                    <div>
                        <label for="notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                        <input id="notes" name="notes" type="text" maxlength="1000" value="{{ old('notes', $receipt?->notes) }}" class="mt-1 w-full rounded-lg border-slate-300">
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
                        {{ $receipt ? 'Save correction' : 'Record delivery' }}
                    </button>
                    @if($receipt && $receipt->chalan_photo_path)
                        <span class="text-sm text-slate-500">Chalan photo on file</span>
                    @endif
                </div>
            </form>
        </div>
    @endforeach
</div>
@endsection
