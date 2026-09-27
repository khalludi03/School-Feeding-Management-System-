@extends('layouts.app')
@section('title', 'Cancel scheduled enrolment change')
@section('content')
<a href="{{ route('schools.show', $school) }}" class="text-sm font-semibold text-primary hover:underline">← School details</a>
<div class="mt-6 max-w-2xl card-glass rounded-2xl p-6 shadow-sm sm:p-8">
    <h1 class="text-3xl font-bold tracking-tight text-base-content">Cancel the scheduled change</h1>
    <p class="mt-2 text-secondary-content">
        This change is dated {{ $enrolment->effective_on->format('j M Y') }}, which has not arrived yet, so it can be withdrawn. The record is kept for audit and stops applying; the previously applicable count takes over from that date.
    </p>
    <dl class="mt-6 grid gap-4 rounded-xl border border-base-300 bg-base-200 p-4 sm:grid-cols-3">
        <div><dt class="text-xs font-semibold text-slate-500">School</dt><dd class="mt-1 font-semibold" lang="bn">{{ $school->bangla_name }}</dd></div>
        <div><dt class="text-xs font-semibold text-slate-500">Scheduled count</dt><dd class="mt-1 font-semibold">{{ number_format($enrolment->pupil_count) }}</dd></div>
        <div><dt class="text-xs font-semibold text-slate-500">Effective date</dt><dd class="mt-1 font-semibold">{{ $enrolment->effective_on->format('j M Y') }}</dd></div>
    </dl>
    @if($errors->any())<div role="alert" class="mt-6 rounded-xl border border-error bg-error/10 p-4 text-sm text-error">Please correct the fields below. The change was not cancelled.</div>@endif
    <form method="post" action="{{ route('schools.enrolments.cancel', [$school, $enrolment]) }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="reason" class="mb-2 block text-sm font-semibold text-base-content">Reason for cancelling</label>
            <textarea id="reason" name="reason" required minlength="3" maxlength="500" rows="3" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">{{ old('reason') }}</textarea>
            @error('reason')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-wrap items-center gap-4 pt-2">
            <button class="rounded-xl bg-error px-5 py-3 font-semibold text-primary-content hover:bg-error/90">Cancel this change</button>
            <a href="{{ route('schools.show', $school) }}" class="font-semibold text-slate-500 hover:underline">Keep it scheduled</a>
        </div>
    </form>
</div>
@endsection
