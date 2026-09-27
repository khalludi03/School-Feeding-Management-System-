@extends('layouts.app')
@section('title', 'Admin dashboard')
@section('content')
@php
    $isWorkingDay = $report['is_working_day'] ?? false;
    $totals = $report['totals'] ?? [];
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
    @php
        $totalDemand = 0;
        $totalAllocated = 0;
        $totalShortfall = 0;
        foreach (collect($report['items']) as $item) {
            $key = $item->item_key;
            $totalDemand += (int) ($totals['demand'][$key] ?? 0);
            $totalAllocated += (int) ($totals['delivered'][$key] ?? 0);
            $totalShortfall += (int) ($totals['confirmed_shortfall'][$key] ?? 0);
        }
        $pct = $totalDemand > 0 ? (int) round($totalAllocated / $totalDemand * 100) : 0;
        $pendingCount = $completion['missing'];
        $totalSchools = $completion['expected'];
        $confirmedSchoolsCount = $totalSchools - $pendingCount - count($confirmedShortfalls);
    @endphp

    <section class="mt-8">
        <h2 class="text-xl font-semibold text-base-content">Today's summary</h2>
        <p class="mt-1 text-sm text-slate-500">Combined across all items and schools.</p>
        <div class="mt-4 grid gap-4 grid-cols-2 lg:grid-cols-4">

            <div class="card bg-base-100 shadow">
                <div class="card-body gap-1 p-4">
                    <div class="label text-sm font-semibold">Today's demand</div>
                    <div class="text-3xl font-bold tabular-nums" style="color:#0F172A">{{ number_format($totalDemand) }}</div>
                    <div class="label text-xs">{{ collect($report['items'])->count() }} items · {{ $totalSchools }} schools</div>
                </div>
            </div>

            <div class="card bg-base-100 shadow">
                <div class="card-body gap-1 p-4">
                    <div class="label text-sm font-semibold">Allocated</div>
                    <div class="text-3xl font-bold tabular-nums" style="color:#16A34A">{{ number_format($totalAllocated) }}</div>
                    <div class="label text-xs">{{ $pct }}% of today's demand</div>
                </div>
            </div>

            <div class="card bg-base-100 shadow">
                <div class="card-body gap-1 p-4">
                    <div class="label text-sm font-semibold">Shortfall</div>
                    <div class="text-3xl font-bold tabular-nums" style="color:#DC2626">{{ number_format($totalShortfall) }}</div>
                    <div class="label text-xs">Confirmed across {{ $confirmedSchoolsCount }} schools</div>
                </div>
            </div>

            <a href="#missing-submissions" class="card bg-base-100 shadow cursor-pointer no-underline hover:shadow-md transition-shadow">
                <div class="card-body gap-1 p-4">
                    <div class="label text-sm font-semibold">Pending submissions</div>
                    <div class="text-3xl font-bold tabular-nums" style="color:#D97706">{{ number_format($pendingCount) }}</div>
                    <div class="label text-xs">of {{ $totalSchools }} schools</div>
                </div>
            </a>

        </div>
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h3 class="card-title">Confirmed shortfalls</h3>
                    <span class="badge badge-error">{{ count($confirmedShortfalls) }}</span>
                </div>
                <p class="text-sm text-slate-500">Schools where the submitted quantity is below today's demand.</p>
                <div class="overflow-x-auto mt-2">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr><th>School</th><th>Item</th><th class="text-right">Demand</th><th class="text-right">Delivered</th><th class="text-right">Shortfall</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($confirmedShortfalls as $row)
                                <tr>
                                    <td><div class="font-medium">{{ $row['school_name'] }}</div><div class="text-xs text-slate-500">{{ $row['school_code'] }}</div></td>
                                    <td>{{ $row['item_name'] }}</td>
                                    <td class="text-right">{{ number_format($row['demand']) }}</td>
                                    <td class="text-right">{{ number_format($row['delivered']) }}</td>
                                    <td class="text-right text-error font-semibold">{{ number_format($row['shortfall']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-slate-500 py-6">No confirmed shortfalls today.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="flex items-center justify-between" id="missing-submissions">
                    <h3 class="card-title">Missing submissions</h3>
                    <span class="badge badge-warning">{{ count($missingSubmissions) }}</span>
                </div>
                <p class="text-sm text-slate-500">Schools that have not recorded today's expected items.</p>
                <div class="overflow-x-auto mt-2">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr><th>School</th><th>Missing items</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($missingSubmissions as $row)
                                <tr>
                                    <td><div class="font-medium">{{ $row['school_name'] }}</div><div class="text-xs text-slate-500">{{ $row['school_code'] }}</div></td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($row['items'] as $item)
                                                <span class="badge badge-outline">{{ $item['item_name'] }} · {{ number_format($item['demand']) }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-slate-500 py-6">All expected schools have submitted.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endif
@endsection
