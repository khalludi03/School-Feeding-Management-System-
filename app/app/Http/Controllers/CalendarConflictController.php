<?php

namespace App\Http\Controllers;

use App\Services\CalendarConflictService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarConflictController extends Controller
{
    public function show(Request $request, CalendarConflictService $conflictService): View
    {
        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $kind = (string) $request->query('kind', 'holiday');
        $name = (string) $request->query('name', '');

        $conflict = $conflictService->checkNonWorkingConflict($date);

        return view('calendar.conflicts', [
            'date' => $date,
            'kind' => $kind,
            'name' => $name,
            'conflicts' => $conflict['conflicts'],
            'blocked' => $conflict['blocked'],
        ]);
    }
}
