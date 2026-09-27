@extends('layouts.app', ['aurora' => true])
@section('title', 'Account recovery')
@section('content')
<div class="mx-auto mt-8 max-w-md card-glass rounded-3xl p-6 shadow-xl shadow-primary/10 sm:mt-14 sm:p-9">
    <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-primary">Account recovery</p>
    <h1 class="text-3xl font-bold tracking-tight text-foreground">Need a new password?</h1>
    <p class="mt-4 leading-7 text-muted-foreground">Contact your upazila Admin. They can issue a new temporary password and send it to your registered WhatsApp number.</p>
    <x-ui.button href="{{ route('login') }}" class="mt-7 inline-flex" size="lg">Back to sign in</x-ui.button>
</div>
@endsection
