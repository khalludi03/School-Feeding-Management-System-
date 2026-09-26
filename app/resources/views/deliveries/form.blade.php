@extends('layouts.app')
@section('title', $receipt ? 'Correct Delivery Entry' : 'Enter Delivery')
@section('content')
<a href="{{ route('staff.home') }}" class="text-sm font-semibold text-primary hover:underline">← Field Staff home</a>

<div class="mt-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-base-content">{{ $receipt ? 'Correct Delivery Entry' : 'Enter Delivery' }}</h1>
        <p class="mt-1 text-secondary-content">{{ $date->format('l, j F Y') }}<span class="mx-2 text-secondary-content/50">·</span>{{ $cycle?->title ?? 'No feeding cycle' }}</p>
    </div>
    <form method="GET" action="{{ route('field.delivery.create') }}" class="flex items-end gap-2">
        <div>
            <label for="delivery_date" class="block text-xs font-semibold uppercase tracking-wide text-secondary-content">Date</label>
            <input id="delivery_date" name="delivery_date" type="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}" class="mt-1 rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
        </div>
        <button class="rounded-lg bg-neutral px-4 py-2 text-sm font-semibold text-white hover:bg-neutral/90">Load</button>
    </form>
</div>

@if(! $isWorkingDay)
    <p class="mt-6 rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">The programme does not deliver on this date, so no entry can be recorded.</p>
@endif

@if($errors->any())
    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-error">
        <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif

@if($schools->isEmpty())
    <p class="mt-6 text-secondary-content">No active school is participating on this date.</p>
@endif

