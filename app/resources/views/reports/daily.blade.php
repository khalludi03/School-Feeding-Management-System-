@extends('layouts.app')
@section('title', 'Daily Delivery Report')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4 print:hidden">
    <div>
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Report</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-charcoal">Daily Delivery Report</h1>
        <p class="mt-2 text-slate-gray">{{ $date->format('l, j F Y') }}<span class="mx-2 text-slate-300">·</span>{{ $report['cycle']?->title ?? 'No feeding cycle' }}</p>
    </div>
    <div class="flex flex-wrap items-end gap-2">
        <form method="GET" class="flex items-end gap-2">
            <div>
                <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                <input id="date" name="date" type="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}" class="mt-1 rounded-lg border border-slate-300 bg-white/80 px-3 py-2 outline-none transition focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100">
            </div>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Show</button>
        </form>
        <button onclick="window.print()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-100">Print</button>
        <a href="{{ route(auth()->user()->role === 'admin' ? 'admin.reports.daily.export' : 'field.report.export', ['date' => $date->toDateString()]) }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Export Excel</a>
    </div>
</div>

@if(! $report['is_working_day'])
    <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">This is not a delivery day, so no demand is generated and no entry is accepted.</p>
@endif

@if($report['is_future'] ?? false)
    <p class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">This date is in the future. Known allocations are shown as planned; no entries are missing yet.</p>
@endif

@if($report['unconfigured_items'] !== [])
    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        Supply weekdays are not configured for
        <strong>{{ implode(', ', array_map(fn($item) => $item->name, $report['unconfigured_items'])) }}</strong>,
        so their demand cannot be derived and is shown as unknown rather than zero.
    </p>
@endif

@if(! ($report['totals']['complete'] ?? true))
    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">This report is <strong>Incomplete</strong>. {{ $report['totals']['entries_missing'] }} expected entries are still missing.</p>
@endif

<div class="mt-6 grid gap-4 sm:grid-cols-4 print:grid-cols-4">
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Participating schools</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['totals']['schools'] }}</p>
    </div>
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Entries recorded</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['totals']['entries_recorded'] }}</p>
    </div>
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Entries missing</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['totals']['entries_missing'] }}</p>
    </div>
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Completeness</p>
        <p class="mt-1 text-2xl font-semibold">{{ ($report['totals']['complete'] ?? false) ? 'Complete' : 'Incomplete' }}</p>
    </div>
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-3 print:grid-cols-3">
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Shortage</p>
        <p class="mt-1 text-2xl font-semibold text-red-700">{{ number_format($report['totals']['total_shortage'] ?? 0) }}</p>
    </div>
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Excess</p>
        <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format($report['totals']['total_excess'] ?? 0) }}</p>
    </div>
    <div class="card-glass rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Net balance</p>
        <p class="mt-1 text-2xl font-semibold">{{ number_format($report['totals']['total_net_balance'] ?? 0) }}</p>
    </div>
</div>

