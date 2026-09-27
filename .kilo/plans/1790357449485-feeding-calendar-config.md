# US4.1, US4.2, US4.3 - Feeding Calendar & Conflict Resolution Implementation Plan

## Overview
Extend the existing calendar system with date-level item scheduling, holiday conflict detection, and conflict resolution workflow. All changes are cycle-scoped.

---

## Data Model Changes

### New Table: `feeding_date_item_schedules`
```sql
CREATE TABLE feeding_date_item_schedules (
    id BIGINT PRIMARY KEY,
    feeding_cycle_id BIGINT NOT NULL REFERENCES feeding_cycles(id) ON DELETE CASCADE,
    feeding_item_id BIGINT NOT NULL REFERENCES feeding_items(id) ON DELETE CASCADE,
    schedule_date DATE NOT NULL,
    is_scheduled BOOLEAN NOT NULL DEFAULT TRUE,
    source VARCHAR(30) NOT NULL DEFAULT 'assumed', -- 'work_order' | 'assumed'
    created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (feeding_cycle_id, feeding_item_id, schedule_date)
);
```

### Extended: `NonWorkingDay` (already exists, global)
- No schema change needed. Conflict checks will query DeliveryReceipt.

### Indexes
- `feeding_date_item_schedules`: unique on (feeding_cycle_id, feeding_item_id, schedule_date)
- Index on (feeding_cycle_id, schedule_date) for month queries

---

## Service Layer

### 1. `DateItemScheduleService`
```php
class DateItemScheduleService
{
    // Get scheduled items for a specific date in a cycle
    public function scheduledItemsOn(FeedingCycle $cycle, CarbonInterface $date): Collection;

    // Check if item is scheduled on date (date-level > weekday pattern)
    // Returns: true | false | null (unconfigured)
    public function isSuppliedOnDate(FeedingItem $item, CarbonInterface $date): ?bool;

    // Get all date-level configs for a month
    public function monthConfigs(FeedingCycle $cycle, Carbon $month): Collection;

    // Set/unset item for a date
    public function setItemForDate(FeedingCycle $cycle, FeedingItem $item, Carbon $date, bool $scheduled, string $source, int $userId): void;

    // Bulk upsert for a month (used by calendar UI)
    public function upsertMonth(FeedingCycle $cycle, array $dateItems, string $source, int $userId): void;

    // Auto-restore from weekday pattern when holiday removed
    public function restoreFromWeekdayPattern(FeedingCycle $cycle, Carbon $date): void;
}
```

### 2. `CalendarConflictService`
```php
class CalendarConflictService
{
    // Check if date can be marked as non-working
    // Returns: { blocked: bool, conflicts: [ { date, school, receipt_id, entered_by } ] }
    public function checkNonWorkingConflict(CarbonInterface $date): array;

    // Check if removing item from date conflicts with allocations
    public function checkItemRemovalConflict(FeedingCycle $cycle, FeedingItem $item, CarbonInterface $date): array;

    // Get all conflicts for a proposed calendar change
    public function getConflictsForChange(/* change spec */): array;
}
```

### 3. `ItemSupplyPattern` (extend)
- `isSuppliedOn()` now checks date-level config first, then weekday pattern
- Return `null` only when NEITHER date-level nor weekday pattern configured

---

## Controller Updates

### `CalendarController`
**New methods:**
- `monthConfig(FeedingCycle $cycle, Carbon $month)` - View with item checkboxes per date
- `updateMonthConfig(Request $request, FeedingCycle $cycle, Carbon $month)` - Bulk save date-item configs
- `store` - Add conflict check before creating NonWorkingDay
- `destroy` - Auto-restore date-item schedule from weekday pattern when holiday removed

**Conflict flow:**
1. Admin submits change (mark holiday / remove item / change schedule)
2. `CalendarConflictService` checks for DeliveryReceipt conflicts
3. If conflicts: redirect to conflict review page with list of affected records
4. If no conflicts: save change

### New: `CalendarConflictController`
- `show(Request $request, FeedingCycle $cycle)` - List conflicts with school, date, receipt, field staff
- Links to inspect records (read-only for Admin)

---

## Views

### 1. `calendar/month-config.blade.php` (replaces/extends index)
- Month grid with per-date item checkboxes (bun/egg/banana)
- Each cell shows: day number, holiday badge, item checkboxes
- Source indicator (work_order/assumed) per item per date
- "Setup incomplete" badge on dates with unconfigured items
- "Review conflicts" button when conflicts exist
- Save button per month (bulk upsert)

### 2. `calendar/conflicts.blade.php`
- Table: Date | School | Record Type | Field Staff | Action (view only)
- "Resolve conflicts" guidance text

---

## Demand Integration (US4.1 AC4)

### `ItemSupplyPattern::isSuppliedOn()` updated logic:
```php
public function isSuppliedOn(FeedingItem $item, CarbonInterface $date): ?bool
{
    // 1. Check date-level override
    $dateConfig = DateItemSchedule::where(...)->first();
    if ($dateConfig !== null) {
        return $dateConfig->is_scheduled;
    }
    // 2. Fallback to weekday pattern
    $weekdays = $this->weekdaysFor($item);
    if ($weekdays === null) {
        return null; // unconfigured
    }
    return in_array($date->dayOfWeek, $weekdays, true);
}
```

### `DailyDemandService::forSchool()` / `explainForSchool()`:
- Use updated `ItemSupplyPattern::isSuppliedOn()`
- "Setup incomplete" flag when any item returns `null` for a working date

---

## Tests Required

### Unit/Feature
- `DateItemScheduleServiceTest`: CRUD, month bulk, fallback logic
- `CalendarConflictServiceTest`: DeliveryReceipt blocks holiday, blocks item removal
- `CalendarControllerTest`:
  - Month config view loads with checkboxes
  - Bulk save creates date-level configs
  - Mark holiday blocked when receipt exists
  - Unmark holiday restores weekday pattern
  - Conflict review page shows affected records
- `ItemSupplyPatternTest`: date-level > weekday > null precedence
- `DemandExplanationTest` (extend): shows setup incomplete for unconfigured dates

---

## Migration Plan
1. Create `feeding_date_item_schedules` migration
2. Create `DateItemSchedule` model + factory
3. Create `DateItemScheduleService`
4. Update `ItemSupplyPattern::isSuppliedOn()`
5. Create `CalendarConflictService`
6. Update `CalendarController` + add `CalendarConflictController`
7. Create/update views
8. Add routes
9. Write tests
10. Run full suite

---

## Open Questions (Resolved)
- ✅ Date-level schedule table (new table)
- ✅ Source tracking per-date (work_order/assumed)
- ✅ Cycle-scoped date schedules
- ✅ Conflict scope: DeliveryReceipt only
- ✅ Setup incomplete: all demand consumers
- ✅ Unmark holiday: auto-restore from weekday pattern