<div class="mt-6 space-y-4">
    @foreach($schools as $school)
        @php
            $alreadyEntered = in_array($school->id, $existingFor, true);
            $isSubject = $receipt !== null && $receipt->school_id === $school->id;
        @endphp
        <div class="card-glass rounded-2xl p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-secondary-content">{{ $school->code }}</p>
                    <p class="text-lg font-semibold text-base-content" lang="bn">{{ $school->bangla_name }}</p>
                </div>
                @if($alreadyEntered && ! $isSubject)
                    <span class="rounded-full bg-base-200 px-3 py-1 text-xs font-semibold text-base-content">Already entered</span>
                @endif
            </div>

            <form method="POST" enctype="multipart/form-data" class="mt-4"
                  action="{{ $receipt ? route('field.delivery.update', $receipt) : route('field.delivery.store') }}">
                @csrf
                @if($receipt)
                    @method('PUT')
                    <input type="hidden" name="delivery_date" value="{{ $receipt->delivery_date->toDateString() }}">
                    <input type="hidden" name="school_id" value="{{ $receipt->school_id }}">
                    <input type="hidden" name="updated_at" value="{{ $receipt->updated_at }}">
                @else
                    <input type="hidden" name="delivery_date" value="{{ $date->toDateString() }}">
                    <input type="hidden" name="school_id" value="{{ $school->id }}">
                @endif

                <div class="space-y-4">
                    @foreach($cycle?->items()->orderBy('sort_order')->get() ?? [] as $item)
                        @php
                            $current = $receipt?->items->firstWhere('feeding_item_id', $item->id)?->delivered_quantity ?? 0;
                            $line = $receipt?->items->firstWhere('feeding_item_id', $item->id);
                            $itemAllocations = $line?->allocations ?? collect();
                            if ($itemAllocations->isEmpty() && $current > 0 && app(\App\Services\ItemSupplyPattern::class)->isSuppliedOn($item, $date) === true) {
                                $itemAllocations = collect([(object) ['allocation_date' => $date, 'allocated_quantity' => $current]]);
                            }
                        @endphp
                        <div class="rounded-lg border border-base-300 p-3">
                            <div>
                                <label for="q-{{ $item->id }}" class="block text-xs font-semibold uppercase tracking-wide text-secondary-content">{{ $item->name }} received</label>
                                <input id="q-{{ $item->id }}" name="quantities[{{ $item->id }}]" type="number" inputmode="numeric" min="0" step="1"
                                       value="{{ old('quantities.'.$item->id, $current) }}"
                                       class="mt-1 w-full rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                            </div>
                            <div class="allocation-rows mt-3" data-item-id="{{ $item->id }}">
                                <p class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Distribution dates</p>
                                @foreach($itemAllocations as $index => $allocation)
                                    <div class="allocation-row mt-2 flex flex-wrap items-end gap-2">
                                        <div>
                                            <input type="date" name="allocations[{{ $item->id }}][{{ $index }}][date]" value="{{ $allocation->allocation_date->toDateString() }}" class="rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                                        </div>
                                        <div>
                                            <input type="number" name="allocations[{{ $item->id }}][{{ $index }}][quantity]" value="{{ $allocation->allocated_quantity }}" min="0" step="1" class="w-24 rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                                        </div>
                                        <button type="button" class="remove-allocation rounded-lg border border-red-200 px-2 py-2 text-xs font-semibold text-error hover:bg-red-50">Remove</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="add-allocation mt-2 text-sm font-semibold text-primary hover:text-primary" data-item-id="{{ $item->id }}">+ Add distribution date</button>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="chalan_number" class="block text-xs font-semibold uppercase tracking-wide text-secondary-content">Chalan number</label>
                        <input id="chalan_number" name="chalan_number" type="text" maxlength="100" value="{{ old('chalan_number', $receipt?->chalan_number) }}" class="mt-1 w-full rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    </div>
                    <div>
                        <label for="chalan_date" class="block text-xs font-semibold uppercase tracking-wide text-secondary-content">Chalan date</label>
                        <input id="chalan_date" name="chalan_date" type="date" value="{{ old('chalan_date', $receipt?->chalan_date?->toDateString()) }}" class="mt-1 w-full rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="chalan_photo" class="block text-xs font-semibold uppercase tracking-wide text-secondary-content">Chalan photo</label>
                        <input id="chalan_photo" name="chalan_photo" type="file" accept="image/*" class="mt-1 w-full text-sm">
                        @if($receipt && $receipt->chalan_photo_path)
                            <p class="mt-1 text-xs text-secondary-content">Existing photo will be kept unless you choose a new one.</p>
                        @endif
                    </div>
                    <div>
                        <label for="notes" class="block text-xs font-semibold uppercase tracking-wide text-secondary-content">Notes</label>
                        <input id="notes" name="notes" type="text" maxlength="1000" value="{{ old('notes', $receipt?->notes) }}" class="mt-1 w-full rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="variance_explanation" class="block text-xs font-semibold uppercase tracking-wide text-secondary-content">Variance explanation</label>
                    <input id="variance_explanation" name="variance_explanation" type="text" maxlength="2000" value="{{ old('variance_explanation', $receipt?->variance_explanation) }}" class="mt-1 w-full rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    <p class="mt-1 text-xs text-secondary-content">Required if allocated quantities differ from demand.</p>
                </div>

                @if($receipt)
                    <div class="mt-4">
                        <label for="correction_reason" class="block text-xs font-semibold uppercase tracking-wide text-secondary-content">Reason for correction</label>
                        <input id="correction_reason" name="correction_reason" type="text" maxlength="2000" value="{{ old('correction_reason') }}" class="mt-1 w-full rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                        <p class="mt-1 text-xs text-secondary-content">Required for every correction.</p>
                    </div>
                @endif

                <div class="mt-4 flex items-center gap-3">
                    <button class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary">
                        {{ $receipt ? 'Save correction' : 'Record delivery' }}
                    </button>
                    @if($receipt && $receipt->chalan_photo_path)
                        <span class="text-sm text-secondary-content">Chalan photo on file</span>
                    @endif
                </div>
            </form>
        </div>
    @endforeach
</div>

<script>
    document.querySelectorAll('.add-allocation').forEach(function (button) {
        button.addEventListener('click', function () {
            const itemId = button.dataset.itemId;
            const container = document.querySelector('.allocation-rows[data-item-id="' + itemId + '"]');
            const index = container.querySelectorAll('.allocation-row').length;
            const row = document.createElement('div');
            row.className = 'allocation-row mt-2 flex flex-wrap items-end gap-2';
            row.innerHTML =
                '<div><input type="date" name="allocations[' + itemId + '][' + index + '][date]" class="rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20"></div>' +
                '<div><input type="number" name="allocations[' + itemId + '][' + index + '][quantity]" value="0" min="0" step="1" class="w-24 rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20"></div>' +
                '<button type="button" class="remove-allocation rounded-lg border border-red-200 px-2 py-2 text-xs font-semibold text-error hover:bg-red-50">Remove</button>';
            container.appendChild(row);
        });
    });

    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-allocation')) {
            event.target.closest('.allocation-row').remove();
        }
    });
</script>
@endsection
