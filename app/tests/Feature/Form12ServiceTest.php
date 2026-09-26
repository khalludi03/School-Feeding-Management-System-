<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\DeliveryReceiptItemAllocation;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\User;
use App\Services\Form12Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Form12ServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 6, 15));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function test_ac1_june_baseline_opening_stock_is_zero(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $data = (new Form12Service)->forSchoolMonth($school, 2026, 6);

        $this->assertTrue($data['is_baseline']);
        foreach ($data['line_items'] as $li) {
            $this->assertSame(0, $li['opening'], 'opening stock should be 0 for baseline period');
        }
    }

    public function test_ac2_later_period_opening_equals_previous_closing(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'JUN001');
        $this->recordAllocation($school, $items, '2026-06-10', ['banana_bread' => 80], chalanDate: '2026-06-02');

        $data = (new Form12Service)->forSchoolMonth($school, 2026, 6);
        $bunJune = collect($data['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(0, $bunJune['opening']);
        $this->assertSame(100, $bunJune['receipts']);
        $this->assertSame(80, $bunJune['distribution']);
        $this->assertSame(20, $bunJune['closing']);

        $dataJuly = (new Form12Service)->forSchoolMonth($school, 2026, 7);
        $bunJuly = collect($dataJuly['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(20, $bunJuly['opening'], 'July opening should equal June closing of 20');
    }

    public function test_ac3_food_received_for_future_date_stays_in_closing_stock(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $this->recordReceipt($school, $items, '2026-08-25', ['banana_bread' => 200], chalan: 'AUG001', chalanDate: '2026-08-25');
        $this->recordAllocation($school, $items, '2026-09-05', ['banana_bread' => 200], chalanDate: '2026-08-25');

        $dataAug = (new Form12Service)->forSchoolMonth($school, 2026, 8);
        $bunAug = collect($dataAug['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(200, $bunAug['receipts']);
        $this->assertSame(0, $bunAug['distribution'], 'no distribution in August');
        $this->assertSame(200, $bunAug['closing'], 'food stays in August closing stock');

        $dataSep = (new Form12Service)->forSchoolMonth($school, 2026, 9);
        $bunSep = collect($dataSep['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(200, $bunSep['opening'], 'September opening = August closing');
        $this->assertSame(0, $bunSep['receipts']);
        $this->assertSame(200, $bunSep['distribution'], 'distribution in September');
        $this->assertSame(0, $bunSep['closing']);
    }

    public function test_ac4_allocation_becomes_derived_distribution(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'T001');
        $this->recordAllocation($school, $items, '2026-06-10', ['banana_bread' => 100], chalanDate: '2026-06-02');

        $data = (new Form12Service)->forSchoolMonth($school, 2026, 6);
        $bun = collect($data['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');

        $this->assertSame(100, $bun['distribution'], 'distribution is automatically derived from allocation records');
        $this->assertSame(0, $bun['closing']);
    }

    public function test_closing_equals_opening_plus_receipts_minus_distribution(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 50], chalan: 'CL001');
        $this->recordReceipt($school, $items, '2026-06-10', ['banana_bread' => 30], chalan: 'CL002');
        $this->recordAllocation($school, $items, '2026-06-15', ['banana_bread' => 60], chalanDate: '2026-06-03');

        $data = (new Form12Service)->forSchoolMonth($school, 2026, 6);
        $bun = collect($data['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');

        $expectedClosing = 0 + (50 + 30) - 60;
        $this->assertSame($expectedClosing, $bun['closing']);
    }

    public function test_returns_correct_structure(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $data = (new Form12Service)->forSchoolMonth($school, 2026, 6);

        $this->assertArrayHasKey('school', $data);
        $this->assertArrayHasKey('year', $data);
        $this->assertArrayHasKey('month', $data);
        $this->assertArrayHasKey('line_items', $data);
        $this->assertArrayHasKey('is_baseline', $data);
        $this->assertArrayHasKey('is_partial', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('items', $data);
    }

    public function test_multiple_items_tracked_separately(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 100, 'boiled_egg' => 50], chalan: 'MULTI01');
        $this->recordAllocation($school, $items, '2026-06-10', ['banana_bread' => 100, 'boiled_egg' => 50], chalanDate: '2026-06-02');

        $data = (new Form12Service)->forSchoolMonth($school, 2026, 6);

        $bun = collect($data['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $egg = collect($data['line_items'])->first(fn ($li) => $li['key'] === 'boiled_egg');

        $this->assertSame(100, $bun['receipts']);
        $this->assertSame(50, $egg['receipts']);
        $this->assertSame(0, $bun['closing']);
        $this->assertSame(0, $egg['closing']);
    }

    private function withCycleAndSchool(): array
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
        ]);

        $school = $this->participatingSchool($cycle);

        $bread = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'banana_bread', 'name' => 'বনরুটি', 'unit' => 'packet', 'weight_grams' => 120, 'sort_order' => 1]);
        $egg = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'boiled_egg', 'name' => 'সিদ্ধ ডিম', 'unit' => 'piece', 'weight_grams' => 60, 'sort_order' => 2]);
        $banana = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'banana', 'name' => 'কলা', 'unit' => 'piece', 'weight_grams' => 100, 'sort_order' => 3]);

        return [$cycle, $school, [$bread, $egg, $banana]];
    }

    private function participatingSchool(FeedingCycle $cycle): School
    {
        $school = School::factory()->create([
            'code' => 'F12TEST-'.uniqid(),
            'bangla_name' => 'টেস্ট-ফর্ম-১২-স্কুল',
            'district' => 'চট্টগ্রাম',
            'upazila' => 'আনোয়ারা',
        ]);
        $school->participationPeriods()->create(['starts_on' => $cycle->starts_on->toDateString()]);

        return $school->refresh();
    }

    private function recordReceipt(School $school, array $items, string $date, array $quantities, ?string $chalan = null, ?string $chalanDate = null): DeliveryReceipt
    {
        $staff = User::factory()->create(['role' => 'field_staff']);

        $receipt = DeliveryReceipt::factory()->create([
            'school_id' => $school->id,
            'delivery_date' => $date,
            'chalan_number' => $chalan ?? '99999',
            'chalan_date' => $chalanDate ?? $date,
            'entered_by' => $staff->id,
            'responsible_by' => $staff->id,
        ]);

        foreach ($items as $item) {
            $qty = $quantities[$item->item_key] ?? 0;
            DeliveryReceiptItem::factory()->create([
                'delivery_receipt_id' => $receipt->id,
                'feeding_item_id' => $item->id,
                'delivered_quantity' => $qty,
            ]);
        }

        return $receipt->refresh();
    }

    private function recordAllocation(School $school, array $items, string $allocationDate, array $quantities, string $chalanDate): void
    {
        $receipt = DeliveryReceipt::query()
            ->where('school_id', $school->id)
            ->whereDate('chalan_date', $chalanDate)
            ->first();

        if (! $receipt) {
            return;
        }

        foreach ($items as $item) {
            $qty = $quantities[$item->item_key] ?? 0;
            if ($qty <= 0) {
                continue;
            }

            $receiptItem = DeliveryReceiptItem::query()
                ->where('delivery_receipt_id', $receipt->id)
                ->where('feeding_item_id', $item->id)
                ->first();

            if (! $receiptItem) {
                continue;
            }

            DeliveryReceiptItemAllocation::factory()->create([
                'delivery_receipt_item_id' => $receiptItem->id,
                'allocation_date' => $allocationDate,
                'allocated_quantity' => $qty,
            ]);
        }
    }
}
