<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\DeliveryReceiptItemAllocation;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScreenshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_capture_dashboard_html(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = $this->openCycle();

        // One school with a shortfall
        $shortfallSchool = $this->participatingSchool($cycle, pupils: 100, code: 'AN-000001');
        $items = $this->items($cycle);
        $this->recordEntry($shortfallSchool, $items, ['bread' => 60, 'egg' => 0, 'banana' => 0]);

        // One school fully delivered
        $deliveredSchool = $this->participatingSchool($cycle, pupils: 80, code: 'AN-000002');
        $this->recordEntry($deliveredSchool, $items, ['bread' => 72, 'egg' => 0, 'banana' => 0]);

        // One school not submitted
        $this->participatingSchool($cycle, pupils: 90, code: 'AN-000003');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        file_put_contents(public_path('screenshot-dashboard.html'), $response->getContent());

        $this->assertFileExists(public_path('screenshot-dashboard.html'));
    }

    private function openCycle(): FeedingCycle
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => Carbon::today()->subDays(5)->toDateString(),
            'ends_on' => Carbon::today()->addDays(20)->toDateString(),
        ]);
        $cycle->forceFill(['ration_factor' => 0.9])->save();

        return $cycle->refresh();
    }

    private function participatingSchool(FeedingCycle $cycle, int $pupils, string $code = 'AN-000001'): School
    {
        $school = School::factory()->create(['code' => $code]);
        $school->participationPeriods()->create([
            'starts_on' => $cycle->starts_on->toDateString(),
            'ends_on' => $cycle->ends_on->toDateString(),
        ]);
        $school->enrolments()->create([
            'effective_on' => $cycle->starts_on->toDateString(),
            'pupil_count' => $pupils,
            'source' => 'test',
        ]);

        return $school->refresh();
    }

    private function items(FeedingCycle $cycle): array
    {
        $bread = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'bread', 'name' => 'Bread', 'sort_order' => 1, 'supply_days' => 16]);
        $egg = FeedingItem::factory()->for($cycle)->withoutSupplyPattern()
            ->create(['item_key' => 'egg', 'name' => 'Egg', 'sort_order' => 2, 'supply_days' => 12]);
        $banana = FeedingItem::factory()->for($cycle)->withoutSupplyPattern()
            ->create(['item_key' => 'banana', 'name' => 'Banana', 'sort_order' => 3, 'supply_days' => 5]);

        return [$bread, $egg, $banana];
    }

    private function recordEntry(School $school, array $items, array $delivered): void
    {
        $staff = User::factory()->create(['role' => 'field_staff']);
        $receipt = DeliveryReceipt::factory()->create([
            'school_id' => $school->id,
            'delivery_date' => Carbon::today()->toDateString(),
            'entered_by' => $staff->id,
            'responsible_by' => $staff->id,
            'chalan_number' => 'CH-'.$school->code,
            'chalan_date' => Carbon::today()->toDateString(),
        ]);

        foreach ($items as $item) {
            $qty = $delivered[$item->item_key] ?? 0;
            if ($qty > 0) {
                $line = DeliveryReceiptItem::factory()->create([
                    'delivery_receipt_id' => $receipt->id,
                    'feeding_item_id' => $item->id,
                    'delivered_quantity' => $qty,
                ]);
                DeliveryReceiptItemAllocation::factory()->create([
                    'delivery_receipt_item_id' => $line->id,
                    'allocation_date' => Carbon::today()->toDateString(),
                    'allocated_quantity' => $qty,
                ]);
            }
        }
    }
}
