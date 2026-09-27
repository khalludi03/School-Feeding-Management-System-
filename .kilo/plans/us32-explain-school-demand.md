# US3.2 - Explain a School’s Demand Implementation Plan

## Goal
Provide Admin and Field Staff a date-specific, step-by-step explanation of how a school’s demand was derived: enrolment basis, 90% target, ration, item schedule, and explicit zero/unknown reasons.

## Scope
- Read-only explanation page
- No changes to demand calculation itself
- No new database tables

## Dependencies
- US3.1 per-item dated rations must already exist
- Existing `DailyDemandService`, `EnrolmentProjectionService`, `ItemSupplyPattern`, `WorkingDayCalendar`

## Implementation

### 1. Service Layer
**File:** `app/Services/DailyDemandService.php`
Add a single new method:
```php
public function explainForSchool(FeedingCycle $cycle, School $school, CarbonInterface $date): array
```
Return shape:
```php
[
    'date' => string,
    'cycle_title' => string,
    'school_code' => string,
    'school_name' => string,
    'is_working_day' => bool,
    'working_day_reason' => ?string,
    'is_participating' => bool,
    'participation_reason' => ?string,
    'pupil_count' => ?int,
    'pupil_count_effective_on' => ?string,
    'pupil_count_source' => ?string,
    'ration_factor' => ?float,
    'ration_source' => ?string, // 'item_dated' | 'cycle_fallback' | null
    'ration_effective_on' => ?string,
    'daily_demand' => ?int,
    'items' => array<[
        'item_key', 'name', 'unit', 'supply_days',
        'supplied_on_date', 'demand', 'zero_reason', 'unknown_reason'
    ]>,
    'setup_incomplete' => bool,
    'setup_incomplete_reasons' => list<string>,
]
```

### 2. Controller
**File:** `app/Http/Controllers/DemandExplanationController.php`
```php
class DemandExplanationController extends Controller
{
    public function show(Request $request, School $school): View|RedirectResponse
    {
        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $cycle = app(CycleDemandService::class)->openCycleFor($date);

        if ($cycle === null) {
            return view('demand.explanation', [
                'school' => $school,
                'date' => $date,
                'error' => 'No open feeding cycle covers this date.',
            ]);
        }

        $explanation = app(DailyDemandService::class)->explainForSchool($cycle, $school, $date);

        return view('demand.explanation', [
            'school' => $school,
            'date' => $date,
            'cycle' => $cycle,
            'explanation' => $explanation,
        ]);
    }
}
```

### 3. Routes
```php
// Admin
Route::get('/schools/{school}/demand', [DemandExplanationController::class, 'show'])
    ->name('schools.demand.explain');
// Field Staff
Route::get('/schools/{school}/demand', [DemandExplanationController::class, 'show'])
    ->name('field.demand.explain');
```

### 4. View
**File:** `resources/views/demand/explanation.blade.php`
- Date picker form
- School header
- Setup incomplete banner (AC3)
- Enrolment card: effective date, count, source
- Ration card: factor, source, effective date
- Item schedule table: item, unit, supply days, supplied on date?, demand, zero/unknown reason label (AC2)
- Per-item demand summary

### 5. Zero/Unknown Reason Labels
- `holiday` → “Non-working day”
- `not_participating` → “School is not participating on this date”
- `item_not_scheduled` → “Item is not scheduled for supply on this weekday”
- `school_not_participating` → “School participation has not started or has ended”
- `no_supply_pattern` → “Supply weekdays not configured”
- `missing_ration` → “No ration factor available”
- `no_pupil_count` → “No enrolment count recorded”

### 6. Access Control
- Admin: any school
- Field Staff: only schools participating on the chosen date
- Non-participating schools: show “Not participating” with reason

### 7. Tests
**File:** `tests/Feature/DemandExplanationTest.php`
- AC1: configured date shows all fields
- AC2: holiday shows “Non-working day”, unscheduled item shows “not scheduled”
- AC3: missing supply pattern shows “setup incomplete”
- Access: Admin can view, Field Staff can view participating school, 403 for non-participating

## Rollout
1. Add `explainForSchool()` to `DailyDemandService`
2. Create controller + routes
3. Create view
4. Add tests
5. Verify `php artisan test` passes