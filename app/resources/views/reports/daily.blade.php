@extends('layouts.app')
@section('title', 'Daily Delivery Report')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4 print:hidden">
    <div>
        <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Report</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Daily Delivery Report</h1>
        <p class="mt-2 text-slate-600">{{ $date->format('l, j F Y') }}<span class="mx-2 text-slate-300">·</span>{{ $report['cycle']?->title ?? 'No feeding cycle' }}</p>
    </div>
    <div class="flex flex-wrap items-end gap-2">
        <form method="GET" class="flex items-end gap-2">
            <div>
                <label for="date" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Date</label>
                <input id="date" name="date" type="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}" class="mt-1 rounded-lg border-slate-300">
            </div>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Show</button>
        </form>
        <button onclick="window.print()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Print</button>
        <a href="{{ route(auth()->user()->role === 'admin' ? 'admin.reports.daily.export' : 'field.report.export', ['date' => $date->toDateString()]) }}"
           class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">Export Excel</a>
    </div>
</div>

@if(! $report['is_working_day'])
    <p class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">This is not a delivery day, so no demand is generated and no entry is accepted.</p>
@endif

@if($report['unconfigured_items'] !== [])
    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        Supply weekdays are not configured for
        <strong>{{ implode(', ', array_map(fn($item) => $item->name, $report['unconfigured_items'])) }}</strong>,
        so their demand cannot be derived and is shown as unknown rather than zero.
    </p>
@endif

@if($report['totals']['complete'] === false)
    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Upazila totals below count only schools with a derivable demand and are therefore understated.</p>
@endif

<div class="mt-6 grid gap-4 sm:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Participating schools</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['totals']['schools'] }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Entries recorded</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['totals']['entries_recorded'] }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Entries missing</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['totals']['entries_missing'] }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Delivery day</p>
        <p class="mt-1 text-2xl font-semibold">{{ $report['is_working_day'] ? 'Yes' : 'No' }}</p>
    </div>
</div>

<div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
                <th rowspan="2" class="px-3 py-2">School</th>
                <th rowspan="2" class="px-3 py-2">Pupils</th>
                <th rowspan="2" class="px-3 py-2">Entry</th>
                @foreach($report['items'] as $item)
                    <th colspan="3" class="border-l border-slate-200 px-3 py-2 text-center" lang="bn">{{ $item->name }}</th>
                @endforeach
            </tr>
            <tr class="text-[11px]">
                @foreach($report['items'] as $item)
                    <th class="border-l border-slate-200 px-3 py-1 font-medium normal-case text-slate-500">Demand</th>
                    <th class="px-3 py-1 font-medium normal-case text-slate-500">Delivered</th>
                    <th class="px-3 py-1 font-medium normal-case text-slate-500">Shortfall</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr class="border-t border-slate-200">
                    <td class="px-3 py-2">
                        <span class="font-semibold">{{ $row['school']->code }}</span>
                        <span class="block text-slate-600" lang="bn">{{ $row['school']->bangla_name }}</span>
                    </td>
                    <td class="px-3 py-2">{{ $row['pupil_count'] === null ? '—' : number_format($row['pupil_count']) }}</td>
                    <td class="px-3 py-2">{{ $row['entry_recorded'] ? 'Yes' : 'No' }}</td>
                    @foreach($report['items'] as $item)
                        @php
                            $demand = $row['demand'][$item->item_key] ?? null;
                            $delivered = $row['delivered'][$item->item_key] ?? null;
                            $shortfall = $row['shortfall'][$item->item_key] ?? null;
                        @endphp
                        <td class="border-l border-slate-200 px-3 py-2">{{ $demand === null ? '—' : number_format($demand) }}</td>
                        <td class="px-3 py-2">{{ $delivered === null ? '—' : number_format($delivered) }}</td>
                        <td class="px-3 py-2 {{ $shortfall !== null && $shortfall > 0 ? 'font-semibold text-red-700' : ($shortfall !== null && $shortfall < 0 ? 'text-emerald-700' : '') }}">
                            {{ $shortfall === null ? '—' : number_format($shortfall) }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 3 + 3 * count($report['items']) }}" class="px-3 py-6 text-center text-slate-600">No active school is participating on this date.</td></tr>
            @endforelse
        </tbody>
        @if($report['rows'] !== [])
            <tfoot class="border-t-2 border-slate-300 bg-slate-50 font-semibold">
                <tr>
                    <td class="px-3 py-2">Upazila total</td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2">{{ $report['totals']['entries_recorded'] }}/{{ $report['totals']['schools'] }}</td>
                    @foreach($report['items'] as $item)
                        <td class="border-l border-slate-200 px-3 py-2">{{ number_format($report['totals']['demand'][$item->item_key] ?? 0) }}</td>
                        <td class="px-3 py-2">{{ number_format($report['totals']['delivered'][$item->item_key] ?? 0) }}</td>
                        <td class="px-3 py-2">{{ number_format($report['totals']['shortfall'][$item->item_key] ?? 0) }}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</div>

<p class="mt-4 text-xs text-slate-500">Shortfall = demand − delivered. A dash means the figure cannot be derived, not that it is zero.</p>
@endsection
