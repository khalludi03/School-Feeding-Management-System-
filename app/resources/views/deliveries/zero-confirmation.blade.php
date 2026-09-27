@extends('layouts.app')
@section('title', 'Confirm Zero Delivery')
@section('content')
<a href="{{ route('staff.home') }}" class="text-sm font-semibold text-primary hover:underline">← Field Staff home</a>

<div class="mt-6">
    <h1 class="text-3xl font-bold tracking-tight text-foreground">Confirm zero delivery</h1>
    <p class="mt-1 text-muted-foreground">Record that a scheduled item was not supplied for a school and date.</p>
</div>

@if($errors->any())
    <div class="mt-6 rounded-xl border border-destructive/20 bg-destructive/10 p-4 text-sm text-destructive">
        <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif

<div class="card-glass mt-6 rounded-2xl p-5 shadow-sm">
    <form method="POST" action="{{ route('field.zero-confirmation.store') }}">
        @csrf
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label for="date" class="block text-xs font-semibold text-muted-foreground">Date</label>
                <input id="date" name="date" type="date" value="{{ old('date', $date->toDateString()) }}" max="{{ now()->toDateString() }}" class="mt-1 w-full rounded-lg border border-border bg-card/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
            </div>
            <div>
                <label for="school_id" class="block text-xs font-semibold text-muted-foreground">School</label>
                <select id="school_id" name="school_id" class="mt-1 w-full rounded-lg border border-border bg-card/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                    <option value="">Select a school</option>
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" @selected(old('school_id') == (string) $school->id)>{{ $school->code }} — {{ $school->bangla_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4">
                <label for="feeding_item_id" class="block text-xs font-semibold text-muted-foreground">Item</label>
            <select id="feeding_item_id" name="feeding_item_id" class="mt-1 w-full rounded-lg border border-border bg-card/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                <option value="">Select an item</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}" @selected(old('feeding_item_id') == (string) $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-4">
                <label for="reason" class="block text-xs font-semibold text-muted-foreground">Reason</label>
            <input id="reason" name="reason" type="text" maxlength="1000" value="{{ old('reason') }}" class="mt-1 w-full rounded-lg border border-border bg-card/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
        </div>

        <div class="mt-4 flex items-center gap-3">
            <x-ui.button type="submit" size="sm">Confirm zero</x-ui.button>
        </div>
    </form>
</div>
@endsection
