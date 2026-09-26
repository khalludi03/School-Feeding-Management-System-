@extends('layouts.app')
@section('title', 'Admin dashboard')
@section('content')
@php
    $isWorkingDay = $report['is_working_day'] ?? false;
    $totals = $report['totals'] ?? [];
    $hasUnknown = collect($totals['demand_unknown_schools'] ?? [])->sum() > 0;
    $dateLabel = $today->format('l, d F Y');
@endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-sm font-semibold" style="color:#0f172a">Admin workspace</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight" style="color:#0f172a">Dashboard</h1>
        <p class="mt-2" style="color:#475569">{{ $dateLabel }}</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.reports.daily') }}" class="btn btn-outline btn-primary">Open daily report</a>
        <a href="{{ route('staff.create') }}" class="btn btn-primary">Create Field Staff</a>
    </div>
</div>

@if (! $isWorkingDay)
    <div role="alert" class="alert alert-info mt-6">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
        <div>
            <h3 class="font-semibold">No scheduled demand today</h3>
            <p class="text-sm opacity-80">{{ $today->isFuture() ? 'This date is in the future.' : 'Today is a non-working day (holiday or weekly off).' }} Supply entries are not expected and no school is counted as missing.</p>
        </div>
    </div>
@endif

@if ($isWorkingDay)
    <section class="mt-8">
        <h2 class="text-xl font-semibold text-base-content">Today’s upazila totals</h2>
        <p class="mt-1 text-sm text-secondary-content">Demand vs delivered per item, with confirmed shortfalls and excess.</p>
        <div class="mt-4 grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($report['items'] as $item)
                @php
                    $key = $item->item_key;
                    $demand = (int) ($totals['demand'][$key] ?? 0);
                    $delivered = (int) ($totals['delivered'][$key] ?? 0);
                    $shortage = (int) ($totals['shortage'][$key] ?? 0);
                    $excess = (int) ($totals['excess'][$key] ?? 0);
                    $confirmed = (int) ($totals['confirmed_shortfall'][$key] ?? 0);
                    $unit = $item->unit_label ?? 'units';
                @endphp
                <div class="card bg-base-100 shadow">
                    <div class="card-body gap-2 p-4">
                        <div class="text-xs font-semibold text-primary">{{ $item->name }}</div>
                        <div class="text-2xl font-bold">{{ number_format($demand) }}</div>
                        <div class="text-xs text-secondary-content">Demand ({{ $unit }})</div>
                        <div class="divider my-1"></div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <div class="text-xs text-secondary-content">Supplied</div>
                                <div class="text-lg font-semibold text-success">{{ number_format($delivered) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-secondary-content">Shortfall</div>
                                <div class="text-lg font-semibold {{ $confirmed > 0 ? 'text-error' : 'text-base-content' }}">{{ number_format($confirmed) }}</div>
                            </div>
                        </div>
                        @if ($excess > 0)
                            <div class="text-xs text-warning">Excess: +{{ number_format($excess) }} {{ $unit }}</div>
                        @elseif ($confirmed > 0)
                            <div class="text-xs text-error">Shortfall: {{ number_format($confirmed) }} {{ $unit }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="card bg-base-100 shadow lg:col-span-1">
            <div class="card-body">
                <h3 class="card-title">Completion</h3>
                <p class="text-sm text-secondary-content">Schools that recorded at least one entry today.</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold">{{ $completion['submitted'] }}</span>
                    <span class="text-secondary-content">of {{ $completion['expected'] }} schools</span>
                </div>
                <div class="radial-progress text-primary mt-2" style="--value:{{ $completion['expected'] > 0 ? (int) round(($completion['submitted'] / $completion['expected']) * 100) : 100 }}; --size:6rem; --thickness:0.6rem;" role="progressbar">{{ $completion['expected'] > 0 ? (int) round(($completion['submitted'] / $completion['expected']) * 100) : 100 }}%</div>
                @if ($completion['missing'] > 0)
                    <div class="alert alert-warning mt-3 text-sm">
                        {{ $completion['missing'] }} school{{ $completion['missing'] === 1 ? '' : 's' }} still missing expected entries.
                    </div>
                @elseif ($hasUnknown)
                    <div class="alert alert-warning mt-3 text-sm">Some schools have unknown demand — check the daily report.</div>
                @else
                    <div class="alert alert-success mt-3 text-sm">All expected schools have submitted their entries for today.</div>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 shadow lg:col-span-2">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h3 class="card-title">Confirmed shortfalls</h3>
                    <span class="badge badge-error">{{ count($confirmedShortfalls) }}</span>
                </div>
                <p class="text-sm text-secondary-content">Schools where the submitted quantity is below today’s demand.</p>
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr><th>School</th><th>Item</th><th class="text-right">Demand</th><th class="text-right">Delivered</th><th class="text-right">Shortfall</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($confirmedShortfalls as $row)
                                <tr>
                                    <td><div class="font-medium">{{ $row['school_name'] }}</div><div class="text-xs text-secondary-content">{{ $row['school_code'] }}</div></td>
                                    <td>{{ $row['item_name'] }}</td>
                                    <td class="text-right">{{ number_format($row['demand']) }}</td>
                                    <td class="text-right">{{ number_format($row['delivered']) }}</td>
                                    <td class="text-right text-error font-semibold">{{ number_format($row['shortfall']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary-content py-6">No confirmed shortfalls today.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-8">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h3 class="card-title">Missing submissions</h3>
                    <span class="badge badge-warning">{{ count($missingSubmissions) }}</span>
                </div>
                <p class="text-sm text-secondary-content">Schools that have not recorded today’s expected items.</p>
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr><th>School</th><th>Missing items</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($missingSubmissions as $row)
                                <tr>
                                    <td><div class="font-medium">{{ $row['school_name'] }}</div><div class="text-xs text-secondary-content">{{ $row['school_code'] }}</div></td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($row['items'] as $item)
                                                <span class="badge badge-outline">{{ $item['item_name'] }} · {{ number_format($item['demand']) }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-secondary-content py-6">All expected schools have submitted.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endif
@endsection
