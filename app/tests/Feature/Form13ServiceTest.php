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
use App\Services\Form13Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Form13ServiceTest extends TestCase
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

    public function test_ac1_form13_totals_equal_sum_of_form12_school_figures(): void
    {
        [$cycle, $school1, $school2, $items] = $this->withTwoSchools();

        $this->recordReceipt($school1, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'S1C01');
        $this->recordReceipt($school2, $items, '2026-06-03', ['banana_bread' => 50], chalan: 'S2C01');
        $this->recordAllocation($school1, $items, '2026-06-05', ['banana_bread' => 80], '2026-06-02');
        $this->recordAllocation($school2, $items, '2026-06-06', ['banana_bread' => 40], '2026-06-03');

        $form12 = new Form12Service;
        $f12School1 = $form12->forSchoolMonth($school1, 2026, 6);
        $f12School2 = $form12->forSchoolMonth($school2, 2026, 6);

        $form13 = new Form13Service($form12);
        $data = $form13->forMonth(2026, 6);

        $bunLine = collect($data['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $f12BunSchool1 = collect($f12School1['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $f12BunSchool2 = collect($f12School2['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');

        $this->assertSame(
            ($f12BunSchool1['opening'] ?? 0) + ($f12BunSchool2['opening'] ?? 0),
            $bunLine['opening'],
            'Form13 opening = sum of Form12 openings'
        );
        $this->assertSame(
            ($f12BunSchool1['receipts'] ?? 0) + ($f12BunSchool2['receipts'] ?? 0),
            $bunLine['receipts'],
            'Form13 receipts = sum of Form12 receipts'
        );
        $this->assertSame(
            ($f12BunSchool1['distribution'] ?? 0) + ($f12BunSchool2['distribution'] ?? 0),
            $bunLine['distribution'],
            'Form13 distribution = sum of Form12 distributions'
        );
        $this->assertSame(
            ($f12BunSchool1['closing'] ?? 0) + ($f12BunSchool2['closing'] ?? 0),
            $bunLine['closing'],
            'Form13 closing = sum of Form12 closings'
        );
    }

    public function test_ac2_non_zero_carried_balance_remains_visible(): void
    {
        [$cycle, $school1, $school2, $items] = $this->withTwoSchools();

        $this->recordReceipt($school1, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'C001');
        $this->recordReceipt($school2, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'C002');
        $this->recordAllocation($school1, $items, '2026-06-05', ['banana_bread' => 60], '2026-06-02');
        $this->recordAllocation($school2, $items, '2026-06-06', ['banana_bread' => 40], '2026-06-02');

        $form13 = (new Form13Service(new Form12Service))->forMonth(2026, 6);
        $bunLine = collect($form13['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');

        $this->assertSame(200, $bunLine['receipts']);
        $this->assertSame(100, $bunLine['distribution']);
        $this->assertSame(100, $bunLine['closing'], 'closing balance is not forced to zero');
    }

    public function test_ac3_correction_recalculates_consistently(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $receipt = $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'CORR01');
        $allocation = $this->recordAllocation($school, $items, '2026-06-10', ['banana_bread' => 100], '2026-06-02');

        $form13Before = (new Form13Service(new Form12Service))->forMonth(2026, 6);
        $bunBefore = collect($form13Before['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(100, $bunBefore['receipts']);
        $this->assertSame(100, $bunBefore['distribution']);
        $this->assertSame(0, $bunBefore['closing']);

        DeliveryReceiptItem::query()
            ->where('delivery_receipt_id', $receipt->id)
            ->update(['delivered_quantity' => 150]);

        $allocation->update(['allocated_quantity' => 150]);

        $form13After = (new Form13Service(new Form12Service))->forMonth(2026, 6);
        $bunAfter = collect($form13After['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $this->assertSame(150, $bunAfter['receipts']);
        $this->assertSame(150, $bunAfter['distribution']);
        $this->assertSame(0, $bunAfter['closing']);
    }

    public function test_ac4_projection_and_incomplete_labels(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $form13May = (new Form13Service(new Form12Service))->forMonth(2026, 5);
        $this->assertFalse($form13May['has_projection'], 'May is past — no projection');

        $form13June = (new Form13Service(new Form12Service))->forMonth(2026, 6);
        $this->assertTrue($form13June['has_projection'], 'June is current month — partial period');
    }

    public function test_school_rows_match_form12_per_school(): void
    {
        [$cycle, $school1, $school2, $items] = $this->withTwoSchools();

        $this->recordReceipt($school1, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'ROW01');
        $this->recordReceipt($school2, $items, '2026-06-03', ['banana_bread' => 200], chalan: 'ROW02');

        $form13 = (new Form13Service(new Form12Service))->forMonth(2026, 6);

        $this->assertCount(2, $form13['school_rows']);
        $this->assertSame(2, $form13['school_count']);

        $row1 = collect($form13['school_rows'])->first(fn ($r) => $r['school']->id === $school1->id);
        $row2 = collect($form13['school_rows'])->first(fn ($r) => $r['school']->id === $school2->id);

        $this->assertNotNull($row1);
        $this->assertNotNull($row2);
        $this->assertSame(100, $row1['item_data']['banana_bread']['receipts'] ?? 0);
        $this->assertSame(200, $row2['item_data']['banana_bread']['receipts'] ?? 0);
    }

    public function test_returns_correct_structure(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $data = (new Form13Service(new Form12Service))->forMonth(2026, 6);

        $this->assertArrayHasKey('year', $data);
        $this->assertArrayHasKey('month', $data);
        $this->assertArrayHasKey('line_items', $data);
        $this->assertArrayHasKey('school_rows', $data);
        $this->assertArrayHasKey('school_count', $data);
        $this->assertArrayHasKey('grand_totals', $data);
        $this->assertArrayHasKey('has_projection', $data);
        $this->assertArrayHasKey('has_incomplete', $data);
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

    private function withTwoSchools(): array
    {
        [$cycle, $school1, $items] = $this->withCycleAndSchool();
        $school2 = $this->makeSchoolWithParticipation($cycle, 'F13SCHOOL2-'.uniqid(), 'স্কুল-২-ফর্ম-১৩');

        return [$cycle, $school1, $school2, $items];
    }

    private function participatingSchool(FeedingCycle $cycle): School
    {
        $school = School::factory()->create([
            'code' => 'F13TEST-'.uniqid(),
            'bangla_name' => 'টেস্ট-ফর্ম-১৩-স্কুল',
            'district' => 'চট্টগ্রাম',
            'upazila' => 'আনোয়ারা',
        ]);
        $school->participationPeriods()->create(['starts_on' => $cycle->starts_on->toDateString()]);

        return $school->refresh();
    }

    private function makeSchoolWithParticipation(FeedingCycle $cycle, string $code, string $banglaName): School
    {
        $school = School::factory()->create([
            'code' => $code,
            'bangla_name' => $banglaName,
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

    private function recordAllocation(School $school, array $items, string $allocationDate, array $quantities, string $chalanDate): ?DeliveryReceiptItemAllocation
    {
        $receipt = DeliveryReceipt::query()
            ->where('school_id', $school->id)
            ->whereDate('chalan_date', $chalanDate)
            ->first();

        if (! $receipt) {
            return null;
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

            return DeliveryReceiptItemAllocation::factory()->create([
                'delivery_receipt_item_id' => $receiptItem->id,
                'allocation_date' => $allocationDate,
                'allocated_quantity' => $qty,
            ]);
        }

        return null;
    }
}
