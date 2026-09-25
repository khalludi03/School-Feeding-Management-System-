<?php

namespace App\Http\Controllers;

use App\Models\FeedingCycle;
use App\Models\NonWorkingDay;
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

    public function store(Request $request, WorkingDayCalendar $calendar): RedirectResponse
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

        NonWorkingDay::query()->create([
            'holiday_on' => $data['holiday_on'],
            'kind' => $data['kind'],
            'name' => $data['name'],
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.calendar')->with(
            'status',
            Carbon::parse($data['holiday_on'])->format('j M Y').' marked as '.$data['kind'].'.',
        );
    }

    public function destroy(NonWorkingDay $nonWorkingDay): RedirectResponse
    {
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
