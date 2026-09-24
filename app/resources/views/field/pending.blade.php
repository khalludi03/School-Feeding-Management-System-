@extends('layouts.app')
@section('title', $title)
@section('content')
<a href="{{ route('staff.home') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Field Staff home</a>
<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"><h1 class="text-3xl font-bold">{{ $title }}</h1><p class="mt-3 text-slate-600">This delivery feature is not part of the current account and access implementation.</p></div>
@endsection
