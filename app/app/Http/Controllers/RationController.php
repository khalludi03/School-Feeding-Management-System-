<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\FeedingCycle;
use App\Models\FeedingItemRation;
use App\Services\ItemRationService;
use App\Services\RationChangeImpactService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use LogicException;

class RationController extends Controller
{
    private const TOKEN_TTL_MINUTES = 15;

    public function index(FeedingCycle $cycle, ItemRationService $itemRations): View
    {
        $items = $cycle->items()->orderBy('sort_order')->get();
        $rations = $itemRations->historyFor($items->first())->map(fn ($r) => $r->effective_on)->unique()->sort()->values()->all();

        return view('rations.index', [
            'cycle' => $cycle,
            'items' => $items,
            'rations' => $rations,
            'itemRations' => $itemRations,
        ]);
    }

    public function create(FeedingCycle $cycle): View
    {
        $items = $cycle->items()->orderBy('sort_order')->get();

        return view('rations.create', [
            'cycle' => $cycle,
            'items' => $items,
        ]);
    }

    public function review(
        Request $request,
        FeedingCycle $cycle,
        ItemRationService $itemRations,
        RationChangeImpactService $impactService,
    ): RedirectResponse {
        $data = $this->validatedRation($request, $cycle);

        $item = $cycle->items()->whereKey($data['feeding_item_id'])->firstOrFail();
        $itemRations->validateRationFactor($data['ration_factor'], $item);

        $review = $impactService->forChange($item, $data['ration_factor'], $data['effective_on']);

        $token = $this->storeReview($cycle, $data, $review);

        return redirect()->route('rations.review.show', ['cycle' => $cycle, 'token' => $token]);
    }

    public function showReview(Request $request, FeedingCycle $cycle, string $token): View|RedirectResponse
    {
        $review = Cache::get($this->reviewCacheKey($token));

        if (! is_array($review) || ($review['feeding_cycle_id'] ?? null) !== $cycle->id) {
            return redirect()->route('rations.create', $cycle)
                ->withErrors(['effective_on' => 'This review has expired. Start the change again.']);
        }

        return view('rations.review-show', [
            'cycle' => $cycle,
            'token' => $token,
            'review' => $review,
        ]);
    }

    public function store(
        Request $request,
        FeedingCycle $cycle,
        ItemRationService $itemRations,
    ): RedirectResponse {
        $token = (string) $request->input('token');
        $review = Cache::get($this->reviewCacheKey($token));

        if (! is_array($review) || ($review['feeding_cycle_id'] ?? null) !== $cycle->id) {
            return redirect()->route('rations.create', $cycle)
                ->withErrors(['effective_on' => 'This review has expired. Start the change again.']);
        }

        $data = $this->validatedRation($request, $cycle);

        if ($data['feeding_item_id'] !== $review['feeding_item_id']
            || $data['ration_factor'] !== $review['ration_factor']
            || $data['effective_on'] !== $review['effective_on']
        ) {
            return redirect()->route('rations.create', $cycle)
                ->withErrors(['effective_on' => 'The confirmed ration does not match the reviewed ration. Review it again.']);
        }

        $item = $cycle->items()->whereKey($data['feeding_item_id'])->firstOrFail();

        try {
            $ration = DB::transaction(function () use ($request, $cycle, $item, $data): FeedingItemRation {
                $existing = FeedingItemRation::query()
                    ->where('feeding_cycle_id', $cycle->id)
                    ->where('feeding_item_id', $item->id)
                    ->whereDate('effective_on', $data['effective_on'])
                    ->first();

                if ($existing !== null) {
                    $existing->update([
                        'ration_factor' => $data['ration_factor'],
                        'created_by' => $request->user()->id,
                    ]);

                    return $existing;
                }

                return $cycle->itemRations()->create([
                    'feeding_item_id' => $item->id,
                    'ration_factor' => $data['ration_factor'],
                    'effective_on' => $data['effective_on'],
                    'created_by' => $request->user()->id,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'effective_on' => 'A ration for this item and date already exists.',
            ]);
        } catch (LogicException $exception) {
            throw ValidationException::withMessages(['effective_on' => $exception->getMessage()]);
        }

        AuditEvent::record('ration_set', null, [
            'feeding_cycle_id' => $cycle->id,
            'feeding_item_id' => $item->id,
            'ration_factor' => $data['ration_factor'],
            'effective_on' => $data['effective_on'],
            'reason' => $data['reason'],
        ]);

        Cache::forget($this->reviewCacheKey($token));

        return redirect()->route('rations.index', $cycle)->with(
            'status',
            'Ration for '.$item->name.' saved effective '.$ration->effective_on->format('j M Y').'.',
        );
    }

    /**
     * @return array{feeding_item_id: int, ration_factor: float, effective_on: string, reason: string}
     */
    private function validatedRation(Request $request, FeedingCycle $cycle): array
    {
        $data = $request->validate([
            'feeding_item_id' => ['required', 'exists:feeding_items,id'],
            'ration_factor' => ['required', 'numeric', 'gt:0', 'lte:1', 'regex:/^\d+(\.\d{1,3})?$/'],
            'effective_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $item = $cycle->items()->whereKey($data['feeding_item_id'])->firstOrFail();

        if ((float) $data['ration_factor'] > 1) {
            throw ValidationException::withMessages([
                'ration_factor' => 'Ration factor cannot exceed 1 (100%).',
            ]);
        }

        return [
            'feeding_item_id' => (int) $data['feeding_item_id'],
            'ration_factor' => (float) $data['ration_factor'],
            'effective_on' => $data['effective_on'],
            'reason' => $data['reason'],
        ];
    }

    private function reviewCacheKey(string $token): string
    {
        return 'ration-change-review:'.$token;
    }

    private function storeReview(FeedingCycle $cycle, array $data, array $review): string
    {
        $token = bin2hex(random_bytes(24));
        Cache::put($this->reviewCacheKey($token), [
            'feeding_cycle_id' => $cycle->id,
            'feeding_item_id' => $data['feeding_item_id'],
            'ration_factor' => $data['ration_factor'],
            'effective_on' => $data['effective_on'],
            'reason' => $data['reason'],
            ...$review,
        ], now()->addMinutes(self::TOKEN_TTL_MINUTES));

        return $token;
    }
}
