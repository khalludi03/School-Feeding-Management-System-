@extends('layouts.app')
@section('title', $title)
@section('content')
<a href="{{ route('staff.home') }}" class="text-sm font-semibold text-primary hover:underline">← Field Staff home</a>
<div class="mt-6 card-glass rounded-2xl p-8 shadow-sm"><h1 class="text-3xl font-bold text-foreground">{{ $title }}</h1><p class="mt-3 text-muted-foreground">This delivery feature is not part of the current account and access implementation.</p></div>
@endsection
