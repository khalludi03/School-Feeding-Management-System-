@extends('layouts.app')
@section('title', 'Reassign Correction Responsibility')
@section('content')
<a href="{{ route('schools.show', $receipt->school) }}" class="text-sm font-semibold text-indigo-600 hover:underline">← {{ $receipt->school->code }}</a>

<div class="mt-6">
    <h1 class="text-3xl font-bold tracking-tight text-charcoal">Reassign correction responsibility</h1>
    <p class="mt-1 text-slate-gray">Receipt dated {{ $receipt->delivery_date->format('j M Y') }} for {{ $receipt->school->bangla_name }}.</p>
</div>

@if($errors->any())
    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif

<div class="card-glass mt-6 rounded-2xl p-5 shadow-sm">
    <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Original author</dt>
            <dd class="mt-1 text-charcoal">{{ $receipt->enteredBy->name }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current responsible owner</dt>
            <dd class="mt-1 text-charcoal">{{ $receipt->responsibleBy->name }}</dd>
        </div>
    </dl>

    <form method="POST" action="{{ route('admin.receipts.assign.update', $receipt) }}" class="mt-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="responsible_by" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">New owner</label>
            <select id="responsible_by" name="responsible_by" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white/80 px-3 py-2 outline-none transition focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100">
                <option value="">Select an active Field Staff member</option>
                @foreach($staff as $user)
                    <option value="{{ $user->id }}" @selected(old('responsible_by') == (string) $user->id)>{{ $user->name }} ({{ $user->username }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="reason" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Reason for reassignment</label>
            <input id="reason" name="reason" type="text" maxlength="2000" value="{{ old('reason') }}" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white/80 px-3 py-2 outline-none transition focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100">
        </div>

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Reassign</button>
        </div>
    </form>
</div>
@endsection
