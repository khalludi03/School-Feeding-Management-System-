<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\School;
use App\Models\SchoolEnrolment;
use App\Services\EnrolmentChangeImpactService;
use App\Services\EnrolmentProjectionService;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use LogicException;

class SchoolEnrolmentController extends Controller
{
    private const TOKEN_TTL_MINUTES = 15;

    public function create(Request $request, School $school, EnrolmentProjectionService $projections): View
    {
        $this->guardActiveSchool($school);

        return view('schools.enrolment-form', [
            'school' => $school,
            'currentCount' => $projections->currentCount($school),
            'firstEffectiveOn' => $projections->firstEffectiveOn($school),
            'scheduled' => $projections->scheduledEnrolments($school),
        ]);
    }

    public function review(
        Request $request,
        School $school,
        EnrolmentChangeImpactService $impact,
    ): RedirectResponse {
        $this->guardActiveSchool($school);
        $data = $this->validatedChange($request, $school);

        $review = $impact->forChange($school, $data['effective_on'], $data['pupil_count']);

        if ($review['missing_factor_cycles'] !== []) {
            throw ValidationException::withMessages([
                'effective_on' => 'These open feeding cycles have no ration factor, so the demand impact cannot be reviewed yet: '
                    .implode(', ', $review['missing_factor_cycles']).'.',
            ]);
        }

        $token = $this->storeReview($school, $data, $review);

        return redirect()->route('schools.enrolments.review.show', ['school' => $school, 'token' => $token]);
    }

    public function showReview(Request $request, School $school, string $token): View|RedirectResponse
    {
        $this->guardActiveSchool($school);
        $review = Cache::get($this->reviewCacheKey($token));

        if (! is_array($review) || ($review['school_id'] ?? null) !== $school->id) {
            return redirect()->route('schools.enrolments.create', $school)
                ->withErrors(['effective_on' => 'This review has expired. Start the change again.']);
        }

        return view('schools.enrolment-review', [
            'school' => $school,
            'token' => $token,
            'review' => $review,
        ]);
    }

    public function store(
        Request $request,
        School $school,
        EnrolmentProjectionService $projections,
    ): RedirectResponse {
        $this->guardActiveSchool($school);
        $token = (string) $request->input('token');
        $review = Cache::get($this->reviewCacheKey($token));

        if (! is_array($review) || ($review['school_id'] ?? null) !== $school->id) {
            return redirect()->route('schools.enrolments.create', $school)
                ->withErrors(['effective_on' => 'This review has expired. Start the change again.']);
        }

        $data = $this->validatedChange($request, $school);

        if ($data['effective_on'] !== $review['effective_on'] || $data['pupil_count'] !== $review['pupil_count']) {
            return redirect()->route('schools.enrolments.create', $school)
                ->withErrors(['effective_on' => 'The confirmed change does not match the reviewed change. Review it again.']);
        }

        $currentCount = $projections->currentCount($school);

        try {
            $enrolment = DB::transaction(function () use ($request, $school, $data, $currentCount, $review): SchoolEnrolment {
                $school = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
                abort_unless($school->is_active, 409, 'This school is no longer active.');

                $enrolment = $school->enrolments()->create([
                    'effective_on' => $data['effective_on'],
                    'pupil_count' => $data['pupil_count'],
                    'recorded_by' => $request->user()->id,
                    'reason' => $data['reason'],
                ]);

                AuditEvent::recordSchool('enrolment_change_scheduled', $school, [
                    'enrolment_id' => $enrolment->id,
                    'reason' => $data['reason'],
                    'before' => [
                        'pupil_count' => $currentCount,
                        'status' => 'current_projection',
                    ],
                    'after' => [
                        'pupil_count' => $data['pupil_count'],
                        'effective_on' => $data['effective_on'],
                        'status' => $review['status'],
                    ],
                    'impact' => $review['cycles'],
                ]);

                return $enrolment;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'effective_on' => 'Another enrolment change is already recorded for that date.',
            ]);
        } catch (LogicException $exception) {
            throw ValidationException::withMessages(['effective_on' => $exception->getMessage()]);
        }

        Cache::forget($this->reviewCacheKey($token));

        return redirect()->route('schools.show', $school)->with(
            'status',
            'Enrolment change scheduled for '.$enrolment->effective_on->format('j M Y').'.',
        );
    }

    public function confirmCancel(School $school, SchoolEnrolment $enrolment): View
    {
        abort_unless($enrolment->school_id === $school->id, 404);
        abort_unless($enrolment->isScheduled(), 409, 'Only a scheduled change can be cancelled.');

        return view('schools.enrolment-cancel-confirm', compact('school', 'enrolment'));
    }

    public function cancel(Request $request, School $school, SchoolEnrolment $enrolment): RedirectResponse
    {
        abort_unless($enrolment->school_id === $school->id, 404);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($request, $school, $enrolment, $data): void {
                $enrolment = SchoolEnrolment::whereKey($enrolment->id)->lockForUpdate()->firstOrFail();
                abort_unless($enrolment->school_id === $school->id, 404);

                if (! $enrolment->isScheduled()) {
                    throw ValidationException::withMessages([
                        'reason' => 'Only a change that has not taken effect yet can be cancelled.',
                    ]);
                }

                $enrolment->forceFill([
                    'cancelled_at' => now(),
                    'cancelled_by' => $request->user()->id,
                    'cancellation_reason' => $data['reason'],
                ])->save();

                AuditEvent::recordSchool('enrolment_change_cancelled', $school, [
                    'enrolment_id' => $enrolment->id,
                    'reason' => $data['reason'],
                    'before' => [
                        'pupil_count' => $enrolment->pupil_count,
                        'effective_on' => $enrolment->effective_on->toDateString(),
                        'status' => 'Scheduled',
                    ],
                    'after' => [
                        'pupil_count' => null,
                        'status' => 'Cancelled',
                    ],
                ]);
            });
        } catch (LogicException $exception) {
            throw ValidationException::withMessages(['reason' => $exception->getMessage()]);
        }

        return redirect()->route('schools.show', $school)->with('status', 'Scheduled enrolment change cancelled.');
    }

    /**
     * @return array{effective_on: string, pupil_count: int, reason: string}
     */
    private function validatedChange(Request $request, School $school): array
    {
        $data = $request->validate([
            'effective_on' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail) use ($school): void {
                    if (! is_string($value) || ! $school->enrolments()->active()->whereDate('effective_on', $value)->exists()) {
                        return;
                    }

                    $fail('An enrolment change is already recorded for that date. Cancel the existing change first or choose another date.');
                },
            ],
            'pupil_count' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        return [
            'effective_on' => $data['effective_on'],
            'pupil_count' => (int) $data['pupil_count'],
            'reason' => $data['reason'],
        ];
    }

    /**
     * @param  array{effective_on: string, pupil_count: int, reason: string}  $data
     * @param  array<string, mixed>  $review
     */
    private function storeReview(School $school, array $data, array $review): string
    {
        $token = bin2hex(random_bytes(24));
        Cache::put($this->reviewCacheKey($token), [
            'school_id' => $school->id,
            'effective_on' => $data['effective_on'],
            'pupil_count' => $data['pupil_count'],
            'reason' => $data['reason'],
            ...$review,
        ], now()->addMinutes(self::TOKEN_TTL_MINUTES));

        return $token;
    }

    private function reviewCacheKey(string $token): string
    {
        return 'enrolment-change-review:'.$token;
    }

    private function guardActiveSchool(School $school): void
    {
        abort_unless($school->is_active, 409, 'Inactive schools cannot receive new enrolment.');
    }
}
