<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\DeliveryReceiptItemAllocation;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\FeedingItemPrice;
use App\Models\School;
use App\Models\User;
use App\Services\Form10Service;
use App\Services\Form12Service;
use App\Services\Form4Service;
use App\Services\Form7Service;
use App\Services\ItemPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportCorrectionTest extends TestCase
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

    public function test_ac1_correction_propagates_to_form4(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $receipt = $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 100], chalan: 'C001');

        $form4 = new Form4Service;
        $before = $form4->forSchoolMonth($school, 2026, 6);
        $this->assertSame(100, $before['total_qty']['banana_bread']);

        DeliveryReceiptItem::query()
            ->where('delivery_receipt_id', $receipt->id)
            ->where('feeding_item_id', $items[0]->id)
            ->update(['delivered_quantity' => 150]);

        $after = $form4->forSchoolMonth($school, 2026, 6);
        $this->assertSame(150, $after['total_qty']['banana_bread']);
    }

    public function test_ac1_correction_propagates_to_form7(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $receipt = $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 100], chalan: 'C001');

        $form7 = new Form7Service;
        $before = $form7->forMonth(2026, 6);
        $row = collect($before['schools'])->first(fn ($r) => $r['school']->id === $school->id);
        $this->assertSame(100, $row['quantities_raw']['banana_bread']);

        DeliveryReceiptItem::query()
            ->where('delivery_receipt_id', $receipt->id)
            ->where('feeding_item_id', $items[0]->id)
            ->update(['delivered_quantity' => 150]);

        $after = $form7->forMonth(2026, 6);
        $rowAfter = collect($after['schools'])->first(fn ($r) => $r['school']->id === $school->id);
        $this->assertSame(150, $rowAfter['quantities_raw']['banana_bread']);
    }

    public function test_ac1_correction_propagates_to_form10(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $receipt = $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 100], chalan: 'C001', chalanDate: '2026-06-02');
        $this->seedPrice($cycle, $items[0]);

        $form10 = new Form10Service(new ItemPriceService);
        $before = $form10->forMonth(2026, 6);
        $bunBefore = collect($before['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(100, $bunBefore['quantity']);

        DeliveryReceiptItem::query()
            ->where('delivery_receipt_id', $receipt->id)
            ->where('feeding_item_id', $items[0]->id)
            ->update(['delivered_quantity' => 150]);

        $after = $form10->forMonth(2026, 6);
        $bunAfter = collect($after['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(150, $bunAfter['quantity']);
    }

    public function test_ac1_correction_propagates_to_form12(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $receipt = $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 100], chalan: 'C001');
        $this->recordAllocation($school, $items, '2026-06-05', ['banana_bread' => 80], chalanDate: '2026-06-03');

        $form12 = new Form12Service;
        $before = $form12->forSchoolMonth($school, 2026, 6);
        $bunBefore = collect($before['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(100, $bunBefore['receipts']);
        $this->assertSame(20, $bunBefore['closing']);

        DeliveryReceiptItem::query()
            ->where('delivery_receipt_id', $receipt->id)
            ->where('feeding_item_id', $items[0]->id)
            ->update(['delivered_quantity' => 150]);

        $after = $form12->forSchoolMonth($school, 2026, 6);
        $bunAfter = collect($after['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(150, $bunAfter['receipts']);
        $this->assertSame(70, $bunAfter['closing']);
    }

    public function test_ac2_correction_affects_subsequent_month_opening_balance(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $juneReceipt = $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 100], chalan: 'JUN01');
        $this->recordAllocation($school, $items, '2026-06-05', ['banana_bread' => 80], chalanDate: '2026-06-03');

        $form12 = new Form12Service;
        $june = $form12->forSchoolMonth($school, 2026, 6);
        $bunJune = collect($june['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(20, $bunJune['closing']);

        $july = $form12->forSchoolMonth($school, 2026, 7);
        $bunJuly = collect($july['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(20, $bunJuly['opening'], 'July opening should carry forward June closing of 20');

        DeliveryReceiptItem::query()
            ->where('delivery_receipt_id', $juneReceipt->id)
            ->where('feeding_item_id', $items[0]->id)
            ->update(['delivered_quantity' => 130]);

        $receiptItem = DeliveryReceiptItem::query()
            ->where('delivery_receipt_id', $juneReceipt->id)
            ->where('feeding_item_id', $items[0]->id)
            ->first();

        DeliveryReceiptItemAllocation::query()
            ->where('delivery_receipt_item_id', $receiptItem->id)
            ->update(['allocated_quantity' => 104]);

        $julyAfter = $form12->forSchoolMonth($school, 2026, 7);
        $bunJulyAfter = collect($julyAfter['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(26, $bunJulyAfter['opening'], 'July opening should reflect corrected June closing of 26');
    }

    public function test_ac4_generating_same_report_twice_produces_identical_output(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 100], chalan: 'C001');

        $form4 = new Form4Service;
        $first = $form4->forSchoolMonth($school, 2026, 6);
        $second = $form4->forSchoolMonth($school, 2026, 6);

        $this->assertSame($first['total_qty']['banana_bread'], $second['total_qty']['banana_bread']);
        $this->assertSame(json_encode($first), json_encode($second), 'Report output should be identical when data is unchanged');
    }

    public function test_ac4_changed_data_produces_different_report_output(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $receipt = $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 100], chalan: 'C001');

        $form4 = new Form4Service;
        $before = $form4->forSchoolMonth($school, 2026, 6);

        DeliveryReceiptItem::query()
            ->where('delivery_receipt_id', $receipt->id)
            ->where('feeding_item_id', $items[0]->id)
            ->update(['delivered_quantity' => 200]);

        $after = $form4->forSchoolMonth($school, 2026, 6);

        $this->assertNotEquals(
            $before['total_qty']['banana_bread'],
            $after['total_qty']['banana_bread'],
            'Report output must change when source data changes'
        );
        $this->assertSame(100, $before['total_qty']['banana_bread']);
        $this->assertSame(200, $after['total_qty']['banana_bread']);
    }

    public function test_ac4_no_caching_between_receipt_and_report(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $receipt = $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 100], chalan: 'C001');

        $form7 = new Form7Service;
        $r1 = $form7->forMonth(2026, 6);
        $row1 = collect($r1['schools'])->first(fn ($r) => $r['school']->id === $school->id);

        DeliveryReceiptItem::query()
            ->where('delivery_receipt_id', $receipt->id)
            ->update(['delivered_quantity' => 77]);

        $r2 = $form7->forMonth(2026, 6);
        $row2 = collect($r2['schools'])->first(fn ($r) => $r['school']->id === $school->id);

        $this->assertSame(100, $row1['quantities_raw']['banana_bread']);
        $this->assertSame(77, $row2['quantities_raw']['banana_bread']);
    }

    private function withCycleAndSchool(): array
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
        ]);

        $school = School::factory()->create([
            'code' => 'RCTEST-'.uniqid(),
            'bangla_name' => 'টেস্ট-রিপোর্ট-সংশোধন-স্কুল',
            'district' => 'চট্টগ্রাম',
            'upazila' => 'আনোয়ারা',
        ]);
        $school->participationPeriods()->create(['starts_on' => $cycle->starts_on->toDateString()]);

        $bread = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'banana_bread', 'name' => 'বনরুটি', 'unit' => 'packet', 'weight_grams' => 120, 'sort_order' => 1]);
        $egg = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'boiled_egg', 'name' => 'সিদ্ধ ডিম', 'unit' => 'piece', 'weight_grams' => 60, 'sort_order' => 2]);
        $banana = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'banana', 'name' => 'কলা', 'unit' => 'piece', 'weight_grams' => 100, 'sort_order' => 3]);

        return [$cycle, $school, [$bread, $egg, $banana]];
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

    private function seedPrice(FeedingCycle $cycle, FeedingItem $item): void
    {
        FeedingItemPrice::factory()->create([
            'feeding_cycle_id' => $cycle->id,
            'feeding_item_id' => $item->id,
            'unit_price' => 10.00,
            'effective_on' => $cycle->starts_on->toDateString(),
        ]);
    }
}
