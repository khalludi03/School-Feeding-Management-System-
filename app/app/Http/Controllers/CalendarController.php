<?php

namespace App\Http\Controllers;

use App\Models\DateItemSchedule;
use App\Models\FeedingCycle;
use App\Models\NonWorkingDay;
use App\Services\CalendarConflictService;
use App\Services\DateItemScheduleService;
use App\Services\ItemSupplyPattern;
use App\Services\WorkingDayCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request, WorkingDayCalendar $calendar): View
    {
        $cycle = $this->currentCycle();
        $month = $this->requestedMonth($request, $cycle);

        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $marked = NonWorkingDay::query()
            ->whereBetween('holiday_on', [$from->toDateString(), $to->toDateString()])
            ->orderBy('holiday_on')
            ->get()
            ->keyBy(fn (NonWorkingDay $day): string => $day->holiday_on->toDateString());

        $days = [];
        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $days[] = [
                'date' => $date->copy(),
                'marked' => $marked->get($date->toDateString()),
            ];
        }

        return view('calendar.index', [
            'cycle' => $cycle,
            'month' => $month,
            'days' => $days,
            'workingDays' => $calendar->workingDaysBetween($from, $to),
            'totalDays' => $from->daysInMonth,
        ]);
    }

    public function monthConfig(Request $request, FeedingCycle $cycle, DateItemScheduleService $dateSchedules, ItemSupplyPattern $patterns): View
    {
        $month = $this->requestedMonth($request, $cycle);
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $marked = NonWorkingDay::query()
            ->whereBetween('holiday_on', [$from->toDateString(), $to->toDateString()])
            ->orderBy('holiday_on')
            ->get()
            ->keyBy(fn (NonWorkingDay $day): string => $day->holiday_on->toDateString());

        $items = $cycle->items()->orderBy('sort_order')->get();
        $configs = $dateSchedules->monthConfigs($cycle, $month)->groupBy('schedule_date');

        $days = [];
        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $days[] = [
                'date' => $date->copy(),
                'marked' => $marked->get($date->toDateString()),
                'items' => $items->mapWithKeys(fn ($item) => [
                    $item->id => [
                        'item' => $item,
                        'scheduled' => $configs->get($date->toDateString())?->firstWhere('feeding_item_id', $item->id)?->is_scheduled ?? $patterns->isSuppliedOn($item, $date) ?? false,
                        'source' => $configs->get($date->toDateString())?->firstWhere('feeding_item_id', $item->id)?->source ?? ($patterns->isSuppliedOn($item, $date) !== null ? 'work_order' : 'assumed'),
                    ],
                ]),
                'setup_incomplete' => $items->contains(fn ($item) => $patterns->isSuppliedOn($item, $date) === null),
            ];
        }

        return view('calendar.month-config', [
            'cycle' => $cycle,
            'month' => $month,
            'days' => $days,
            'items' => $items,
            'totalDays' => $from->daysInMonth,
        ]);
    }

    public function updateMonthConfig(Request $request, FeedingCycle $cycle, DateItemScheduleService $dateSchedules): RedirectResponse
    {
        $month = $this->requestedMonth($request, $cycle);
        $items = $cycle->items()->orderBy('sort_order')->get();

        $requested = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'items' => ['required', 'array'],
            'items.*' => ['string', 'regex:/^\d+-\d{4}-\d{2}-\d{2}$/'],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $requested['month'].'-01')->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $allowedItems = $items->pluck('id')->all();
        $dateItems = [];

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            $dateStr = $date->toDateString();
            foreach ($allowedItems as $itemId) {
                $key = $itemId.'-'.$dateStr;
                $checked = in_array($key, $requested['items'], true);

                $dateItems[] = [
                    'feeding_item_id' => (int) $itemId,
                    'is_scheduled' => $checked,
                    'schedule_date' => $dateStr,
                ];
            }
        }

        $dateSchedules->upsertMonth($cycle, $from, $dateItems, DateItemSchedule::SOURCE_WORK_ORDER, $request->user()->id);

        return redirect()->route('admin.calendar.month-config', ['cycle' => $cycle, 'month' => $from->format('Y-m')])
            ->with('status', 'Month configuration saved.');
    }

    public function store(Request $request, WorkingDayCalendar $calendar, CalendarConflictService $conflictService): RedirectResponse|View
    {
        $data = $request->validate([
            'holiday_on' => [
                'required',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    if (NonWorkingDay::query()->whereDate('holiday_on', $value)->exists()) {
                        $fail('That date is already marked as a non-working day.');
                    }
                },
            ],
            'kind' => ['required', Rule::in([NonWorkingDay::KIND_HOLIDAY, NonWorkingDay::KIND_WEEKLY_OFF])],
            'name' => ['required', 'string', 'max:160'],
        ]);

        $holidayDate = Carbon::parse($data['holiday_on']);
        $conflict = $conflictService->checkNonWorkingConflict($holidayDate);

        if ($conflict['blocked']) {
            return view('calendar.conflicts', [
                'date' => $holidayDate,
                'kind' => $data['kind'],
                'name' => $data['name'],
                'conflicts' => $conflict['conflicts'],
            ]);
        }

        NonWorkingDay::query()->create([
            'holiday_on' => $data['holiday_on'],
            'kind' => $data['kind'],
            'name' => $data['name'],
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.calendar')->with(
            'status',
            $holidayDate->format('j M Y').' marked as '.$data['kind'].'.',
        );
    }

    public function destroy(NonWorkingDay $nonWorkingDay, DateItemScheduleService $dateSchedules): RedirectResponse
    {
        $holidayDate = $nonWorkingDay->holiday_on;
        $cycle = $this->currentCycle();

        if ($cycle !== null) {
            $dateSchedules->restoreFromWeekdayPattern($cycle, $holidayDate, request()->user()->id);
        }

        $nonWorkingDay->delete();

        return redirect()->route('admin.calendar')->with('status', 'Date returned to working days.');
    }

    private function requestedMonth(Request $request, ?FeedingCycle $cycle): Carbon
    {
        $raw = $request->query('month');

        if (is_string($raw) && Carbon::hasFormat($raw, 'Y-m')) {
            return Carbon::createFromFormat('Y-m-d', $raw.'-01')->startOfMonth();
        }

        return $cycle?->starts_on->copy()->startOfMonth() ?? Carbon::today()->startOfMonth();
    }

    private function currentCycle(): ?FeedingCycle
    {
        return FeedingCycle::query()->orderByDesc('id')->first();
    }
}
