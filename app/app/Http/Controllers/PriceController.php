<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\FeedingItemPrice;
use App\Services\ItemPriceService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use LogicException;

class PriceController extends Controller
{
    private const TOKEN_TTL_MINUTES = 15;

    public function index(FeedingCycle $cycle, ItemPriceService $itemPrices): View
    {
        $items = $cycle->items()->orderBy('sort_order')->get();

        return view('prices.index', [
            'cycle' => $cycle,
            'items' => $items,
            'itemPrices' => $itemPrices,
        ]);
    }

    public function create(FeedingCycle $cycle): View
    {
        $items = $cycle->items()->orderBy('sort_order')->get();

        return view('prices.create', [
            'cycle' => $cycle,
            'items' => $items,
        ]);
    }

    public function review(
        Request $request,
        FeedingCycle $cycle,
        ItemPriceService $itemPrices,
    ): RedirectResponse {
        $data = $this->validatedPrice($request, $cycle);

        $item = $cycle->items()->whereKey($data['feeding_item_id'])->firstOrFail();
        $itemPrices->validatePrice($data['unit_price']);

        $review = $this->storeReview($cycle, $item, $data);

        return redirect()->route('prices.review.show', ['cycle' => $cycle, 'token' => $review['token']]);
    }

    public function showReview(Request $request, FeedingCycle $cycle, string $token): View|RedirectResponse
    {
        $review = Cache::get($this->reviewCacheKey($token));

        if (! is_array($review) || ($review['feeding_cycle_id'] ?? null) !== $cycle->id) {
            return redirect()->route('prices.create', $cycle)
                ->withErrors(['effective_on' => 'This review has expired. Start the change again.']);
        }

        return view('prices.review-show', [
            'cycle' => $cycle,
            'token' => $token,
            'review' => $review,
        ]);
    }

    public function store(
        Request $request,
        FeedingCycle $cycle,
        ItemPriceService $itemPrices,
    ): RedirectResponse {
        $token = (string) $request->input('token');
        $review = Cache::get($this->reviewCacheKey($token));

        if (! is_array($review) || ($review['feeding_cycle_id'] ?? null) !== $cycle->id) {
            return redirect()->route('prices.create', $cycle)
                ->withErrors(['effective_on' => 'This review has expired. Start the change again.']);
        }

        $data = $this->validatedPrice($request, $cycle);

        if ($data['feeding_item_id'] !== $review['feeding_item_id']
            || $data['unit_price'] !== $review['unit_price']
            || $data['effective_on'] !== $review['effective_on']
        ) {
            return redirect()->route('prices.create', $cycle)
                ->withErrors(['effective_on' => 'The confirmed price does not match the reviewed price. Review it again.']);
        }

        $item = $cycle->items()->whereKey($data['feeding_item_id'])->firstOrFail();

        try {
            $price = DB::transaction(function () use ($request, $cycle, $item, $data): FeedingItemPrice {
                $existing = FeedingItemPrice::query()
                    ->where('feeding_cycle_id', $cycle->id)
                    ->where('feeding_item_id', $item->id)
                    ->whereDate('effective_on', $data['effective_on'])
                    ->first();

                if ($existing !== null) {
                    $existing->update([
                        'unit_price' => $data['unit_price'],
                        'created_by' => $request->user()->id,
                    ]);

                    return $existing;
                }

                return $cycle->itemPrices()->create([
                    'feeding_item_id' => $item->id,
                    'unit_price' => $data['unit_price'],
                    'effective_on' => $data['effective_on'],
                    'created_by' => $request->user()->id,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'effective_on' => 'A price for this item and date already exists.',
            ]);
        } catch (LogicException $exception) {
            throw ValidationException::withMessages(['effective_on' => $exception->getMessage()]);
        }

        AuditEvent::record('price_set', null, [
            'feeding_cycle_id' => $cycle->id,
            'feeding_item_id' => $item->id,
            'unit_price' => $data['unit_price'],
            'effective_on' => $data['effective_on'],
            'reason' => $data['reason'],
        ]);

        Cache::forget($this->reviewCacheKey($token));

        return redirect()->route('prices.index', $cycle)->with(
            'status',
            'Price for '.$item->name.' saved effective '.$price->effective_on->format('j M Y').'.',
        );
    }

    /**
     * @return array{feeding_item_id: int, unit_price: float, effective_on: string, reason: string}
     */
    private function validatedPrice(Request $request, FeedingCycle $cycle): array
    {
        $data = $request->validate([
            'feeding_item_id' => ['required', 'exists:feeding_items,id'],
            'unit_price' => ['required', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,3})?$/'],
            'effective_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        return [
            'feeding_item_id' => (int) $data['feeding_item_id'],
            'unit_price' => (float) $data['unit_price'],
            'effective_on' => $data['effective_on'],
            'reason' => $data['reason'],
        ];
    }

    private function reviewCacheKey(string $token): string
    {
        return 'price-change-review:'.$token;
    }

    /**
     * @return array{token: string, feeding_cycle_id: int, feeding_item_id: int, unit_price: float, effective_on: string, reason: string}
     */
    private function storeReview(FeedingCycle $cycle, FeedingItem $item, array $data): array
    {
        $token = bin2hex(random_bytes(24));
        $review = [
            'feeding_cycle_id' => $cycle->id,
            'feeding_item_id' => $item->id,
            'unit_price' => $data['unit_price'],
            'effective_on' => $data['effective_on'],
            'reason' => $data['reason'],
            'item_name' => $item->name,
            'unit' => $item->unit,
        ];

        Cache::put($this->reviewCacheKey($token), $review, now()->addMinutes(self::TOKEN_TTL_MINUTES));

        return ['token' => $token] + $review;
    }
}
