<?php

namespace App\Http\Controllers;

use App\Models\FeedingCycle;
use App\Models\School;
use App\Services\DailyDemandService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DemandExplanationController extends Controller
{
    public function show(Request $request, School $school, DailyDemandService $demandExplainer): View|RedirectResponse
    {
        $user = Auth::user();
        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $cycle = FeedingCycle::query()
            ->whereHas('schoolPlanningSnapshots', function ($query) use ($school) {
                $query->where('school_id', $school->id);
            })
            ->openOn($date)
            ->first();

        if ($cycle === null) {
            return view('demand.explanation', [
                'school' => $school,
                'date' => $date,
                'cycle' => null,
                'explanation' => null,
                'error' => 'No open feeding cycle covers this date.',
            ]);
        }

        $explanation = $demandExplainer->explainForSchool($cycle, $school, $date);

        return view('demand.explanation', [
            'school' => $school,
            'date' => $date,
            'cycle' => $cycle,
            'explanation' => $explanation,
        ]);
    }
}
