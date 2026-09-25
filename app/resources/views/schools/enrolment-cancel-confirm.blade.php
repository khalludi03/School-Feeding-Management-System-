@extends('layouts.app')
@section('title', 'Cancel scheduled enrolment change')
@section('content')
<a href="{{ route('schools.show', $school) }}" class="text-sm font-semibold text-blue-700 hover:underline">← School details</a>
<div class="mt-6 max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    <h1 class="text-3xl font-bold tracking-tight">Cancel the scheduled change</h1>
    <p class="mt-2 text-slate-600">
        This change is dated {{ $enrolment->effective_on->format('j M Y') }}, which has not arrived yet, so it can be withdrawn. The record is kept for audit and stops applying; the previously applicable count takes over from that date.
    </p>
    <dl class="mt-6 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">School</dt><dd class="mt-1 font-semibold" lang="bn">{{ $school->bangla_name }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Scheduled count</dt><dd class="mt-1 font-semibold">{{ number_format($enrolment->pupil_count) }}</dd></div>
        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Effective date</dt><dd class="mt-1 font-semibold">{{ $enrolment->effective_on->format('j M Y') }}</dd></div>
    </dl>
    @if($errors->any())<div role="alert" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">Please correct the fields below. The change was not cancelled.</div>@endif
    <form method="post" action="{{ route('schools.enrolments.cancel', [$school, $enrolment]) }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="reason" class="mb-2 block text-sm font-semibold">Reason for cancelling</label>
            <textarea id="reason" name="reason" required minlength="3" maxlength="500" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">{{ old('reason') }}</textarea>
            @error('reason')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-wrap items-center gap-4 pt-2">
            <button class="rounded-xl bg-rose-700 px-5 py-3 font-semibold text-white hover:bg-rose-800">Cancel this change</button>
            <a href="{{ route('schools.show', $school) }}" class="font-semibold text-slate-600 hover:underline">Keep it scheduled</a>
        </div>
    </form>
</div>
@endsection
