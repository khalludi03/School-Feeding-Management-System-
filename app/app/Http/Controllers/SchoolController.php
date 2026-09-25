<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\School;
use App\Services\ProvisionalEmisService;
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
        if (Str::length($search) > 120) {
            $search = Str::substr($search, 0, 120);
        }
        $includeInactive = $request->boolean('include_inactive');
        $schools = School::query()
            ->with([
                'enrolments' => fn ($query) => $query
                    ->whereDate('effective_on', '<=', today())
                    ->orderByDesc('effective_on')->orderByDesc('id'),
                'participationPeriods' => fn ($query) => $query->orderByDesc('starts_on'),
                'planningSnapshots' => fn ($query) => $query->with('feedingCycle')->latest('id'),
            ])
            ->when(! $includeInactive, fn ($query) => $query->where('is_active', true))
            ->when($search !== '', fn ($query) => $query->where(function ($matches) use ($search): void {
                $pattern = '%'.$this->escapeLike($search).'%';
                foreach (['code', 'bangla_name', 'emis_code'] as $column) {
                    $matches->orWhereRaw("LOWER({$column}) LIKE LOWER(?) ESCAPE '!'", [$pattern]);
                }
            }))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('schools.index', compact('schools', 'search', 'includeInactive'));
    }

    public function create(): View
    {
        return view('schools.form', ['school' => null]);
    }

    public function store(Request $request, SchoolCodeService $codes, ProvisionalEmisService $provisionalEmis): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $school = DB::transaction(function () use ($request, $data, $codes, $provisionalEmis): School {
                $identity = $this->identityData($data, $request, true);
                if ($data['generate_provisional_emis']) {
                    $identity['emis_code'] = $provisionalEmis->generate();
                    $identity['emis_source'] = 'Generated provisional identity';
                    $identity['emis_is_provisional'] = true;
                    $identity['emis_verified_at'] = null;
                    $identity['emis_verified_by'] = null;
                }

                $school = School::create([
                    ...$identity,
                    'code' => $codes->nextCode(),
                    'is_active' => true,
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
                    'emis_is_provisional' => $school->emis_is_provisional,
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
            'planningSnapshots' => fn ($query) => $query->with('feedingCycle')->latest('id'),
            'auditEvents' => fn ($query) => $query->with('actor')->latest('created_at')->latest('id'),
        ]);

        return view('schools.show', compact('school'));
    }

    public function edit(School $school): View
    {
        return view('schools.form', compact('school'));
    }

    public function update(Request $request, School $school, ProvisionalEmisService $provisionalEmis): RedirectResponse
    {
        try {
            $changed = DB::transaction(function () use ($request, $school, $provisionalEmis): bool {
                $school = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
                $data = $this->validated($request, $school);
                $identity = $this->identityData($data, $request, false, $school);
                if ($data['generate_provisional_emis']) {
                    $identity['emis_code'] = $provisionalEmis->generate();
                    $identity['emis_source'] = 'Generated provisional identity';
                    $identity['emis_is_provisional'] = true;
                    $identity['emis_verified_at'] = null;
                    $identity['emis_verified_by'] = null;
                }

                $school->fill($identity);
                $changes = [];
                foreach (['bangla_name', 'union', 'cluster', 'teacher_name', 'teacher_phone', 'emis_code', 'emis_source', 'emis_is_provisional'] as $field) {
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

    public function confirmDeactivate(School $school): View
    {
        abort_unless($school->is_active, 409, 'This school is no longer active.');

        return view('schools.confirm-action', ['school' => $school, 'action' => 'deactivate']);
    }

    public function confirmReactivate(School $school): View
    {
        abort_if($school->is_active, 409, 'This school is already active.');

        return view('schools.confirm-action', ['school' => $school, 'action' => 'reactivate']);
    }

    public function deactivate(Request $request, School $school): RedirectResponse
    {
        $data = $this->validatedStatusChange($request);
        DB::transaction(function () use ($data, $school): void {
            $school = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            abort_unless($school->is_active, 409, 'This school is no longer active.');
            $school->forceFill(['is_active' => false])->save();
            AuditEvent::recordSchool('school_deactivated', $school, ['reason' => $data['reason']]);
        });

        return redirect()->route('schools.show', $school)->with('status', 'School deactivated.');
    }

    public function reactivate(Request $request, School $school): RedirectResponse
    {
        $data = $this->validatedStatusChange($request);
        DB::transaction(function () use ($data, $school): void {
            $school = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            abort_if($school->is_active, 409, 'This school is already active.');
            if ($school->emis_code === null) {
                throw ValidationException::withMessages([
                    'emis_code' => 'Add an official or provisional EMIS before reactivating this school.',
                ]);
            }
            $school->forceFill(['is_active' => true])->save();
            AuditEvent::recordSchool('school_reactivated', $school, ['reason' => $data['reason']]);
        });

        return redirect()->route('schools.show', $school)->with('status', 'School reactivated.');
    }

    private function validatedStatusChange(Request $request): array
    {
        return $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'current_password' => ['required', 'current_password'],
        ]);
    }

    private function validated(Request $request, ?School $school = null): array
    {
        $generateProvisional = $request->boolean('generate_provisional_emis');
        $emisCode = $request->input('emis_code', $school?->emis_code);
        $emisSource = $request->input('emis_source', $school?->emis_source);
        $submittedEmisCode = is_string($emisCode) ? (trim($emisCode) === '' ? null : Str::upper(trim($emisCode))) : $emisCode;
        $submittedEmisSource = is_string($emisSource) ? (trim($emisSource) === '' ? null : trim($emisSource)) : $emisSource;
        $teacherPhone = $request->input('teacher_phone');
        if ($generateProvisional && (
            ($school === null && ($submittedEmisCode !== null || $submittedEmisSource !== null))
            || ($school !== null && (
                ($submittedEmisCode !== null && $submittedEmisCode !== $school->emis_code)
                || ($submittedEmisSource !== null && $submittedEmisSource !== $school->emis_source)
            ))
        )) {
            throw ValidationException::withMessages([
                'emis_code' => 'Leave the official EMIS fields blank when generating a provisional identity.',
            ]);
        }
        $request->merge([
            'emis_code' => $generateProvisional
                ? null
                : (is_string($emisCode) ? (trim($emisCode) === '' ? null : Str::upper(trim($emisCode))) : $emisCode),
            'emis_source' => $generateProvisional
                ? null
                : (is_string($emisSource) ? (trim($emisSource) === '' ? null : trim($emisSource)) : $emisSource),
            'teacher_phone' => is_string($teacherPhone) ? (trim($teacherPhone) === '' ? null : preg_replace('/[\s()+-]/', '', $teacherPhone)) : $teacherPhone,
            'generate_provisional_emis' => $generateProvisional,
        ]);

        $emisChanged = $request->input('emis_code') !== $school?->emis_code
            || $request->input('emis_source') !== $school?->emis_source;
        $needsVerification = ! $generateProvisional && $emisChanged && $request->filled('emis_code');
        $rules = [
            'bangla_name' => ['required', 'string', 'max:255', 'regex:/[\x{0985}-\x{09B9}\x{09DC}-\x{09DF}]/u'],
            'union' => ['nullable', 'string', 'max:120'],
            'cluster' => ['nullable', 'string', 'max:120'],
            'teacher_name' => ['nullable', 'string', 'max:255'],
            'teacher_phone' => ['nullable', 'string', 'max:20', 'regex:/^(?:880|0)[1-9]\d{8,10}$/'],
            'emis_code' => ['nullable', 'string', 'max:64', Rule::unique('schools', 'emis_code')->ignore($school?->id)],
            'emis_source' => ['nullable', 'string', 'max:255', 'required_with:emis_code', Rule::prohibitedIf(! $request->filled('emis_code'))],
            'generate_provisional_emis' => ['nullable', 'boolean'],
            'emis_verified' => $needsVerification ? ['accepted'] : ['prohibited'],
        ];

        if ($school === null) {
            $rules['code'] = ['prohibited'];
            $rules['enrolment_count'] = ['required', 'integer', 'min:0', 'max:1000000'];
            $rules['enrolment_effective_on'] = ['required', 'date_format:Y-m-d', 'before_or_equal:today'];
            $rules['participation_starts_on'] = ['required', 'date_format:Y-m-d'];
        } else {
            foreach (['union', 'cluster', 'teacher_name', 'teacher_phone', 'emis_code', 'emis_source', 'generate_provisional_emis'] as $field) {
                $rules[$field][] = 'present';
            }
            $rules['code'] = ['prohibited'];
            $rules['enrolment_count'] = ['prohibited'];
            $rules['enrolment_effective_on'] = ['prohibited'];
            $rules['participation_starts_on'] = ['prohibited'];
        }

        $data = $request->validate($rules);
        if ($school === null && ! $generateProvisional && ! $request->filled('emis_code')) {
            throw ValidationException::withMessages([
                'emis_code' => 'Provide an attested official EMIS or explicitly generate a provisional EMIS.',
            ]);
        }
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
            'emis_is_provisional' => $school?->emis_is_provisional ?? false,
        ];

        $emisChanged = $creating || $data['generate_provisional_emis']
            || $identity['emis_code'] !== $school?->emis_code
            || $identity['emis_source'] !== $school?->emis_source;
        if ($emisChanged) {
            if ($data['generate_provisional_emis']) {
                $identity['emis_is_provisional'] = true;
                $identity['emis_verified_at'] = null;
                $identity['emis_verified_by'] = null;
            } else {
                $identity['emis_is_provisional'] = false;
                $identity['emis_verified_at'] = $identity['emis_code'] ? now() : null;
                $identity['emis_verified_by'] = $identity['emis_code'] ? $request->user()->id : null;
            }
        }

        return $identity;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    private function throwUniqueConflict(UniqueConstraintViolationException $exception): never
    {
        $codeConflict = str_contains($exception->getMessage(), 'schools_code_unique')
            || str_contains($exception->getMessage(), 'schools.code');

        throw ValidationException::withMessages($codeConflict
            ? ['code' => 'A generated school code conflicted. Please try again.']
            : ['emis_code' => 'This EMIS is already used by another school.']);
    }
}
