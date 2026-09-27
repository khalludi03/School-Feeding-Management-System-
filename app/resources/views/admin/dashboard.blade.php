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

    <div
        id="dashboard-root"
        data-dashboard="{{ json_encode([
            'totalDemand' => $totalDemand,
            'totalAllocated' => $totalAllocated,
            'totalShortfall' => $totalShortfall,
            'confirmedSchoolsCount' => $confirmedSchoolsCount,
            'pendingCount' => $pendingCount,
            'totalSchools' => $totalSchools,
            'itemCount' => collect($report['items'])->count(),
            'shortfallPct' => $pct,
            'confirmedShortfalls' => $confirmedShortfalls,
            'missingSubmissions' => $missingSubmissions,
        ], JSON_HEX_APOS) }}"
    ></div>

    <script>
        window.__DASHBOARD_DATA__ = JSON.parse(
            document.getElementById('dashboard-root').dataset.dashboard
        );
    </script>
    @vite('resources/js/dashboard.tsx')
@endif
@endsection
