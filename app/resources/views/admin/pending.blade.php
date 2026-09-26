@extends('layouts.app')
@section('title', $title)
@section('content')
<a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a>
<div class="mt-6 max-w-2xl card-glass rounded-2xl p-6 shadow-sm sm:p-8">
    <p class="text-sm font-semibold uppercase tracking-widest text-primary">Admin workspace</p>
    <h1 class="mt-3 text-3xl font-bold tracking-tight text-base-content">{{ $title }}</h1>
    <p class="mt-3 text-secondary-content">This module has not been built yet. Only Admin accounts can open this page.</p>
</div>
@endsection
