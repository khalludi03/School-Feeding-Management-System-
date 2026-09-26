@extends('layouts.app')
@section('title', 'Field Staff home')
@section('content')
<p class="text-sm font-semibold uppercase tracking-widest text-primary">Field Staff workspace</p>
<h1 class="mt-2 text-3xl font-bold tracking-tight text-base-content">Hello, {{ auth()->user()->name }}</h1>
<p class="mt-2 text-secondary-content">Choose where you want to go.</p>
<div class="mt-8 grid gap-5 md:grid-cols-3">
    @foreach([['Enter Delivery', 'field.delivery.create', 'Record food received at a school.'], ['Confirm Zero', 'field.zero-confirmation.create', 'Record that a scheduled item was not delivered.'], ['My Entries', 'field.entries', 'Review entries you created or are assigned to.'], ['Daily Delivery Report', 'field.report', 'See the upazila delivery summary.']] as [$label, $route, $description])
        <a href="{{ route($route) }}" class="card-glass rounded-2xl p-6 shadow-sm transition hover:border-primary/30 hover:shadow-md"><h2 class="text-xl font-semibold text-base-content">{{ $label }}</h2><p class="mt-2 text-secondary-content">{{ $description }}</p><span class="mt-5 inline-block font-semibold text-primary">Open →</span></a>
    @endforeach
</div>
@endsection
