# US3.1 - Set a Dated Ration Implementation Plan

## Goal
Enable Admin to set per-item (bun, boiled egg, banana) rations with effective dates so that all schools' demand follows the applicable programme rule from that date forward.

## Decisions Made

| Decision | Choice |
|----------|--------|
| **Storage** | New `feeding_item_rations` table with `feeding_cycle_id`, `feeding_item_id`, `ration_factor` (decimal 4,3), `effective_on` (date), `created_by`, timestamps. Unique index on `(feeding_cycle_id, feeding_item_id, effective_on)`. |
| **Resolution Logic** | Latest per-item ration where `effective_on <= date` applies. Falls back to `FeedingCycle.ration_factor` if no item-specific ration exists. Null ration = unknown demand. |
| **Fallback** | Cycle-level `ration_factor` kept as fallback for items without dated rations. |
| **Date Constraints** | Effective date = today or future (no past dates). |
| **Validation** | Ration factor > 0, ≤ 1 (max 100%). Must produce whole packets/pieces per student (see US3.1-AC2). |

---

## Data Model

### New Migration: `feeding_item_rations`
```php
Schema::create('feeding_item_rations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('feeding_cycle_id')->constrained()->cascadeOnDelete();
    $table->foreignId('feeding_item_id')->constrained()->cascadeOnDelete();
    $table->decimal('ration_factor', 4, 3); // e.g., 0.900
    $table->date('effective_on');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->unique(['feeding_cycle_id', 'feeding_item_id', 'effective_on']);
    $table->index(['feeding_cycle_id', 'feeding_item_id', 'effective_on']);
});
```

### Model: `FeedingItemRation`
```php
class FeedingItemRation extends Model
{
    protected $fillable = ['feeding_cycle_id', 'feeding_item_id', 'ration_factor', 'effective_on', 'created_by'];
    protected function casts(): array { return ['ration_factor' => 'decimal:3', 'effective_on' => 'date']; }
    
    public function feedingCycle(): BelongsTo { return $this->belongsTo(FeedingCycle::class); }
    public function feedingItem(): BelongsTo { return $this->belongsTo(FeedingItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
```

---

## Service Layer Changes

### 1. New Service: `ItemRationService`
```php
class ItemRationService
{
    // Returns the applicable ration factor for an item on a date, or null
    public function rationFactorFor(FeedingItem $item, CarbonInterface $date): ?float
    
    // Returns all dated rations for an item (history view)
    public function historyFor(FeedingItem $item): Collection
    
    // Validate ration factor produces whole units per student
    public function validateRationFactor(float $rationFactor, FeedingItem $item): void
}
```

### 2. Update `CycleDemandService`
- Modify `rationFactorFor()` to use `ItemRationService` for per-item resolution
- Cycle-level `ration_factor` becomes fallback
- Per-item demand = `pupil_count * item_ration_factor` (rounded)

### 3. Update `DailyDemandService`
- Pass per-item ration factors through to demand calculation
- Items not supplied on a date still return 0 (US3.1-AC3)

---

## Controller & Routes

### New Controller: `RationController` (admin only)
```php
Route::prefix('admin')->middleware('role:admin')->group(function () {
    Route::get('/cycles/{cycle}/rations', [RationController::class, 'index'])->name('rations.index');
    Route::get('/cycles/{cycle}/rations/create', [RationController::class, 'create'])->name('rations.create');
    Route::post('/cycles/{cycle}/rations/review', [RationController::class, 'review'])->name('rations.review');
    Route::get('/cycles/{cycle}/rations/review/{token}', [RationController::class, 'showReview'])->name('rations.review.show');
    Route::post('/cycles/{cycle}/rations', [RationController::class, 'store'])->name('rations.store');
});
```

### Flow Mirrors Enrolment Scheduling
1. **Create** — Select item, enter ration factor, pick effective date (today+)
2. **Review** — Show demand impact per school (uses `DailyDemandService`), preview school-level daily demand changes
3. **Confirm** — Save dated ration, audit log

---

## Views

| View | Purpose |
|------|---------|
| `rations/index.blade.php` | List current rations per item with effective dates; "Set new ration" button |
| `rations/create.blade.php` | Form: item dropdown, ration factor input (decimal 0-1, step 0.001), effective date picker (min today), reason textarea |
| `rations/review.blade.php` | Show impact: before/after daily demand per school, total upazila demand change, flag fractional packet issues |
| `rations/review-show.blade.php` | Token-based review page (same as enrolment review) |

---

## Validation Rules (US3.1-AC2)

```php
$request->validate([
    'feeding_item_id' => ['required', 'exists:feeding_items,id'],
    'ration_factor' => ['required', 'numeric', 'gt:0', 'lte:1', 'regex:/^\d+(\.\d{1,3})?$/'],
    'effective_on' => ['required', 'date', 'after_or_equal:today'],
    'reason' => ['required', 'string', 'min:3', 'max:500'],
]);

// Custom: ration_factor must produce whole units per student for supplied items
// i.e., round(pupil_count * ration_factor) must yield integer that divides evenly
// into packet/piece counts for the item's supply_days pattern
```

---

## Audit Logging
- Action: `ration_set`
- Details: `feeding_cycle_id`, `feeding_item_id`, `ration_factor`, `effective_on`, `reason`, `actor_id`

---

## Test Coverage

1. **Unit** — `ItemRationService`: resolution logic (latest effective, fallback to cycle, null handling)
2. **Unit** — `CycleDemandService`: per-item demand uses item ration, falls back to cycle
3. **Feature** — `RationSettingTest`: create → review → confirm flow; validation rejects negative/past/fractional
4. **Feature** — `DailyDemandTest`: items without supply pattern, holidays, non-participating schools return 0/unknown (US3.1-AC3)
5. **Feature** — `GpsfpReconciliationTest`: September setup reconciles to source files (US3.1-AC4)

---

## Migration Path

1. Add `feeding_item_rations` table (migration)
2. Seed current cycle's `ration_factor` (0.900) as first dated record for all 3 food items with `effective_on = cycle.starts_on`
3. Deploy service/controller/views
4. Admin can now add future-dated ration changes

---

## Out of Scope
- Fractional packet/piece demand surfacing in reports (handled by existing `unconfiguredItems` logic)
- UI for Field Staff to see rations (they only see daily demand quantities)
- Bulk import of ration schedules

---

## Validation Commands
```bash
php artisan test --filter=Ration
php artisan test --filter=DailyDemand
php artisan test --filter=GpsfpReconciliation
bun run build
php artisan test
```