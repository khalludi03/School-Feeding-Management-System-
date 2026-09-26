@extends('layouts.app')
@section('title', 'Staff accounts')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-primary hover:underline">← Dashboard</a><h1 class="mt-3 text-3xl font-bold tracking-tight text-base-content">Staff accounts</h1><p class="mt-2 text-secondary-content">Manage access without deleting delivery history.</p></div>
    <a href="{{ route('staff.create') }}" class="rounded-xl bg-primary px-5 py-3 font-semibold text-primary-content hover:bg-primary">Create Field Staff</a>
</div>
<form action="{{ route('staff.index') }}" method="get" class="mt-8 flex gap-3">
    <input name="search" aria-label="Search staff" value="{{ $search }}" placeholder="Search name, username, or WhatsApp" class="min-w-0 flex-1 rounded-xl border border-base-300 bg-white/80 px-4 py-3 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
    <button class="rounded-xl border border-base-300 bg-white/80 px-5 py-3 font-semibold hover:bg-base-200">Search</button>
</form>
<div class="mt-6 overflow-x-auto card-glass rounded-2xl">
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-base-200 text-secondary-content"><tr><th class="px-5 py-4">Staff</th><th class="px-5 py-4">WhatsApp</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($staff as $member)
            <tr>
                <td class="px-5 py-4"><div class="font-semibold">{{ $member->name }}</div><div class="text-secondary-content">{{ $member->username }}</div></td>
                <td class="px-5 py-4 text-secondary-content">{{ $member->whatsapp_number }}</td>
                <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $member->is_active ? 'bg-success/10 text-success' : 'bg-base-200 text-base-content' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>@if($member->is_demo)<span class="ml-2 text-xs text-secondary-content">Demo</span>@endif</td>
                <td class="px-5 py-4">
                    @unless($member->is_demo)
                        <div class="flex flex-wrap items-center gap-3">
                            <a class="font-semibold text-primary hover:underline" href="{{ route('staff.edit', $member) }}">Edit</a>
                            @if($member->is_active)
                                <a class="font-semibold text-primary hover:underline" href="{{ route('staff.reset.confirm', $member) }}">Reset password</a>
                                <a class="font-semibold text-error hover:underline" href="{{ route('staff.deactivate.confirm', $member) }}">Deactivate</a>
                            @else
                                <a class="font-semibold text-success hover:underline" href="{{ route('staff.reactivate.confirm', $member) }}">Reactivate</a>
                            @endif
                        </div>
                    @else <span class="text-secondary-content">Protected</span> @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-5 py-10 text-center text-secondary-content">No staff accounts found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $staff->links() }}</div>
@endsection
