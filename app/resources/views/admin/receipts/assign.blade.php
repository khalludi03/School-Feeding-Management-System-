@extends('layouts.app')
@section('title', 'Reassign Correction Responsibility')
@section('content')
<a href="{{ route('schools.show', $receipt->school) }}" class="text-sm font-semibold text-primary hover:underline">← {{ $receipt->school->code }}</a>

<div class="mt-6">
    <h1 class="text-3xl font-bold tracking-tight text-base-content">Reassign correction responsibility</h1>
    <p class="mt-1 text-secondary-content">Receipt dated {{ $receipt->delivery_date->format('j M Y') }} for {{ $receipt->school->bangla_name }}.</p>
</div>

@if($errors->any())
    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-error">
        <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif

<div class="card-glass mt-6 rounded-2xl p-5 shadow-sm">
    <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Original author</dt>
            <dd class="mt-1 text-base-content">{{ $receipt->enteredBy->name }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-secondary-content">Current responsible owner</dt>
            <dd class="mt-1 text-base-content">{{ $receipt->responsibleBy->name }}</dd>
        </div>
    </dl>

    <form method="POST" action="{{ route('admin.receipts.assign.update', $receipt) }}" class="mt-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="responsible_by" class="block text-xs font-semibold uppercase tracking-wide text-secondary-content">New owner</label>
            <select id="responsible_by" name="responsible_by" required class="mt-1 w-full rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
                <option value="">Select an active Field Staff member</option>
                @foreach($staff as $user)
                    <option value="{{ $user->id }}" @selected(old('responsible_by') == (string) $user->id)>{{ $user->name }} ({{ $user->username }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="reason" class="block text-xs font-semibold uppercase tracking-wide text-secondary-content">Reason for reassignment</label>
            <input id="reason" name="reason" type="text" maxlength="2000" value="{{ old('reason') }}" required class="mt-1 w-full rounded-lg border border-base-300 bg-white/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
        </div>

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-content hover:bg-primary/90">Reassign</button>
        </div>
    </form>
</div>
@endsection
