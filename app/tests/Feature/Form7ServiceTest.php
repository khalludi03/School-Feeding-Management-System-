<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\DeliveryReceiptItemZeroConfirmation;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\User;
use App\Services\Form7Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Form7ServiceTest extends TestCase
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

    public function test_ac1_receipts_with_positive_qty_show_chalan_count_and_quantity(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 172, 'boiled_egg' => 172], chalan: '10001');
        $this->recordReceipt($school, $items, '2026-06-07', ['banana_bread' => 172], chalan: '10002');

        $data = (new Form7Service)->forMonth(2026, 6);

        $row = $this->schoolRow($data, $school);
        $this->assertSame(2, $row['chalan_counts_raw']['banana_bread']);
        $this->assertSame(344, $row['quantities_raw']['banana_bread']);
        $this->assertSame(1, $row['chalan_counts_raw']['boiled_egg']);
        $this->assertSame(172, $row['quantities_raw']['boiled_egg']);
    }

    public function test_ac2_single_chalan_with_buns_and_eggs_counts_once_per_item(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $receipt = $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 172, 'boiled_egg' => 172, 'banana' => 0], chalan: '20001');

        $data = (new Form7Service)->forMonth(2026, 6);

        $row = $this->schoolRow($data, $school);
        $this->assertSame(1, $row['chalan_counts_raw']['banana_bread'], 'bun chalan counted once');
        $this->assertSame(1, $row['chalan_counts_raw']['boiled_egg'], 'egg chalan counted once');
        $this->assertSame(0, $row['chalan_counts_raw']['banana'], 'banana with 0 qty not counted');
    }

    public function test_ac4_zero_confirmations_add_no_chalan_count_or_quantity(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 172], chalan: '10001');

        $eggItem = collect($items)->first(fn ($i) => $i->item_key === 'boiled_egg');
        DeliveryReceiptItemZeroConfirmation::factory()->create([
            'school_id' => $school->id,
            'feeding_item_id' => $eggItem->id,
            'date' => '2026-06-03',
        ]);

        $data = (new Form7Service)->forMonth(2026, 6);

        $row = $this->schoolRow($data, $school);
        $this->assertSame(1, $row['chalan_counts_raw']['banana_bread']);
        $this->assertSame(0, $row['chalan_counts_raw']['boiled_egg']);
        $this->assertSame(0, $row['quantities_raw']['boiled_egg']);
    }

    public function test_school_with_no_receipts_still_appears_with_zero_counts(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $data = (new Form7Service)->forMonth(2026, 6);

        $row = $this->schoolRow($data, $school);
        $this->assertSame(0, $row['chalan_counts_raw']['banana_bread']);
        $this->assertSame(0, $row['quantities_raw']['banana_bread']);
    }

    public function test_grand_totals_sum_all_schools(): void
    {
        [$cycle, $school1, $items] = $this->withCycleAndSchool();
        $school2 = $this->makeSchoolWithParticipation($cycle, 'F7GRAND-'.uniqid(), 'স্কুল-গ্র্যান্ড-'.uniqid());

        $this->recordReceipt($school1, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'GA001');
        $this->recordReceipt($school2, $items, '2026-06-03', ['banana_bread' => 200], chalan: 'GA002');

        $data = (new Form7Service)->forMonth(2026, 6);

        $foundSchools = collect($data['schools']);
        $row1 = $foundSchools->first(fn ($r) => $r['school']->id === $school1->id);
        $row2 = $foundSchools->first(fn ($r) => $r['school']->id === $school2->id);

        $this->assertNotNull($row1, 'school1 should be in results');
        $this->assertNotNull($row2, 'school2 should be in results');
        $this->assertSame(100, $row1['quantities_raw']['banana_bread']);
        $this->assertSame(200, $row2['quantities_raw']['banana_bread']);
        $this->assertSame(300, $row1['quantities_raw']['banana_bread'] + $row2['quantities_raw']['banana_bread']);
    }

    public function test_reconciles_with_form4_totals(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 172, 'boiled_egg' => 172], chalan: '10001');
        $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 100], chalan: '10002');

        $data = (new Form7Service)->forMonth(2026, 6);

        $row = $this->schoolRow($data, $school);
        $this->assertSame(272, $row['quantities_raw']['banana_bread']);
        $this->assertSame(172, $row['quantities_raw']['boiled_egg']);
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

    private function makeSchool(string $code, string $banglaName): School
    {
        return School::factory()->create([
            'code' => $code,
            'bangla_name' => $banglaName,
            'district' => 'চট্টগ্রাম',
            'upazila' => 'আনোয়ারা',
        ]);
    }

    private function participatingSchool(FeedingCycle $cycle): School
    {
        $school = $this->makeSchool('TEST-'.uniqid(), 'টেস্ট-স্কুল-'.uniqid());
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

    private function recordReceipt(School $school, array $items, string $date, array $quantities, ?string $chalan = null): DeliveryReceipt
    {
        $staff = User::factory()->create(['role' => 'field_staff']);

        $receipt = DeliveryReceipt::factory()->create([
            'school_id' => $school->id,
            'delivery_date' => $date,
            'chalan_number' => $chalan ?? '99999',
            'chalan_date' => $date,
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

    private function schoolRow(array $data, School $school): array
    {
        return collect($data['schools'])->first(fn ($r) => $r['school']->id === $school->id);
    }
}
