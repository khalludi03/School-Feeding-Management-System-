<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\School;
use App\Services\SchoolCodeService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function index(Request $request): View
    {
        $searchTerm = $request->query('search', '');
        $search = is_string($searchTerm) ? trim($searchTerm) : '';
        $schools = School::with([
            'enrolments' => fn ($query) => $query->orderByDesc('effective_on')->orderByDesc('id'),
            'participationPeriods' => fn ($query) => $query->orderByDesc('starts_on'),
        ])->when($search !== '', fn ($query) => $query->where(fn ($matches) => $matches
            ->where('code', 'like', "%{$search}%")
            ->orWhere('bangla_name', 'like', "%{$search}%")
            ->orWhere('emis_code', 'like', "%{$search}%")))
            ->orderBy('id')->paginate(15)->withQueryString();

        return view('schools.index', compact('schools', 'search'));
    }

    public function create(): View
    {
        return view('schools.form', ['school' => null]);
    }

    public function store(Request $request, SchoolCodeService $codes): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $school = DB::transaction(function () use ($request, $data, $codes): School {
                $school = School::create([
                    ...$this->identityData($data, $request, true),
                    'code' => $codes->nextCode(),
                ]);
                $school->enrolments()->create([
                    'effective_on' => $data['enrolment_effective_on'],
                    'pupil_count' => $data['enrolment_count'],
                    'recorded_by' => $request->user()->id,
                ]);
                $school->participationPeriods()->create([
                    'starts_on' => $data['participation_starts_on'],
                    'recorded_by' => $request->user()->id,
                ]);
                AuditEvent::recordSchool('school_created', $school, [
                    'code' => $school->code,
                    'bangla_name' => $school->bangla_name,
                    'enrolment_count' => $data['enrolment_count'],
                    'enrolment_effective_on' => $data['enrolment_effective_on'],
                    'participation_starts_on' => $data['participation_starts_on'],
                    'emis_code' => $school->emis_code,
                    'emis_source' => $school->emis_source,
                ]);

                return $school;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $this->throwUniqueConflict($exception);
        }

        return redirect()->route('schools.show', $school)->with('status', 'School created.');
    }

    public function show(School $school): View
    {
        $school->load([
            'enrolments' => fn ($query) => $query->orderByDesc('effective_on')->orderByDesc('id'),
            'participationPeriods' => fn ($query) => $query->orderByDesc('starts_on'),
        ]);

        return view('schools.show', compact('school'));
    }

    public function edit(School $school): View
    {
        return view('schools.form', compact('school'));
    }

    public function update(Request $request, School $school): RedirectResponse
    {
        try {
            $changed = DB::transaction(function () use ($request, $school): bool {
                $school = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
                $data = $this->validated($request, $school);
                $school->fill($this->identityData($data, $request, false, $school));
                $changes = [];
                foreach (['bangla_name', 'union', 'cluster', 'teacher_name', 'teacher_phone', 'emis_code', 'emis_source'] as $field) {
                    if ($school->isDirty($field)) {
                        $changes[$field] = [
                            'from' => $school->getRawOriginal($field),
                            'to' => $school->getAttribute($field),
                        ];
                    }
                }

                if ($changes === []) {
                    return false;
                }

                $school->save();
                AuditEvent::recordSchool('school_identity_updated', $school, ['changes' => $changes]);

                return true;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $this->throwUniqueConflict($exception);
        }

        return redirect()->route('schools.show', $school)
            ->with('status', $changed ? 'School identity updated.' : 'No identity changes were made.');
    }

    private function validated(Request $request, ?School $school = null): array
    {
        $emisCode = $request->input('emis_code', $school?->emis_code);
        $emisSource = $request->input('emis_source', $school?->emis_source);
        $teacherPhone = $request->input('teacher_phone');
        $request->merge([
            'emis_code' => is_string($emisCode) ? (trim($emisCode) === '' ? null : Str::upper(trim($emisCode))) : $emisCode,
            'emis_source' => is_string($emisSource) ? (trim($emisSource) === '' ? null : trim($emisSource)) : $emisSource,
            'teacher_phone' => is_string($teacherPhone) ? (trim($teacherPhone) === '' ? null : preg_replace('/[\s()+-]/', '', $teacherPhone)) : $teacherPhone,
        ]);

        $emisChanged = $request->input('emis_code') !== $school?->emis_code
            || $request->input('emis_source') !== $school?->emis_source;
        $needsVerification = $request->filled('emis_code') && $emisChanged;

        $rules = [
            'bangla_name' => ['required', 'string', 'max:255', 'regex:/[\x{0985}-\x{09B9}\x{09DC}-\x{09DF}]/u'],
            'union' => ['nullable', 'string', 'max:120'],
            'cluster' => ['nullable', 'string', 'max:120'],
            'teacher_name' => ['nullable', 'string', 'max:255'],
            'teacher_phone' => ['nullable', 'string', 'max:20', 'regex:/^(?:880|0)[1-9]\d{8,10}$/'],
            'emis_code' => ['nullable', 'string', 'max:64', Rule::unique('schools', 'emis_code')->ignore($school?->id)],
            'emis_source' => ['nullable', 'string', 'max:255', 'required_with:emis_code', Rule::prohibitedIf(! $request->filled('emis_code'))],
            'emis_verified' => $needsVerification ? ['accepted'] : ['prohibited'],
        ];

        if ($school === null) {
            $rules['code'] = ['prohibited'];
            $rules['enrolment_count'] = ['required', 'integer', 'min:0', 'max:1000000'];
            $rules['enrolment_effective_on'] = ['required', 'date_format:Y-m-d', 'before_or_equal:today'];
            $rules['participation_starts_on'] = ['required', 'date_format:Y-m-d'];
        } else {
            foreach (['union', 'cluster', 'teacher_name', 'teacher_phone', 'emis_code', 'emis_source'] as $field) {
                $rules[$field][] = 'present';
            }
            $rules['code'] = ['prohibited'];
            $rules['enrolment_count'] = ['prohibited'];
            $rules['enrolment_effective_on'] = ['prohibited'];
            $rules['participation_starts_on'] = ['prohibited'];
        }

        $data = $request->validate($rules);
        if (! empty($data['teacher_phone']) && str_starts_with($data['teacher_phone'], '0')) {
            $data['teacher_phone'] = '88'.$data['teacher_phone'];
        }

        return $data;
    }

    private function identityData(array $data, Request $request, bool $creating, ?School $school = null): array
    {
        $identity = [
            'bangla_name' => $data['bangla_name'],
            'union' => $data['union'] ?? null,
            'cluster' => $data['cluster'] ?? null,
            'teacher_name' => $data['teacher_name'] ?? null,
            'teacher_phone' => $data['teacher_phone'] ?? null,
            'emis_code' => $data['emis_code'] ?? null,
            'emis_source' => $data['emis_source'] ?? null,
        ];

        $emisChanged = $creating || $identity['emis_code'] !== $school?->emis_code
            || $identity['emis_source'] !== $school?->emis_source;
        if ($emisChanged) {
            $identity['emis_verified_at'] = $identity['emis_code'] ? now() : null;
            $identity['emis_verified_by'] = $identity['emis_code'] ? $request->user()->id : null;
        }

        return $identity;
    }

    private function throwUniqueConflict(UniqueConstraintViolationException $exception): never
    {
        $codeConflict = str_contains($exception->getMessage(), 'schools_code_unique')
            || str_contains($exception->getMessage(), 'schools.code');

        throw ValidationException::withMessages($codeConflict
            ? ['code' => 'A generated school code conflicted. Please try again.']
            : ['emis_code' => 'This verified EMIS is already used by another school.']);
    }
}
