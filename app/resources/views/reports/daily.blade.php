@extends('layouts.app')
@section('title', 'Daily Delivery Report')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4 print:hidden">
    <div>
        <p class="text-sm font-semibold text-muted-foreground">Report</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-foreground">Daily Delivery Report</h1>
        <p class="mt-2 text-muted-foreground">{{ $date->format('l, j F Y') }}<span class="mx-2 text-base-300">·</span>{{ $report['cycle']?->title ?? 'No feeding cycle' }}</p>
    </div>
    <div class="flex flex-wrap items-end gap-2">
        <form method="GET" class="flex items-end gap-2">
            <div>
                <label for="date" class="block text-xs font-semibold text-muted-foreground">Date</label>
                <input id="date" name="date" type="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}" class="mt-1 rounded-lg border border-border bg-card/80 px-3 py-2 outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/20">
            </div>
            <x-ui.button size="sm">Show</x-ui.button>
        </form>
        <button onclick="window.print()" class="rounded-lg border border-border px-4 py-2 text-sm font-semibold hover:bg-muted">Print</button>
        <x-ui.button href="{{ route(auth()->user()->role === 'admin' ? 'admin.reports.daily.export' : 'field.report.export', ['date' => $date->toDateString()]) }}" size="sm">Export Excel</x-ui.button>
    </div>
</div>

@if(! $report['is_working_day'])
    <p class="mt-6 rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">This is not a delivery day, so no demand is generated and no entry is accepted.</p>
@endif

@if($report['is_future'] ?? false)
    <p class="mt-4 rounded-xl border border-primary/30 bg-primary/10 p-4 text-sm text-primary">This date is in the future. Known allocations are shown as planned; no entries are missing yet.</p>
@endif

@if($report['unconfigured_items'] !== [])
    <p class="mt-4 rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">
        Supply weekdays are not configured for
        <strong>{{ implode(', ', array_map(fn($item) => $item->name, $report['unconfigured_items'])) }}</strong>,
        so their demand cannot be derived and is shown as unknown rather than zero.
    </p>
@endif

@if(! ($report['totals']['complete'] ?? true))
    <p class="mt-4 rounded-xl border border-warning bg-warning/10 p-4 text-sm text-warning">This report is <strong>Incomplete</strong>. {{ $report['totals']['entries_missing'] }} expected entries are still missing.</p>
@endif

<div class="mt-6 grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 print:grid-cols-5">
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold text-muted-foreground">Participating schools</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['totals']['schools'] }}</p>
    </div>
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold text-muted-foreground">Entries recorded</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['totals']['entries_recorded'] }}</p>
    </div>
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold text-muted-foreground">Entries missing</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['totals']['entries_missing'] }}</p>
    </div>
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold text-muted-foreground">Demand not set</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['totals']['unknown_demand_schools'] }}</p>
    </div>
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold text-muted-foreground">Completeness</p>
        <p class="mt-1 text-2xl font-semibold">{{ ($report['totals']['complete'] ?? false) ? 'Complete' : 'Incomplete' }}</p>
    </div>
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-2 print:grid-cols-2">
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold text-muted-foreground">Shortage</p>
        <p class="mt-1 text-2xl font-semibold text-destructive">{{ number_format($report['totals']['total_shortage'] ?? 0) }}</p>
    </div>
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold text-muted-foreground">Excess</p>
        <p class="mt-1 text-2xl font-semibold text-success">{{ number_format($report['totals']['total_excess'] ?? 0) }}</p>
    </div>
</div>

