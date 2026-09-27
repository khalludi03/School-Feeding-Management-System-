# US3.3 - Maintain Effective Prices Implementation Plan

## Goal
Allow Admin to set per-item unit prices with effective dates so that invoicing uses the price applicable on the receipt date, not a later change.

## Data Model
New table: `feeding_item_prices`
- `id`
- `feeding_cycle_id` (FK to feeding_cycles)
- `feeding_item_id` (FK to feeding_items)
- `unit_price` (decimal 12,3) — three decimals retained
- `effective_on` (date)
- `created_by` (FK to users, nullable)
- `timestamps`
- Unique index on `(feeding_cycle_id, feeding_item_id, effective_on)`

Model: `FeedingItemPrice`
- `fillable`: feeding_cycle_id, feeding_item_id, unit_price, effective_on, created_by
- `casts`: unit_price => decimal:3, effective_on => date
- Relationships: belongsTo FeedingCycle, FeedingItem, User (creator)

## Service Layer

### New Service: `ItemPriceService`
```php
class ItemPriceService
{
    // Returns the applicable price for an item on a date, or null
    public function priceFor(FeedingItem $item, CarbonInterface $date): ?float
    
    // Returns all dated prices for an item (history)
    public function historyFor(FeedingItem $item): Collection
    
    // Validate price is > 0 and has at most 3 decimals
    public function validatePrice(float $price): void
}
```

Price resolution: latest `effective_on <= date`; if none, fall back to current `FeedingItem.unit_price`.

## Controller + Routes

### New Controller: `PriceController` (admin only)
```php
Route::prefix('admin')->middleware('role:admin')->group(function () {
    Route::get('/cycles/{cycle}/prices', [PriceController::class, 'index'])->name('prices.index');
    Route::get('/cycles/{cycle}/prices/create', [PriceController::class, 'create'])->name('prices.create');
    Route::post('/cycles/{cycle}/prices/review', [PriceController::class, 'review'])->name('prices.review');
    Route::get('/cycles/{cycle}/prices/review/{token}', [PriceController::class, 'showReview'])->name('prices.review.show');
    Route::post('/cycles/{cycle}/prices', [PriceController::class, 'store'])->name('prices.store');
});
```

Flow mirrors enrolment/ration:
1. Create: select item, enter price, pick effective date
2. Review: show applicability to existing receipt dates
3. Confirm: save dated price

## Views
- `prices/index.blade.php` — list current prices per item
- `prices/create.blade.php` — form with item select, price input (step 0.001), effective date, reason
- `prices/review-show.blade.php` — shows receipt dates affected, old vs new price

## Invoice Integration (Future/Out of Scope for US3.3)
- Form 10 generation should query `ItemPriceService::priceFor($item, $receipt->delivery_date)`
- Separate line items by applicable price when a period spans multiple prices
- Missing price → show “Price not set” and block final total

## Validation
- Price > 0
- Max 3 decimal places
- Effective date = today or future
- Reason required

## Audit Logging
- Action: `price_set`
- Details: feeding_cycle_id, feeding_item_id, unit_price, effective_on, reason, actor_id

## Tests
- Feature: `EffectivePriceTest`
  - AC1: September receipt uses September price even after October change
  - AC2: allocation does not alter receipt price
  - AC3: multi-price period separates quantities by rate
  - AC4: missing price blocks invoice total
  - AC5: three decimals retained

## Migration
`feeding_item_prices` table as described above.