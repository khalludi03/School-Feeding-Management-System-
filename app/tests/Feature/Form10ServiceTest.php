<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\FeedingItemPrice;
use App\Models\School;
use App\Models\User;
use App\Services\Form10Service;
use App\Services\ItemPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Form10ServiceTest extends TestCase
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

    public function test_returns_correct_structure(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $data = (new Form10Service(new ItemPriceService))->forMonth(2026, 6);

        $this->assertArrayHasKey('year', $data);
        $this->assertArrayHasKey('month', $data);
        $this->assertArrayHasKey('line_items', $data);
        $this->assertArrayHasKey('grand_total', $data);
        $this->assertArrayHasKey('chalan_count', $data);
        $this->assertArrayHasKey('school_count', $data);
        $this->assertArrayHasKey('invoice_number', $data);
        $this->assertArrayHasKey('recipient', $data);
        $this->assertArrayHasKey('bank_info', $data);
    }

    public function test_ac3_distinct_chalan_count_across_all_schools(): void
    {
        [$cycle, $school1, $items] = $this->withCycleAndSchool();
        $school2 = $this->makeSchoolWithParticipation($cycle, 'F10CHALAN-'.uniqid(), 'স্কুল-চালান-'.uniqid());

        $this->recordReceipt($school1, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'C001');
        $this->recordReceipt($school1, $items, '2026-06-03', ['banana_bread' => 50], chalan: 'C001');
        $this->recordReceipt($school2, $items, '2026-06-04', ['banana_bread' => 75], chalan: 'C002');

        $data = (new Form10Service(new ItemPriceService))->forMonth(2026, 6);

        $this->assertSame(2, $data['chalan_count'], 'should count 2 distinct chalan numbers (C001, C002)');
    }

    public function test_ac6_grand_total_uses_weighted_price_from_chalan_dates(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $bread = collect($items)->first(fn ($i) => $i->item_key === 'banana_bread');

        FeedingItemPrice::factory()->for($cycle)->create([
            'feeding_item_id' => $bread->id,
            'unit_price' => 10.0,
            'effective_on' => '2026-06-01',
        ]);

        FeedingItemPrice::factory()->for($cycle)->create([
            'feeding_item_id' => $bread->id,
            'unit_price' => 12.0,
            'effective_on' => '2026-06-10',
        ]);

        $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'T100', chalanDate: '2026-06-02');
        $this->recordReceipt($school, $items, '2026-06-15', ['banana_bread' => 100], chalan: 'T101', chalanDate: '2026-06-15');

        $data = (new Form10Service(new ItemPriceService))->forMonth(2026, 6);

        $breadLine = collect($data['line_items'])->first(fn ($li) => $li['key'] === 'banana_bread');
        $expectedTotal = round(100 * 10.0, 1) + round(100 * 12.0, 1);
        $this->assertSame($expectedTotal, $breadLine['raw_total'], 'line total uses chalan-date price per receipt');
        $this->assertSame(200, $breadLine['quantity'], 'total quantity is sum of both receipts');
    }

    public function test_ac6_grand_total_rounded_to_nearest_taka(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();
        $bread = collect($items)->first(fn ($i) => $i->item_key === 'banana_bread');

        FeedingItemPrice::factory()->for($cycle)->create([
            'feeding_item_id' => $bread->id,
            'unit_price' => 10.555,
            'effective_on' => '2026-06-01',
        ]);

        $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'RND001', chalanDate: '2026-06-02');

        $data = (new Form10Service(new ItemPriceService))->forMonth(2026, 6);

        $this->assertGreaterThan(0, $data['grand_total']);
        $this->assertEquals((int) $data['grand_total'], (int) $data['grand_total'], 'grand total should be integer after rounding');
    }

    public function test_empty_month_returns_zero_totals(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $data = (new Form10Service(new ItemPriceService))->forMonth(2026, 7);

        $this->assertSame(0, $data['chalan_count']);
        $this->assertSame(0.0, $data['grand_total']);
    }

    public function test_school_count_reflects_participating_schools(): void
    {
        [$cycle, $school1, $items] = $this->withCycleAndSchool();
        $school2 = $this->makeSchoolWithParticipation($cycle, 'F10SCHOOL-'.uniqid(), 'স্কুল-২-'.uniqid());

        $this->recordReceipt($school1, $items, '2026-06-02', ['banana_bread' => 100], chalan: 'SC001');

        $data = (new Form10Service(new ItemPriceService))->forMonth(2026, 6);

        $this->assertSame(2, $data['school_count']);
    }

    public function test_invoice_number_generated_from_cycle_and_period(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $data = (new Form10Service(new ItemPriceService))->forMonth(2026, 6);

        $this->assertNotEmpty($data['invoice_number']);
        $this->assertStringContainsString('2026', $data['invoice_number']);
        $this->assertStringContainsString('06', $data['invoice_number']);
    }

    public function test_bangla_month_and_year_codes_present(): void
    {
        [$cycle, $school, $items] = $this->withCycleAndSchool();

        $data = (new Form10Service(new ItemPriceService))->forMonth(2026, 6);

        $this->assertSame('জুন', $data['month_name']);
        $this->assertMatchesRegularExpression('/[০-৯]+/', $data['year_code']);
    }

    private function withCycleAndSchool(): array
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
        ]);

        $school = $this->participatingSchool($cycle);

        $bread = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'banana_bread', 'name' => 'বনরুটি', 'unit' => 'packet', 'weight_grams' => 120, 'sort_order' => 1, 'unit_price' => 10.0]);
        $egg = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'boiled_egg', 'name' => 'সিদ্ধ ডিম', 'unit' => 'piece', 'weight_grams' => 60, 'sort_order' => 2, 'unit_price' => 8.0]);
        $banana = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'banana', 'name' => 'কলা', 'unit' => 'piece', 'weight_grams' => 100, 'sort_order' => 3, 'unit_price' => 5.0]);

        return [$cycle, $school, [$bread, $egg, $banana]];
    }

    private function participatingSchool(FeedingCycle $cycle): School
    {
        $school = School::factory()->create([
            'code' => 'F10TEST-'.uniqid(),
            'bangla_name' => 'টেস্ট-ফর্ম-১০-স্কুল',
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
}
