<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\FeedingCycle;
use App\Models\School;
use App\Services\DailyDemandService;
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

    public function create(Request $request, WorkingDayCalendar $calendar): View
    {
        $date = $this->requestedDate($request);
        $cycle = $this->cycleFor($date);

        return view('deliveries.form', [
            'date' => $date,
            'cycle' => $cycle,
            'isWorkingDay' => $calendar->isWorkingDay($date),
            'schools' => $this->schoolsFor($cycle, $date, $calendar),
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

        try {
            $receipt = DB::transaction(function () use ($request, $school, $date, $cycle, $data): DeliveryReceipt {
                $school = School::query()->whereKey($school->id)->lockForUpdate()->firstOrFail();

                $receipt = new DeliveryReceipt([
                    'chalan_photo_path' => $data['chalan_photo'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);
                $receipt->school_id = $school->id;
                $receipt->delivery_date = $date->toDateString();
                $receipt->entered_by = $request->user()->id;
                $receipt->save();

                $this->storeQuantities($receipt, $cycle, $data['quantities']);

                AuditEvent::recordSchool('delivery_recorded', $school, [
                    'delivery_receipt_id' => $receipt->id,
                    'delivery_date' => $date->toDateString(),
                    'quantities' => $data['quantities'],
                    'has_chalan_photo' => isset($data['chalan_photo']),
                ]);

                return $receipt;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'delivery_date' => 'An entry for this school and date already exists. Correct the existing entry instead.',
            ]);
        }

        return redirect()->route('field.entries')
            ->with('status', 'Delivery recorded for '.$school->code.' on '.$date->format('j M Y').'.');
    }

    public function index(Request $request): View
    {
        $receipts = DeliveryReceipt::query()
            ->where('entered_by', $request->user()->id)
            ->with(['school', 'items.item'])
            ->latest('delivery_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('deliveries.index', compact('receipts'));
    }

    public function edit(Request $request, DeliveryReceipt $receipt, DailyDemandService $demands): View
    {
        $this->guardEditable($receipt, $request);
        $cycle = $this->cycleFor($receipt->delivery_date);
        abort_if($cycle === null, 422, 'No feeding cycle covers that date.');

        $receipt->load(['school.participationPeriods', 'items.item']);

        return view('deliveries.form', [
            'date' => $receipt->delivery_date,
            'cycle' => $cycle,
            'isWorkingDay' => true,
            'schools' => collect([$receipt->school]),
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

        $before = $receipt->quantitiesByItemKey();

        DB::transaction(function () use ($receipt, $cycle, $data, $before): void {
            $receipt = DeliveryReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            $receipt->forceFill([
                'chalan_photo_path' => $data['chalan_photo'] ?? $receipt->chalan_photo_path,
                'notes' => $data['notes'] ?? null,
            ])->save();

            $this->storeQuantities($receipt, $cycle, $data['quantities']);

            AuditEvent::recordSchool('delivery_corrected', $receipt->school, [
                'delivery_receipt_id' => $receipt->id,
                'delivery_date' => $receipt->delivery_date->toDateString(),
                'before' => $before,
                'after' => $data['quantities'],
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
        abort_unless($receipt->entered_by === $request->user()->id, 404);
    }

    /**
     * @return array{delivery_date: string, school_id: int, quantities: array<string, int>, notes: string|null, chalan_photo: string|null}
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
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $receipt): void {
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

                        return;
                    }

                    $clash = DeliveryReceipt::query()
                        ->where('school_id', $school->id)
                        ->whereDate('delivery_date', $date)
                        ->when($receipt !== null, fn ($query) => $query->whereKeyNot($receipt->id))
                        ->exists();

                    if ($clash) {
                        $fail('An entry for this school and date already exists. Correct the existing entry instead.');
                    }
                },
            ],
            'quantities' => ['required', 'array'],
            'quantities.*' => ['required', 'integer', 'min:0', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
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

        if ($request->hasFile('chalan_photo')) {
            $data['chalan_photo'] = $request->file('chalan_photo')->store('chalan', 'public');
        }

        return [
            'delivery_date' => $data['delivery_date'],
            'school_id' => (int) $data['school_id'],
            'quantities' => array_map(intval(...), $data['quantities']),
            'notes' => $data['notes'] ?? null,
            'chalan_photo' => $data['chalan_photo'] ?? null,
        ];
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
