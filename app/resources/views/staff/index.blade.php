@extends('layouts.app')
@section('title', 'Staff accounts')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Dashboard</a><h1 class="mt-3 text-3xl font-bold tracking-tight">Staff accounts</h1><p class="mt-2 text-slate-600">Manage access without deleting delivery history.</p></div>
    <a href="{{ route('staff.create') }}" class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Create Field Staff</a>
</div>
<form action="{{ route('staff.index') }}" method="get" class="mt-8 flex gap-3">
    <input name="search" aria-label="Search staff" value="{{ $search }}" placeholder="Search name, username, or WhatsApp" class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
    <button class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold hover:bg-slate-100">Search</button>
</form>
<div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="px-5 py-4">Staff</th><th class="px-5 py-4">WhatsApp</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($staff as $member)
            <tr>
                <td class="px-5 py-4"><div class="font-semibold">{{ $member->name }}</div><div class="text-slate-500">{{ $member->username }}</div></td>
                <td class="px-5 py-4 text-slate-600">{{ $member->whatsapp_number }}</td>
                <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $member->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>@if($member->is_demo)<span class="ml-2 text-xs text-slate-500">Demo</span>@endif</td>
                <td class="px-5 py-4">
                    @unless($member->is_demo)
                        <div class="flex flex-wrap items-center gap-3">
                            <a class="font-semibold text-blue-700 hover:underline" href="{{ route('staff.edit', $member) }}">Edit</a>
                            @if($member->is_active)
                                <form method="post" action="{{ route('staff.reset', $member) }}">@csrf<button class="font-semibold text-blue-700 hover:underline">Reset password</button></form>
                                <form method="post" action="{{ route('staff.deactivate', $member) }}">@csrf<button class="font-semibold text-rose-700 hover:underline">Deactivate</button></form>
                            @else
                                <form method="post" action="{{ route('staff.reactivate', $member) }}">@csrf<button class="font-semibold text-emerald-700 hover:underline">Reactivate</button></form>
                            @endif
                        </div>
                    @else <span class="text-slate-500">Protected</span> @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No staff accounts found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $staff->links() }}</div>
@endsection