<div class="mt-6 overflow-x-auto card-glass rounded-2xl">
    <table class="min-w-full text-sm">
        <thead class="bg-muted text-left text-xs text-muted-foreground">
            <tr>
                <th rowspan="2" class="px-3 py-2">School</th>
                <th rowspan="2" class="px-3 py-2">Pupils</th>
                @foreach($report['items'] as $item)
                    <th colspan="4" class="border-l border-border px-3 py-2 text-center" lang="bn">{{ $item->name }}</th>
                @endforeach
            </tr>
            <tr class="text-[11px]">
                @foreach($report['items'] as $item)
                    <th class="border-l border-border px-3 py-1 font-medium normal-case text-muted-foreground">Demand</th>
                    <th class="px-3 py-1 font-medium normal-case text-muted-foreground">Delivered</th>
                    <th class="px-3 py-1 font-medium normal-case text-muted-foreground">Shortfall</th>
                    <th class="px-3 py-1 font-medium normal-case text-muted-foreground">Status</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr class="border-t border-border">
                    <td class="px-3 py-2">
                        <span class="font-semibold">{{ $row['school']->code }}</span>
                        <span class="block text-muted-foreground" lang="bn">{{ $row['school']->bangla_name }}</span>
                    </td>
                    <td class="px-3 py-2">{{ $row['pupil_count'] === null ? '—' : number_format($row['pupil_count']) }}</td>
                    @foreach($report['items'] as $item)
                        @php
                            $demand = $row['demand'][$item->item_key] ?? null;
                            $delivered = $row['delivered'][$item->item_key] ?? null;
                            $shortfall = $row['shortfall'][$item->item_key] ?? null;
                            $status = $row['status'][$item->item_key] ?? 'not_submitted';
                        @endphp
                        <td class="border-l border-border px-3 py-2">{{ $demand === null ? '—' : number_format($demand) }}</td>
                        <td class="px-3 py-2">{{ $delivered === null ? '—' : number_format($delivered) }}</td>
                        <td class="px-3 py-2 {{ $shortfall !== null && $shortfall > 0 ? 'font-semibold text-destructive' : ($shortfall !== null && $shortfall < 0 ? 'text-success' : '') }}">
                            {{ $shortfall === null ? '—' : number_format($shortfall) }}
                        </td>
                        <td class="px-3 py-2">
                            <x-ui.badge variant="{{ match($status) {
                                'submitted' => 'success',
                                'planned' => 'default',
                                'confirmed_shortfall' => 'destructive',
                                'not_scheduled' => 'neutral',
                                'unknown_demand' => 'warning',
                                default => 'destructive',
                            } }}">{{ match($status) {
                                'submitted' => 'Submitted',
                                'planned' => 'Planned',
                                'confirmed_shortfall' => 'Confirmed shortfall',
                                'not_scheduled' => 'Not scheduled',
                                'unknown_demand' => 'Unknown demand',
                                default => 'Not submitted',
                            } }}</x-ui.badge>
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 2 + 4 * count($report['items']) }}" class="px-3 py-6 text-center text-muted-foreground">No active school is participating on this date.</td></tr>
            @endforelse
        </tbody>
        @if($report['rows'] !== [])
            <tfoot class="border-t-2 border-border bg-muted font-semibold">
                <tr>
                    <td class="px-3 py-2">Upazila total</td>
                    <td class="px-3 py-2"></td>
                    @foreach($report['items'] as $item)
                        <td class="border-l border-border px-3 py-2">{{ number_format($report['totals']['demand'][$item->item_key] ?? 0) }}</td>
                        <td class="px-3 py-2">{{ number_format($report['totals']['delivered'][$item->item_key] ?? 0) }}</td>
                        <td class="px-3 py-2 {{ ($report['totals']['shortfall'][$item->item_key] ?? 0) > 0 ? 'text-destructive' : (($report['totals']['shortfall'][$item->item_key] ?? 0) < 0 ? 'text-success' : '') }}">{{ number_format($report['totals']['shortfall'][$item->item_key] ?? 0) }}</td>
                        <td class="px-3 py-2 text-xs">{{ ($report['totals']['entries_missing'] ?? 0) > 0 ? 'Incomplete' : 'Complete' }}</td>
                    @endforeach
                </tr>
                <tr class="bg-muted text-xs">
                    <td class="px-3 py-2">Shortage / Excess</td>
                    <td class="px-3 py-2"></td>
                    @foreach($report['items'] as $item)
                        <td colspan="4" class="border-l border-border px-3 py-2">
                            <span class="text-destructive">−{{ number_format($report['totals']['shortage'][$item->item_key] ?? 0) }}</span>
                            <span class="mx-1 text-muted-foreground/70">/</span>
                            <span class="text-success">+{{ number_format($report['totals']['excess'][$item->item_key] ?? 0) }}</span>
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</div>

 <p class="mt-4 text-xs text-muted-foreground">Shortfall = demand − delivered. A dash means the figure cannot be derived, not that it is zero. Statuses follow the daily comparison rules.</p>
@endsection
