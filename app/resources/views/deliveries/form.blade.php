@extends('layouts.app')
@section('title', $receipt ? 'Correct Delivery Entry' : 'Enter Delivery')
@section('content')

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-foreground">{{ $receipt ? 'Correct Delivery Entry' : 'Enter Delivery' }}</h1>
        <p class="mt-1 text-muted-foreground">{{ $date->format('l, j F Y') }}<span class="mx-2 text-muted-foreground/50">·</span>{{ $cycle?->title ?? 'No feeding cycle' }}</p>
    </div>
    <form method="GET" action="{{ route('field.delivery.create') }}" class="flex items-end gap-2">
        <div>
            <label for="delivery_date" class="block text-xs font-semibold text-muted-foreground">Date</label>
            <input id="delivery_date" name="delivery_date" type="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}" class="mt-1 rounded-lg border border-border bg-card/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
        </div>
        <button class="rounded-lg bg-neutral px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-neutral/90">Load</button>
    </form>
</div>

@if(! $isWorkingDay)
    <p class="mt-6 rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">The programme does not deliver on this date, so no entry can be recorded.</p>
@endif

@if(count($schools) === 0 && $isWorkingDay)
    <p class="mt-6 text-muted-foreground">No active school is participating on this date.</p>
@endif

@if($isWorkingDay && count($schools) > 0)
    <div id="delivery-form-react-root" class="mt-6"></div>
    <script id="delivery-form-data" type="application/json">
    {
        "csrfToken": "{{ csrf_token() }}",
        "formAction": "{{ $receipt ? route('field.delivery.update', $receipt) : route('field.delivery.store') }}",
        "method": "{{ $receipt ? 'PUT' : 'POST' }}",
        "date": "{{ $date->toDateString() }}",
        "schools": @json($schools),
        "items": @json($cycle?->items()->orderBy('sort_order')->get(['id', 'item_key', 'name']) ?? []),
        "existingFor": @json($existingFor),
        "isEdit": @json($receipt !== null),
        "oldInput": @json(session()->getOldInput()),
        "errors": @json($errors->get('*')),
        "existingPhotoUrl": @json($receipt && $receipt->chalan_photo_path ? Storage::disk('s3')->url($receipt->chalan_photo_path) : null),
        "existingNotes": @json($receipt?->notes),
        "existingVariance": @json($receipt?->variance_explanation),
        "existingCorrectionReason": null,
        "existingChalanNumber": @json($receipt?->chalan_number),
        "existingChalanDate": @json($receipt?->chalan_date?->toDateString()),
        "existingSchoolId": @json($receipt?->school_id)
    }
    </script>
@endif

@endsection
