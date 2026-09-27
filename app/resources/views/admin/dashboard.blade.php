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
        <p class="text-sm font-semibold text-foreground">Admin workspace</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-foreground">Dashboard</h1>
        <p class="mt-2 text-muted-foreground">{{ $dateLabel }}</p>
    </div>
    <div class="flex gap-3">
        <x-ui.button href="{{ route('admin.reports.daily') }}" variant="outline">Open daily report</x-ui.button>
        <x-ui.button href="{{ route('staff.create') }}">Create Field Staff</x-ui.button>
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

    <!-- Today's delivery -->
    <section class="mt-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-foreground">Today's delivery</h2>
                <p class="mt-1 text-sm text-muted-foreground">All schools and items for today, sorted by status.</p>
            </div>
            @if(count($todayDeliveries) > 10)
                <a href="{{ route('admin.reports.daily') }}" class="text-sm font-medium text-primary hover:underline">View all in Daily report</a>
            @endif
        </div>
        <div class="mt-4 overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted text-left text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">School code</th>
                            <th class="px-4 py-3 font-medium">School name</th>
                            <th class="px-4 py-3 font-medium">Item</th>
                            <th class="px-4 py-3 text-right font-medium">Demand</th>
                            <th class="px-4 py-3 text-right font-medium">Delivered</th>
                            <th class="px-4 py-3 text-right font-medium">Shortfall</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(array_slice($todayDeliveries, 0, 10) as $row)
                            <tr class="border-t border-border">
                                <td class="px-4 py-3 font-medium">{{ $row['school_code'] }}</td>
                                <td class="px-4 py-3" lang="bn">{{ $row['school_name'] }}</td>
                                <td class="px-4 py-3">{{ $row['item_name'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['demand'] === null ? '—' : number_format($row['demand']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['delivered'] === null ? '—' : number_format($row['delivered']) }}</td>
                                <td class="px-4 py-3 text-right {{ $row['shortfall'] !== null && $row['shortfall'] > 0 ? 'font-semibold text-destructive' : '' }}">
                                    {{ $row['shortfall'] === null ? '—' : number_format($row['shortfall']) }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-ui.badge variant="{{ $row['variant'] }}">{{ $row['status'] }}</x-ui.badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-muted-foreground">No delivery data for today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(count($todayDeliveries) > 10)
                <div class="border-t border-border p-4 text-center">
                    <a href="{{ route('admin.reports.daily') }}" class="text-sm font-medium text-primary hover:underline">View all {{ count($todayDeliveries) }} rows in Daily report</a>
                </div>
            @endif
        </div>
    </section>
@endif
@endsection
