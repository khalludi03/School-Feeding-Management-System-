@extends('layouts.app')
@section('title', $title)
@section('content')
<a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Dashboard</a>
<div class="mt-6 max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
    <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Admin workspace</p>
    <h1 class="mt-3 text-3xl font-bold tracking-tight">{{ $title }}</h1>
    <p class="mt-3 text-slate-600">This module has not been built yet. Only Admin accounts can open this page.</p>
</div>
@endsection
