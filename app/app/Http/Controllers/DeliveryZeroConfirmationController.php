<?php

namespace App\Http\Controllers;

use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Services\DeliveryZeroConfirmationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryZeroConfirmationController extends Controller
{
    public function create(Request $request): View
    {
        $date = CarbonImmutable::parse($request->input('date', today()->toDateString()));
        $cycle = FeedingCycle::query()
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->orderByDesc('id')
            ->first();

        return view('deliveries.zero-confirmation', [
            'date' => $date,
            'cycle' => $cycle,
            'schools' => School::query()->orderBy('code')->get(['id', 'code', 'bangla_name']),
            'items' => $cycle?->items()->orderBy('sort_order')->get() ?? collect(),
        ]);
    }

    public function store(Request $request, DeliveryZeroConfirmationService $service): RedirectResponse
    {
        $data = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'feeding_item_id' => ['required', 'integer', 'exists:feeding_items,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $school = School::query()->findOrFail($data['school_id']);
        $item = FeedingItem::query()->findOrFail($data['feeding_item_id']);
        $date = CarbonImmutable::parse($data['date']);

        $service->confirm($school, $item, $date, $data['reason'], $request->user());

        return redirect()->route('field.report', ['date' => $date->toDateString()])
            ->with('status', 'Zero delivery confirmed for '.$school->code.' on '.$date->format('j M Y').'.');
    }
}
