@extends('layouts.app')
@section('title', 'Set item ration')
@section('content')
<a href="{{ route('rations.index', $cycle) }}" class="text-sm font-semibold text-primary hover:underline">← Item rations</a>
<div class="mt-6 max-w-2xl card-glass rounded-2xl p-6 shadow-sm sm:p-8">
    <h1 class="text-3xl font-bold tracking-tight text-base-content">Set item ration</h1>
    <p class="mt-2 text-secondary-content">Enter a per-student ration for one food item in {{ $cycle->title }}. The factor applies upazila-wide from the chosen date.</p>
    @if($errors->any())
        <div role="alert" class="mt-6 rounded-xl border border-error bg-error/10 p-4 text-sm text-error">Please correct the fields below. No ration was saved.</div>
    @endif
    <form method="post" action="{{ route('rations.review', $cycle) }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="feeding_item_id" class="mb-2 block text-sm font-semibold text-base-content">Food item</label>
            <select id="feeding_item_id" name="feeding_item_id" required class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                <option value="">Select an item</option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}" @selected(old('feeding_item_id') == $item->id)>{{ $item->name }} ({{ $item->unit }})</option>
                @endforeach
            </select>
            @error('feeding_item_id')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="ration_factor" class="mb-2 block text-sm font-semibold text-base-content">Ration factor</label>
            <input id="ration_factor" name="ration_factor" type="number" step="0.001" min="0.001" max="1" required value="{{ old('ration_factor') }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
            <p class="mt-1 text-xs text-secondary-content">Enter a value between 0.001 and 1. For example, 0.900 means 90% of the planned pupil count.</p>
            @error('ration_factor')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="effective_on" class="mb-2 block text-sm font-semibold text-base-content">Effective date</label>
            <input id="effective_on" name="effective_on" type="date" min="{{ today()->toDateString() }}" required value="{{ old('effective_on', today()->toDateString()) }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
            <p class="mt-1 text-xs text-secondary-content">Today or later. The new factor applies from this date onward until a later dated change replaces it.</p>
            @error('effective_on')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="reason" class="mb-2 block text-sm font-semibold text-base-content">Reason</label>
            <textarea id="reason" name="reason" required minlength="3" maxlength="500" rows="3" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">{{ old('reason') }}</textarea>
            <p class="mt-1 text-xs text-secondary-content">Recorded in the audit history alongside the before and after factors.</p>
            @error('reason')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-wrap items-center gap-4 pt-2">
            <button type="submit" class="rounded-xl bg-primary px-5 py-3 font-semibold text-primary-content hover:bg-primary">Review demand impact</button>
            <a href="{{ route('rations.index', $cycle) }}" class="font-semibold text-secondary-content hover:underline">Cancel</a>
        </div>
    </form>
</div>
@endsection