<div class="mt-6 overflow-x-auto card-glass rounded-2xl">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-gray">
            <tr>
                <th rowspan="2" class="px-3 py-2">School</th>
                <th rowspan="2" class="px-3 py-2">Pupils</th>
                @foreach($report['items'] as $item)
                    <th colspan="4" class="border-l border-slate-200 px-3 py-2 text-center" lang="bn">{{ $item->name }}</th>
                @endforeach
            </tr>
            <tr class="text-[11px]">
                @foreach($report['items'] as $item)
                    <th class="border-l border-slate-200 px-3 py-1 font-medium normal-case text-slate-500">Demand</th>
                    <th class="px-3 py-1 font-medium normal-case text-slate-500">Delivered</th>
                    <th class="px-3 py-1 font-medium normal-case text-slate-500">Shortfall</th>
                    <th class="px-3 py-1 font-medium normal-case text-slate-500">Status</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr class="border-t border-slate-200">
                    <td class="px-3 py-2">
                        <span class="font-semibold">{{ $row['school']->code }}</span>
                        <span class="block text-slate-gray" lang="bn">{{ $row['school']->bangla_name }}</span>
                    </td>
                    <td class="px-3 py-2">{{ $row['pupil_count'] === null ? '—' : number_format($row['pupil_count']) }}</td>
                    @foreach($report['items'] as $item)
                        @php
                            $demand = $row['demand'][$item->item_key] ?? null;
                            $delivered = $row['delivered'][$item->item_key] ?? null;
                            $shortfall = $row['shortfall'][$item->item_key] ?? null;
                            $status = $row['status'][$item->item_key] ?? 'not_submitted';
                        @endphp
                        <td class="border-l border-slate-200 px-3 py-2">{{ $demand === null ? '—' : number_format($demand) }}</td>
                        <td class="px-3 py-2">{{ $delivered === null ? '—' : number_format($delivered) }}</td>
                        <td class="px-3 py-2 {{ $shortfall !== null && $shortfall > 0 ? 'font-semibold text-red-700' : ($shortfall !== null && $shortfall < 0 ? 'text-emerald-700' : '') }}">
                            {{ $shortfall === null ? '—' : number_format($shortfall) }}
                        </td>
                        <td class="px-3 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ match($status) {
                                'submitted' => 'bg-emerald-100 text-emerald-800',
                                'planned' => 'bg-indigo-100 text-indigo-800',
                                'confirmed_shortfall' => 'bg-rose-100 text-rose-800',
                                'not_scheduled' => 'bg-slate-100 text-slate-600',
                                'unknown_demand' => 'bg-amber-100 text-amber-800',
                                default => 'bg-red-100 text-red-800',
                            } }}">{{ match($status) {
                                'submitted' => 'Submitted',
                                'planned' => 'Planned',
                                'confirmed_shortfall' => 'Confirmed shortfall',
                                'not_scheduled' => 'Not scheduled',
                                'unknown_demand' => 'Unknown demand',
                                default => 'Not submitted',
                            } }}</span>
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 2 + 4 * count($report['items']) }}" class="px-3 py-6 text-center text-slate-gray">No active school is participating on this date.</td></tr>
            @endforelse
        </tbody>
        @if($report['rows'] !== [])
            <tfoot class="border-t-2 border-slate-300 bg-slate-50 font-semibold">
                <tr>
                    <td class="px-3 py-2">Upazila total</td>
                    <td class="px-3 py-2"></td>
                    @foreach($report['items'] as $item)
                        <td class="border-l border-slate-200 px-3 py-2">{{ number_format($report['totals']['demand'][$item->item_key] ?? 0) }}</td>
                        <td class="px-3 py-2">{{ number_format($report['totals']['delivered'][$item->item_key] ?? 0) }}</td>
                        <td class="px-3 py-2 {{ ($report['totals']['shortfall'][$item->item_key] ?? 0) > 0 ? 'text-red-700' : (($report['totals']['shortfall'][$item->item_key] ?? 0) < 0 ? 'text-emerald-700' : '') }}">{{ number_format($report['totals']['shortfall'][$item->item_key] ?? 0) }}</td>
                        <td class="px-3 py-2 text-xs">{{ ($report['totals']['entries_missing'] ?? 0) > 0 ? 'Incomplete' : 'Complete' }}</td>
                    @endforeach
                </tr>
                <tr class="bg-slate-100 text-xs">
                    <td class="px-3 py-2">Shortage / Excess / Net</td>
                    <td class="px-3 py-2"></td>
                    @foreach($report['items'] as $item)
                        <td colspan="4" class="border-l border-slate-200 px-3 py-2">
                            <span class="text-red-700">−{{ number_format($report['totals']['shortage'][$item->item_key] ?? 0) }}</span>
                            <span class="mx-1 text-slate-400">/</span>
                            <span class="text-emerald-700">+{{ number_format($report['totals']['excess'][$item->item_key] ?? 0) }}</span>
                            <span class="mx-1 text-slate-400">/</span>
                            <span>{{ number_format($report['totals']['net_balance'][$item->item_key] ?? 0) }}</span>
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<p class="mt-4 text-xs text-slate-500">Shortfall = demand − delivered. A dash means the figure cannot be derived, not that it is zero. Statuses follow the daily comparison rules.</p>
@endsection
