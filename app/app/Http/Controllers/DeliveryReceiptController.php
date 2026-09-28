<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Services\DailyDemandService;
use App\Services\DeliveryAllocationService;
use App\Services\ItemSupplyPattern;
use App\Services\WorkingDayCalendar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DeliveryReceiptController extends Controller
{
    private const MAX_PHOTO_KILOBYTES = 4096;

    private function mapSchoolsWithDemands($schools, $cycle, $date, $demandsService)
    {
        if ($cycle === null) {
            return $schools;
        }

        return $schools->map(function ($s) use ($cycle, $date, $demandsService) {
            $demandData = $demandsService->forSchool($cycle, $s, $date);

            return [
                'id' => $s->id,
                'code' => $s->code,
                'bangla_name' => $s->bangla_name,
                'emis_code' => $s->emis_code,
                'demands' => $demandData['items'] ?? [],
            ];
        });
    }

    public function create(Request $request, WorkingDayCalendar $calendar, DailyDemandService $demands): View
    {
        $date = $this->requestedDate($request);
        $cycle = $this->cycleFor($date);

        return view('deliveries.form', [
            'date' => $date,
            'cycle' => $cycle,
            'isWorkingDay' => $calendar->isWorkingDay($date),
            'schools' => $this->mapSchoolsWithDemands($this->schoolsFor($cycle, $date, $calendar), $cycle, $date, $demands),
            'receipt' => null,
            'existingFor' => DeliveryReceipt::query()
                ->whereDate('delivery_date', $date->toDateString())
                ->pluck('school_id')
                ->all(),
        ]);
    }

    public function store(
        Request $request,
        WorkingDayCalendar $calendar,
        DailyDemandService $demands,
    ): RedirectResponse {
        $data = $this->validated($request, $calendar);

        $school = School::query()->whereKey($data['school_id'])->firstOrFail();
        $date = CarbonImmutable::parse($data['delivery_date']);
        $cycle = $this->cycleFor($date);
        abort_if($cycle === null, 422, 'No feeding cycle covers that date.');

        $existing = $this->matchingReceipt($school, $data);
        if ($existing !== null) {
            return redirect()->route('field.entries')
                ->with('status', 'Delivery already recorded for '.$school->code.' on '.$date->format('j M Y').'.');
        }

        $duplicate = $this->duplicateChalanReceipt($school, $data);
        if ($duplicate !== null) {
            throw ValidationException::withMessages([
                'chalan_number' => 'A receipt with this chalan number and date already exists for this school.',
            ]);
        }

        $this->ensureVarianceExplanation($school, $cycle, $data);

        try {
            $receipt = DB::transaction(function () use ($request, $school, $date, $cycle, $data): DeliveryReceipt {
                $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();

                $receipt = new DeliveryReceipt([
                    'chalan_number' => $data['chalan_number'],
                    'chalan_date' => $data['chalan_date'],
                    'chalan_photo_path' => $data['chalan_photo'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'variance_explanation' => $data['variance_explanation'] ?? null,
                ]);
                $receipt->school_id = $school->id;
                $receipt->delivery_date = $date->toDateString();
                $receipt->entered_by = $request->user()->id;
                $receipt->responsible_by = $request->user()->id;
                $receipt->save();

                $this->storeQuantities($receipt, $cycle, $data['quantities']);

                app(DeliveryAllocationService::class)->replaceForReceipt(
                    $receipt,
                    $cycle,
                    $data['allocations'] === []
                        ? $this->defaultAllocations($receipt, $cycle)
                        : $data['allocations']
                );

                AuditEvent::recordSchool('delivery_recorded', $school, [
                    'delivery_receipt_id' => $receipt->id,
                    'delivery_date' => $date->toDateString(),
                    'chalan_number' => $data['chalan_number'],
                    'chalan_date' => $data['chalan_date'],
                    'quantities' => $data['quantities'],
                    'has_chalan_photo' => isset($data['chalan_photo']),
                ]);

                return $receipt;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'chalan_number' => 'A receipt with this chalan number and date already exists for this school.',
            ]);
        }

        return redirect()->route('field.entries')
            ->with('status', 'Delivery recorded for '.$school->code.' on '.$date->format('j M Y').'.');
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $receipts = DeliveryReceipt::query()
            ->where(fn ($query) => $query->where('entered_by', $user->id)->orWhere('responsible_by', $user->id))
            ->when($request->filled('school_id'), fn ($query, $schoolId) => $query->where('school_id', $schoolId))
            ->when($request->filled('from'), fn ($query, $from) => $query->whereDate('delivery_date', '>=', $from))
            ->when($request->filled('to'), fn ($query, $to) => $query->whereDate('delivery_date', '<=', $to))
            ->with(['school', 'items.item'])
            ->latest('delivery_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $schools = School::query()
            ->orderBy('code')
            ->get(['id', 'code', 'bangla_name']);

        return view('deliveries.index', [
            'receipts' => $receipts,
            'schools' => $schools,
            'filters' => $request->only(['school_id', 'from', 'to']),
        ]);
    }

    public function edit(Request $request, DeliveryReceipt $receipt, DailyDemandService $demands): View
    {
        $this->guardEditable($receipt, $request);
        $cycle = $this->cycleFor($receipt->delivery_date);
        abort_if($cycle === null, 422, 'No feeding cycle covers that date.');

        $receipt->load(['school.participationPeriods', 'items.item', 'items.allocations']);

        return view('deliveries.form', [
            'date' => $receipt->delivery_date,
            'cycle' => $cycle,
            'isWorkingDay' => true,
            'schools' => $this->mapSchoolsWithDemands(collect([$receipt->school]), $cycle, $receipt->delivery_date, $demands),
            'existingFor' => [],
            'receipt' => $receipt,
        ]);
    }

    public function update(Request $request, DeliveryReceipt $receipt): RedirectResponse
    {
        $this->guardEditable($receipt, $request);
        $data = $this->validated($request, app(WorkingDayCalendar::class), $receipt);
        $cycle = $this->cycleFor($receipt->delivery_date);
        abort_if($cycle === null, 422, 'No feeding cycle covers that date.');

        $this->ensureVarianceExplanation($receipt->school, $cycle, $data);

        if (isset($data['updated_at']) && $data['updated_at'] !== $receipt->updated_at->toDateTimeString()) {
            return redirect()->route('field.delivery.edit', $receipt)
                ->with('status', 'This record changed while you were editing it. Please review the latest values.');
        }

        $before = $receipt->quantitiesByItemKey();

        DB::transaction(function () use ($receipt, $cycle, $data, $before): void {
            $receipt = DeliveryReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            $receipt->forceFill([
                'chalan_photo_path' => $data['chalan_photo'] ?? $receipt->chalan_photo_path,
                'notes' => $data['notes'] ?? null,
                'variance_explanation' => $data['variance_explanation'] ?? null,
            ])->save();

            $this->storeQuantities($receipt, $cycle, $data['quantities']);

            app(DeliveryAllocationService::class)->replaceForReceipt(
                $receipt,
                $cycle,
                $data['allocations'] === []
                    ? $this->defaultAllocations($receipt, $cycle)
                    : $data['allocations']
            );

            AuditEvent::recordSchool('delivery_corrected', $receipt->school, [
                'delivery_receipt_id' => $receipt->id,
                'delivery_date' => $receipt->delivery_date->toDateString(),
                'before' => $before,
                'after' => $data['quantities'],
                'correction_reason' => $data['correction_reason'],
            ]);
        });

        return redirect()->route('field.entries')->with('status', 'Delivery entry corrected.');
    }

    private function storeQuantities(DeliveryReceipt $receipt, FeedingCycle $cycle, array $quantities): void
    {
        $lines = $receipt->items()->get()->keyBy('feeding_item_id');

        foreach ($cycle->items()->pluck('id') as $itemId) {
            $quantity = (int) ($quantities[(string) $itemId] ?? 0);

            $line = $lines->get($itemId) ?? new DeliveryReceiptItem;
            $line->delivery_receipt_id = $receipt->id;
            $line->feeding_item_id = $itemId;
            $line->delivered_quantity = $quantity;
            $line->save();
        }
    }

    private function guardEditable(DeliveryReceipt $receipt, Request $request): void
    {
        abort_unless($receipt->responsible_by === $request->user()->id, 404);
    }

    /**
     * @return array{delivery_date: string, school_id: int, quantities: array<string, int>, notes: string|null, chalan_photo: string|null, chalan_number: string|null, chalan_date: string|null, allocations: array<int, list<array{date: string, quantity: int}>>}
     */
    private function validated(Request $request, WorkingDayCalendar $calendar, ?DeliveryReceipt $receipt = null): array
    {
        $cycle = $this->cycleFor($this->requestedDate($request));

        $data = $request->validate([
            'delivery_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
                function (string $attribute, mixed $value, \Closure $fail) use ($calendar): void {
                    if (! is_string($value) || $calendar->isWorkingDay(CarbonImmutable::parse($value))) {
                        return;
                    }

                    $fail('The programme does not deliver on this date, so an entry cannot be recorded.');
                },
            ],
            'school_id' => [
                'required',
                'integer',
                Rule::exists('schools', 'id'),
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if (! is_numeric($value)) {
                        return;
                    }

                    $date = (string) $request->input('delivery_date');

                    $school = School::query()
                        ->with('participationPeriods')
                        ->find((int) $value);

                    if ($school === null) {
                        return;
                    }

                    if (Carbon::hasFormat($date, 'Y-m-d') && ! $school->isParticipatingOn(CarbonImmutable::parse($date))) {
                        $fail('This school is not taking part in the programme on that date.');
                    }
                },
            ],
            'quantities' => ['required', 'array'],
            'quantities.*' => ['required', 'integer', 'min:0', 'max:10000000'],
            'chalan_number' => ['nullable', 'string', 'max:100'],
            'chalan_date' => ['nullable', 'date_format:Y-m-d'],
            'allocations' => ['nullable', 'array'],
            'allocations.*' => ['array'],
            'allocations.*.*.date' => ['nullable', 'date_format:Y-m-d'],
            'allocations.*.*.quantity' => ['nullable', 'integer', 'min:0'],
            'variance_explanation' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'correction_reason' => [Rule::requiredIf($receipt !== null), 'nullable', 'string', 'max:2000'],
            'updated_at' => ['nullable', 'string'],
            'chalan_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.self::MAX_PHOTO_KILOBYTES,
            ],
        ]);

        abort_if($cycle === null, 422, 'No feeding cycle covers that date.');

        $allowed = $cycle->items()->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $submitted = array_keys($data['quantities']);

        if (array_diff($submitted, $allowed) !== []) {
            throw ValidationException::withMessages([
                'quantities' => 'A quantity was submitted for an item outside this feeding cycle.',
            ]);
        }

        $quantities = array_map(intval(...), $data['quantities']);
        $hasPositiveQuantity = max($quantities) > 0;

        if ($hasPositiveQuantity) {
            if (empty($data['chalan_number'])) {
                throw ValidationException::withMessages([
                    'chalan_number' => 'A chalan number is required when a positive quantity is received.',
                ]);
            }

            if (empty($data['chalan_date'])) {
                throw ValidationException::withMessages([
                    'chalan_date' => 'A chalan date is required when a positive quantity is received.',
                ]);
            }

            $hasExistingPhoto = $receipt !== null && $receipt->chalan_photo_path !== null;
            if (! $hasExistingPhoto && ! $request->hasFile('chalan_photo')) {
                throw ValidationException::withMessages([
                    'chalan_photo' => 'A chalan photo is required when a positive quantity is received.',
                ]);
            }
        }

        if ($request->hasFile('chalan_photo')) {
            $data['chalan_photo'] = $request->file('chalan_photo')->store('chalan', config('filesystems.default'));
        }

        $allocations = [];
        foreach (($data['allocations'] ?? []) as $itemKey => $rows) {
            $itemId = (int) $itemKey;
            $allocations[$itemId] = [];
            foreach ($rows as $row) {
                $date = $row['date'] ?? null;
                $quantity = isset($row['quantity']) ? (int) $row['quantity'] : 0;
                if (is_string($date) && $date !== '' && $quantity > 0) {
                    $allocations[$itemId][] = ['date' => $date, 'quantity' => $quantity];
                }
            }
        }

        return [
            'delivery_date' => $data['delivery_date'],
            'school_id' => (int) $data['school_id'],
            'quantities' => $quantities,
            'chalan_number' => $data['chalan_number'] ?? null,
            'chalan_date' => $data['chalan_date'] ?? null,
            'allocations' => $allocations,
            'variance_explanation' => $data['variance_explanation'] ?? null,
            'notes' => $data['notes'] ?? null,
            'chalan_photo' => $data['chalan_photo'] ?? null,
            'correction_reason' => $data['correction_reason'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function matchingReceipt(School $school, array $data): ?DeliveryReceipt
    {
        if (empty($data['chalan_number']) || empty($data['chalan_date'])) {
            return null;
        }

        $receipt = DeliveryReceipt::query()
            ->where('school_id', $school->id)
            ->where('chalan_number', $data['chalan_number'])
            ->whereDate('chalan_date', $data['chalan_date'])
            ->first();

        if ($receipt === null) {
            return null;
        }

        if ($receipt->delivery_date->toDateString() !== $data['delivery_date']) {
            return null;
        }

        $existing = $receipt->quantitiesByItemKey();
        $submitted = collect($data['quantities'])
            ->mapWithKeys(fn (int $qty, string $id): array => [
                FeedingItem::query()->whereKey((int) $id)->value('item_key') => $qty,
            ])
            ->all();

        return $existing == $submitted ? $receipt : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function duplicateChalanReceipt(School $school, array $data): ?DeliveryReceipt
    {
        if (empty($data['chalan_number']) || empty($data['chalan_date'])) {
            return null;
        }

        return DeliveryReceipt::query()
            ->where('school_id', $school->id)
            ->where('chalan_number', $data['chalan_number'])
            ->whereDate('chalan_date', $data['chalan_date'])
            ->first();
    }

    /**
     * @return array<int, list<array{date: string, quantity: int}>>
     */
    private function defaultAllocations(DeliveryReceipt $receipt, FeedingCycle $cycle): array
    {
        $allocations = [];
        $receiptItems = $receipt->items()->with('item')->get()->keyBy('feeding_item_id');
        $patterns = app(ItemSupplyPattern::class);

        foreach ($cycle->items()->get() as $item) {
            $quantity = $receiptItems->get($item->id)?->delivered_quantity ?? 0;
            if ($quantity > 0 && $patterns->isSuppliedOn($item, $receipt->delivery_date) === true) {
                $allocations[$item->id] = [
                    ['date' => $receipt->delivery_date->toDateString(), 'quantity' => $quantity],
                ];
            }
        }

        return $allocations;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, list<array{date: string, quantity: int}>>
     */
    private function effectiveAllocations(array $data, FeedingCycle $cycle): array
    {
        if ($data['allocations'] !== []) {
            return $data['allocations'];
        }

        $allocations = [];
        $patterns = app(ItemSupplyPattern::class);
        $date = CarbonImmutable::parse($data['delivery_date']);

        foreach ($cycle->items()->get() as $item) {
            $quantity = $data['quantities'][$item->id] ?? 0;
            if ($quantity > 0 && $patterns->isSuppliedOn($item, $date) === true) {
                $allocations[$item->id] = [
                    ['date' => $date->toDateString(), 'quantity' => $quantity],
                ];
            }
        }

        return $allocations;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureVarianceExplanation(School $school, FeedingCycle $cycle, array $data): void
    {
        $allocations = $this->effectiveAllocations($data, $cycle);

        if ($allocations === []) {
            return;
        }

        $demands = app(DailyDemandService::class);
        $hasVariance = false;

        foreach ($allocations as $itemId => $rows) {
            $item = FeedingItem::query()->find($itemId);
            if ($item === null) {
                continue;
            }

            foreach ($rows as $row) {
                $allocationDate = CarbonImmutable::parse($row['date']);
                $demand = $demands->forSchool($cycle, $school, $allocationDate);
                $demandValue = $demand['items'][$item->item_key] ?? null;

                if ($demandValue !== null && $demandValue !== $row['quantity']) {
                    $hasVariance = true;
                    break 2;
                }
            }
        }

        if ($hasVariance && empty($data['variance_explanation'])) {
            throw ValidationException::withMessages([
                'variance_explanation' => 'An explanation is required when supply differs from demand.',
            ]);
        }
    }

    private function requestedDate(Request $request): CarbonInterface
    {
        $raw = $request->input('delivery_date');

        return is_string($raw) && $raw !== '' && $this->isRealDate($raw)
            ? CarbonImmutable::parse($raw)
            : CarbonImmutable::today();
    }

    private function isRealDate(string $value): bool
    {
        return Carbon::hasFormat($value, 'Y-m-d');
    }

    private function cycleFor(CarbonInterface $date): ?FeedingCycle
    {
        return FeedingCycle::query()
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, School>
     */
    private function schoolsFor(?FeedingCycle $cycle, CarbonInterface $date, WorkingDayCalendar $calendar)
    {
        // Participation on the chosen date is the only filter. A school whose deactivation is dated in
        // the future is still open for today's entry, and one deactivated earlier is already excluded
        // by the closed participation period, so the directory flag is not consulted here.
        return School::query()
            ->with(['participationPeriods', 'enrolments' => fn ($query) => $query
                ->active()
                ->whereDate('effective_on', '<=', $date->toDateString())
                ->orderByDesc('effective_on')])
            ->orderBy('code')
            ->get()
            ->filter(fn (School $school): bool => $school->isParticipatingOn($date));
    }
}
