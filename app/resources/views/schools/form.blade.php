@extends('layouts.app')
@section('title', $school ? 'Edit school identity' : 'Add School')
@section('content')
<a href="{{ $school ? route('schools.show', $school) : route('schools.index') }}" class="text-sm font-semibold text-primary hover:underline">← {{ $school ? 'School details' : 'School directory' }}</a>
<div class="mt-6 max-w-3xl card-glass rounded-2xl p-6 shadow-sm sm:p-8">
    <h1 class="text-3xl font-bold tracking-tight text-base-content">{{ $school ? 'Edit school identity' : 'Add School' }}</h1>
    <p class="mt-2 text-slate-500">{{ $school ? 'Correct identity and optional details. Dated enrolment and participation history cannot be changed here.' : 'A permanent internal code starting at AN-001 is assigned when you save. An official EMIS code is required.' }}</p>
    @if($school)<div class="mt-6 rounded-xl border border-base-300 bg-base-200 p-4 text-sm"><span class="font-semibold">Internal school code:</span> {{ $school->code }}      <span class="ml-2 text-slate-500">Cannot be changed</span></div>@endif
    @error('code')<p class="mt-3 text-sm text-error">{{ $message }}</p>@enderror
    @if($errors->any())<div role="alert" class="mt-6 rounded-xl border border-error bg-error/10 p-4 text-sm text-error">Please correct the fields below. No school change was saved.</div>@endif
    <form method="post" action="{{ $school ? route('schools.update', $school) : route('schools.store') }}" class="mt-8 space-y-6">
        @csrf
        @if($school) @method('PUT') @endif
        <div><label for="bangla_name" class="mb-2 block text-sm font-semibold text-base-content">School name in Bangla</label><input id="bangla_name" name="bangla_name" lang="bn" required maxlength="255" value="{{ old('bangla_name', $school?->bangla_name) }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">@error('bangla_name')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>

        @unless($school)
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="enrolment_count" class="mb-2 block text-sm font-semibold text-base-content">Initial enrolment</label><input id="enrolment_count" name="enrolment_count" type="number" min="0" max="1000000" step="1" required value="{{ old('enrolment_count') }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20"><p class="mt-1 text-xs text-secondary-content">Enter 0 only when the confirmed count is zero.</p>@error('enrolment_count')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
                <div><label for="enrolment_effective_on" class="mb-2 block text-sm font-semibold text-base-content">Enrolment effective date</label><input id="enrolment_effective_on" name="enrolment_effective_on" type="date" max="{{ today()->toDateString() }}" required value="{{ old('enrolment_effective_on') }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">@error('enrolment_effective_on')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
            </div>
            <div><label for="participation_starts_on" class="mb-2 block text-sm font-semibold text-base-content">Participation start date</label><input id="participation_starts_on" name="participation_starts_on" type="date" required value="{{ old('participation_starts_on', today()->toDateString()) }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20"><p class="mt-1 text-xs text-secondary-content">Future dates are allowed. The school will not be selectable for delivery before this date.</p>@error('participation_starts_on')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
        @else
            <p class="rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">Enrolment and participation dates are changed from the school details page, using the dated workflows that keep earlier demand intact. This form never overwrites that history.</p>
        @endunless

        <fieldset class="space-y-5 border-t border-base-300 pt-6"><legend class="text-lg font-semibold text-base-content">EMIS identity</legend>
            <p class="text-sm text-secondary-content">Every school needs its official EMIS code, taken from an official roster or school document. The code must be unique across the directory.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="emis_code" class="mb-2 block text-sm font-semibold text-base-content">Official EMIS code</label><input id="emis_code" name="emis_code" required maxlength="64" value="{{ old('emis_code', $school?->emis_code) }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">@error('emis_code')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
                <div><label for="emis_source" class="mb-2 block text-sm font-semibold text-base-content">Official source</label><input id="emis_source" name="emis_source" required maxlength="255" placeholder="Roster or school document reference" value="{{ old('emis_source', $school?->emis_source) }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">@error('emis_source')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
            </div>
            <div><label class="flex items-start gap-3 text-sm font-semibold text-base-content"><input name="emis_verified" type="checkbox" value="1" @checked(old('emis_verified')) class="mt-1 rounded border-base-300 text-primary">I checked this official EMIS code against the official source above.</label><p class="mt-1 text-xs text-secondary-content">Required when adding or changing an official code or source. The verification time and Admin are recorded automatically.</p>@error('emis_verified')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
        </fieldset>

        <fieldset class="space-y-5 border-t border-base-300 pt-6"><legend class="text-lg font-semibold text-base-content">Optional details</legend>
            <p class="text-sm text-secondary-content">Leave unknown values blank. They will appear as Not provided, not zero.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="union" class="mb-2 block text-sm font-semibold text-base-content">Union</label><input id="union" name="union" maxlength="120" value="{{ old('union', $school?->union) }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">@error('union')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
                <div><label for="cluster" class="mb-2 block text-sm font-semibold text-base-content">Cluster</label><input id="cluster" name="cluster" maxlength="120" value="{{ old('cluster', $school?->cluster) }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">@error('cluster')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
                <div><label for="teacher_name" class="mb-2 block text-sm font-semibold text-base-content">Teacher contact name</label><input id="teacher_name" name="teacher_name" maxlength="255" value="{{ old('teacher_name', $school?->teacher_name) }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">@error('teacher_name')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
                <div><label for="teacher_phone" class="mb-2 block text-sm font-semibold text-base-content">Teacher contact phone</label><input id="teacher_phone" name="teacher_phone" type="tel" placeholder="01XXXXXXXXX" value="{{ old('teacher_phone', $school?->teacher_phone) }}" class="w-full rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">@error('teacher_phone')<p class="mt-1 text-sm text-error">{{ $message }}</p>@enderror</div>
            </div>
        </fieldset>
        <div class="flex flex-wrap items-center gap-4 pt-2"><button class="rounded-xl bg-primary px-5 py-3 font-semibold text-white hover:bg-primary">{{ $school ? 'Save identity changes' : 'Create school' }}</button><a href="{{ $school ? route('schools.show', $school) : route('schools.index') }}" class="font-semibold text-slate-500 hover:underline">Cancel</a></div>
    </form>
</div>
@endsection
