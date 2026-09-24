@extends('layouts.app')
@section('title', 'Field Staff home')
@section('content')
<p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Field Staff workspace</p>
<h1 class="mt-2 text-3xl font-bold tracking-tight">Hello, {{ auth()->user()->name }}</h1>
<p class="mt-2 text-slate-600">Choose where you want to go.</p>
<div class="mt-8 grid gap-5 md:grid-cols-3">
    @foreach([['Enter Delivery', 'field.delivery.create', 'Record food received at a school.'], ['My Entries', 'field.entries', 'Review entries you created.'], ['Daily Delivery Report', 'field.report', 'See the upazila delivery summary.']] as [$label, $route, $description])
        <a href="{{ route($route) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-blue-300 hover:shadow-md"><h2 class="text-xl font-semibold">{{ $label }}</h2><p class="mt-2 text-slate-600">{{ $description }}</p><span class="mt-5 inline-block font-semibold text-blue-700">Open →</span></a>
    @endforeach
</div>
@endsection
