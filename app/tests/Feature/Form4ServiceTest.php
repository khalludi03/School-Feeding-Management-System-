<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\DeliveryReceiptItemAllocation;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\User;
use App\Services\Form4Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Form4ServiceTest extends TestCase
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

    public function test_empty_month_returns_all_calendar_days(): void
    {
        $school = $this->makeSchool();
        $data = (new Form4Service)->forSchoolMonth($school, 2026, 6);

        $this->assertSame(30, count($data['days']));
        $this->assertSame('জুন', $data['month_name']);
        $this->assertSame('২০২৬', $data['year_bangla']);

        foreach ($data['days'] as $day) {
            $this->assertSame('-', $day['chalan_numbers']);
            $this->assertSame('০', $day['quantities']['banana_bread']);
            $this->assertSame('০', $day['quantities']['boiled_egg']);
            $this->assertSame('০', $day['quantities']['banana']);
            $this->assertSame('০', $day['biscuit_qty']);
            $this->assertSame('০', $day['milk_qty']);
            $this->assertTrue($day['is_empty']);
        }
    }

    public function test_single_receipt_day_shows_chalan_and_bangla_quantity(): void
    {
        [$school, $items] = $this->withCycleAndSchool();
        $receipt = $this->recordReceipt($school, $items, '2026-06-02', [
            'banana_bread' => 172,
            'boiled_egg' => 172,
        ]);

        $data = (new Form4Service)->forSchoolMonth($school, 2026, 6);

        $day2 = $data['days'][1];
        $this->assertFalse($day2['is_empty']);
        $this->assertSame((string) $receipt->chalan_number, $day2['chalan_numbers']);
        $this->assertSame('১৭২', $day2['quantities']['banana_bread']);
        $this->assertSame('১৭২', $day2['quantities']['boiled_egg']);
    }

    public function test_multiple_chalans_same_day_sums_quantities_not_comma_separates(): void
    {
        [$school, $items] = $this->withCycleAndSchool();
        $this->recordReceipt($school, $items, '2026-06-07', ['banana_bread' => 172, 'boiled_egg' => 172], chalan: '15510');
        $this->recordReceipt($school, $items, '2026-06-07', ['banana_bread' => 185, 'boiled_egg' => 185], chalan: '16237');

        $data = (new Form4Service)->forSchoolMonth($school, 2026, 6);

        $day7 = $data['days'][6];
        $this->assertFalse($day7['is_empty']);
        $this->assertSame('15510, 16237', $day7['chalan_numbers']);
        $this->assertSame('৩৫৭', $day7['quantities']['banana_bread']);
        $this->assertSame('৩৫৭', $day7['quantities']['boiled_egg']);
    }

    public function test_banana_only_day_and_empty_days_show_zero_not_dash(): void
    {
        [$school, $items] = $this->withCycleAndSchool();
        $this->recordReceipt($school, $items, '2026-06-08', ['banana' => 172], chalan: '14329');

        $data = (new Form4Service)->forSchoolMonth($school, 2026, 6);

        $day8 = $data['days'][7];
        $this->assertFalse($day8['is_empty']);
        $this->assertSame('14329', $day8['chalan_numbers']);
        $this->assertSame('০', $day8['quantities']['banana_bread']);
        $this->assertSame('০', $day8['quantities']['boiled_egg']);
        $this->assertSame('১৭২', $day8['quantities']['banana']);

        $day7 = $data['days'][6];
        $this->assertTrue($day7['is_empty']);
        $this->assertSame('০', $day7['quantities']['banana_bread']);
        $this->assertSame('০', $day7['quantities']['banana']);
    }

    public function test_totals_row_sums_all_quantities_across_month(): void
    {
        [$school, $items] = $this->withCycleAndSchool();
        $this->recordReceipt($school, $items, '2026-06-02', ['banana_bread' => 172], chalan: '10001');
        $this->recordReceipt($school, $items, '2026-06-03', ['banana_bread' => 100], chalan: '10002');

        $data = (new Form4Service)->forSchoolMonth($school, 2026, 6);

        $this->assertSame(272, $data['total_qty']['banana_bread']);
    }

    public function test_receipt_allocated_to_future_month_still_counts_in_receipt_month(): void
    {
        [$school, $items] = $this->withCycleAndSchool();
        $receipt = $this->recordReceipt($school, $items, '2026-06-10', ['banana_bread' => 200], chalan: '20001');

        $receiptItem = $receipt->items()->first();
        DeliveryReceiptItemAllocation::query()->create([
            'delivery_receipt_item_id' => $receiptItem->id,
            'allocation_date' => '2026-07-01',
            'allocated_quantity' => 200,
        ]);

        $data = (new Form4Service)->forSchoolMonth($school, 2026, 6);

        $day10 = $data['days'][9];
        $this->assertFalse($day10['is_empty']);
        $this->assertSame('২০০', $day10['quantities']['banana_bread']);
        $this->assertSame(200, $data['total_qty']['banana_bread']);
    }

    public function test_meta_uses_emis_code_preferring_school_code(): void
    {
        $school = $this->makeSchool('AN-001', 'টেস্ট স্কুল', 'চট্টগ্রাম', 'আনোয়ারা', 'পাইকপাড়া', 'সি-১', '৯১৪১১০৬০১০১');

        $data = (new Form4Service)->forSchoolMonth($school, 2026, 6);

        $this->assertSame('টেস্ট স্কুল', $data['meta']['school_name']);
        $this->assertSame('৯১৪১১০৬০১০১', $data['meta']['school_code']);
        $this->assertSame('চট্টগ্রাম', $data['meta']['district']);
        $this->assertSame('আনোয়ারা', $data['meta']['upazila']);
        $this->assertSame('পাইকপাড়া', $data['meta']['union']);
        $this->assertSame('সি-১', $data['meta']['cluster']);
    }

    public function test_meta_falls_back_to_internal_code_when_no_emis(): void
    {
        $school = $this->makeSchool('AN-001', 'টেস্ট স্কুল', 'চট্টগ্রাম', 'আনোয়ারা');

        $data = (new Form4Service)->forSchoolMonth($school, 2026, 6);

        $this->assertSame('AN-001', $data['meta']['school_code']);
    }

    public function test_to_bangla_converts_digits(): void
    {
        $service = new Form4Service;

        $this->assertSame('০', $service->toBangla(0));
        $this->assertSame('১', $service->toBangla(1));
        $this->assertSame('৯', $service->toBangla(9));
        $this->assertSame('১০', $service->toBangla(10));
        $this->assertSame('১২৩', $service->toBangla(123));
        $this->assertSame('২০২৬', $service->toBangla(2026));
    }

    private function withCycleAndSchool(): array
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => '2026-06-01',
            'ends_on' => '2026-06-30',
        ]);

        $school = $this->participatingSchool($cycle);

        $bread = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'banana_bread', 'name' => 'বনরুটি', 'unit' => 'packet', 'sort_order' => 1]);
        $egg = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'boiled_egg', 'name' => 'সিদ্ধ ডিম', 'unit' => 'piece', 'sort_order' => 2]);
        $banana = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'banana', 'name' => 'কলা', 'unit' => 'piece', 'sort_order' => 3]);

        return [$school, [$bread, $egg, $banana]];
    }

    private function makeSchool(
        string $code = 'TEST-001',
        string $banglaName = 'টেস্ট স্কুল',
        string $district = 'চট্টগ্রাম',
        string $upazila = 'আনোয়ারা',
        string $union = '',
        string $cluster = '',
        ?string $emisCode = null
    ): School {
        return School::factory()->create([
            'code' => $code,
            'bangla_name' => $banglaName,
            'district' => $district,
            'upazila' => $upazila,
            'union' => $union,
            'cluster' => $cluster,
            'emis_code' => $emisCode,
        ]);
    }

    private function participatingSchool(FeedingCycle $cycle): School
    {
        $school = $this->makeSchool();
        $school->participationPeriods()->create(['starts_on' => $cycle->starts_on->toDateString()]);
        $school->enrolments()->create([
            'effective_on' => $cycle->starts_on->toDateString(),
            'pupil_count' => 100,
            'source' => 'test',
        ]);

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
}
